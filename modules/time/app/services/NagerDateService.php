<?php

namespace App\Services;

use PDO;
use Exception;

class NagerDateService
{
    private $baseUrl = 'https://date.nager.at/api/v3';
    private $countryCode = 'PH';
    private $db;
    private $timeout = 10;

    public function __construct($database, $countryCode = 'PH')
    {
        $this->db = $database;
        $this->countryCode = $countryCode;
    }

    /**
     * Fetch holidays for a specific year from Nager.Date API
     */
    public function fetchHolidaysForYear($year)
    {
        try {
            $url = "{$this->baseUrl}/PublicHolidays/{$year}/{$this->countryCode}";

            $context = stream_context_create([
                'http' => [
                    'timeout' => $this->timeout,
                    'user_agent' => 'PHP-Holiday-Manager'
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ]
            ]);

            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                throw new Exception("Failed to fetch holidays from Nager.Date API");
            }

            $holidays = json_decode($response, true);

            if (!is_array($holidays)) {
                throw new Exception("Invalid response from Nager.Date API");
            }

            return $this->transformHolidayData($holidays);
        } catch (Exception $e) {
            throw new Exception("Nager.Date API Error: " . $e->getMessage());
        }
    }

    /**
     * Fetch holidays for multiple years
     */
    public function fetchHolidaysForYears($startYear, $endYear)
    {
        $allHolidays = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            try {
                $holidays = $this->fetchHolidaysForYear($year);
                $allHolidays = array_merge($allHolidays, $holidays);
            } catch (Exception $e) {
                // Log error but continue with other years
                error_log("Failed to fetch holidays for year $year: " . $e->getMessage());
            }
        }

        return $allHolidays;
    }

    /**
     * Transform API response to database format
     */
    private function transformHolidayData($apiHolidays)
    {
        $transformed = [];

        foreach ($apiHolidays as $holiday) {
            // Determine if recurring (fixed date holidays recur yearly)
            $isRecurring = isset($holiday['fixed']) ? $holiday['fixed'] : false;

            // Use localName as fallback if name is not available
            $holidayName = $holiday['name'] ?? $holiday['localName'] ?? 'Holiday';

            $transformed[] = [
                'name' => $holidayName,
                'localName' => $holiday['localName'] ?? $holiday['name'] ?? '',
                'holiday_date' => $holiday['date'] ?? '',
                'is_recurring' => $isRecurring ? 1 : 0,
                'country_code' => $this->countryCode,
                'description' => $holiday['localName'] ?? $holiday['name'] ?? '',
                'category' => 'national',
                'holiday_scope' => 'national',
                'is_working_day' => 0,
                'source' => 'api_nager',
                'api_source' => 'nager.date'
            ];
        }

        return $transformed;
    }

    /**
     * Sync holidays for current year and next year
     */
    public function syncHolidays($createdBy = null)
    {
        try {
            $currentYear = date('Y');
            $nextYear = $currentYear + 1;

            // Fetch holidays for current and next year
            $holidays = $this->fetchHolidaysForYears($currentYear, $nextYear);

            if (empty($holidays)) {
                throw new Exception("No holidays fetched from API");
            }

            // Clear existing non-recurring holidays for these years
            $this->clearOldHolidays($currentYear);
            $this->clearOldHolidays($nextYear);

            // Insert new holidays
            $this->bulkInsertHolidays($holidays, $createdBy);

            // Fix any empty holiday names
            $this->fixEmptyHolidayNames();

            // Log the sync
            $this->logSync(count($holidays));

            return [
                'success' => true,
                'message' => "Synced " . count($holidays) . " holidays",
                'count' => count($holidays)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'count' => 0
            ];
        }
    }

    /**
     * Fix empty holiday names in database
     * Update rows where name is empty to use description instead
     */
    public function fixEmptyHolidayNames()
    {
        try {
            // Fix NULL or empty names
            $query = "UPDATE ta_holidays 
                     SET name = COALESCE(NULLIF(description, ''), 'Holiday')
                     WHERE name IS NULL OR name = '' OR TRIM(name) = ''";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $updated = $stmt->rowCount();
            
            // Also ensure description is not empty for display
            $query2 = "UPDATE ta_holidays 
                      SET description = name
                      WHERE description IS NULL OR description = ''";
            $stmt2 = $this->db->prepare($query2);
            $stmt2->execute();
            
            return $updated;
        } catch (Exception $e) {
            error_log("Error fixing empty holiday names: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clear old holidays for a year
     */
    private function clearOldHolidays($year)
    {
        $query = "DELETE FROM ta_holidays
                 WHERE YEAR(holiday_date) = :year
                 AND country_code = :country_code
                 AND is_recurring = 0
                 AND source = 'api_nager'";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':year', $year, PDO::PARAM_INT);
        $stmt->bindParam(':country_code', $this->countryCode);

        return $stmt->execute();
    }

    /**
     * Bulk insert holidays into database
     */
    private function bulkInsertHolidays($holidays, $createdBy = null)
    {
        try {
            $this->db->beginTransaction();

            $query = "INSERT INTO ta_holidays
                     (name, holiday_date, is_recurring, country_code, description, category, holiday_scope, province_name, is_working_day, source, is_active, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($query);

            foreach ($holidays as $holiday) {
                $stmt->execute([
                    $holiday['name'],
                    $holiday['holiday_date'],
                    $holiday['is_recurring'],
                    $holiday['country_code'],
                    $holiday['description'],
                    $holiday['category'],
                    $holiday['holiday_scope'] ?? 'national',
                    $holiday['province_name'] ?? null,
                    $holiday['is_working_day'] ?? 0,
                    $holiday['source'] ?? 'api_nager',
                    1,
                    $createdBy
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Log sync operation
     */
    private function logSync($totalCount)
    {
        $query = "INSERT INTO ta_holiday_sync_log
                 (sync_date, total_holidays, country_code)
                 VALUES (CURDATE(), :total_holidays, :country_code)
                 ON DUPLICATE KEY UPDATE
                 total_holidays = :total_holidays,
                 last_synced = CURRENT_TIMESTAMP";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':total_holidays', $totalCount, PDO::PARAM_INT);
        $stmt->bindParam(':country_code', $this->countryCode);

        return $stmt->execute();
    }

    /**
     * Get last sync timestamp
     */
    public function getLastSyncTime()
    {
        $query = "SELECT last_synced FROM ta_holiday_sync_log
                 WHERE country_code = :country_code
                 ORDER BY last_synced DESC
                 LIMIT 1";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':country_code', $this->countryCode);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['last_synced'] ?? null;
    }
}
