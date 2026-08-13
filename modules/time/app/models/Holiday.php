<?php

namespace App\Models;

use PDO;

class Holiday
{
    private $db;
    private $table = 'ta_holidays';
    private $syncTable = 'ta_holiday_sync_log';

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Get all active holidays
     */
    public function getAllHolidays($filters = [])
    {
        // Use COALESCE to provide fallback names - if name is empty, use description, otherwise use a default
        $query = "SELECT 
                    id,
                    COALESCE(NULLIF(name, ''), description, 'Holiday') as name,
                    holiday_date,
                    is_recurring,
                    country_code,
                    description,
                    category,
                    is_active,
                    created_by,
                    created_at,
                    updated_at
                  FROM {$this->table} WHERE is_active = 1";

        if (isset($filters['year'])) {
            $query .= " AND YEAR(holiday_date) = :year";
        }

        if (isset($filters['month'])) {
            $query .= " AND MONTH(holiday_date) = :month";
        }

        if (isset($filters['country_code'])) {
            $query .= " AND country_code = :country_code";
        }

        $query .= " ORDER BY holiday_date ASC";

        $stmt = $this->db->prepare($query);

        if (isset($filters['year'])) {
            $stmt->bindParam(':year', $filters['year']);
        }
        if (isset($filters['month'])) {
            $stmt->bindParam(':month', $filters['month']);
        }
        if (isset($filters['country_code'])) {
            $stmt->bindParam(':country_code', $filters['country_code']);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get upcoming holidays (next 30 days)
     */
    public function getUpcomingHolidays($days = 30)
    {
        $query = "SELECT 
                    id,
                    COALESCE(NULLIF(name, ''), description, 'Holiday') as name,
                    holiday_date,
                    is_recurring,
                    country_code,
                    description,
                    category,
                    is_active,
                    created_by,
                    created_at,
                    updated_at
                  FROM {$this->table} 
                 WHERE is_active = 1 
                 AND holiday_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :days DAY)
                 ORDER BY holiday_date ASC";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get holidays by date range
     */
    public function getHolidaysByRange($startDate, $endDate)
    {
        $query = "SELECT 
                    id,
                    COALESCE(NULLIF(name, ''), description, 'Holiday') as name,
                    holiday_date,
                    is_recurring,
                    country_code,
                    description,
                    category,
                    is_active,
                    created_by,
                    created_at,
                    updated_at
                  FROM {$this->table}
                 WHERE is_active = 1
                 AND holiday_date BETWEEN :startDate AND :endDate
                 ORDER BY holiday_date ASC";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':startDate', $startDate);
        $stmt->bindParam(':endDate', $endDate);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if a date is a holiday
     */
    public function isHoliday($date)
    {
        $query = "SELECT id FROM {$this->table}
                 WHERE is_active = 1
                 AND DATE(holiday_date) = DATE(:date)
                 LIMIT 1";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':date', $date);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Get holiday by date
     */
    public function getHolidayByDate($date)
    {
        $query = "SELECT 
                    id,
                    COALESCE(NULLIF(name, ''), description, 'Holiday') as name,
                    holiday_date,
                    is_recurring,
                    country_code,
                    description,
                    category,
                    is_active,
                    created_by,
                    created_at,
                    updated_at
                  FROM {$this->table}
                 WHERE is_active = 1
                 AND DATE(holiday_date) = DATE(:date)
                 LIMIT 1";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':date', $date);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a holiday
     */
    public function create($data)
    {
        $query = "INSERT INTO {$this->table}
                 (name, holiday_date, is_recurring, country_code, description, category, is_active, created_by)
                 VALUES
                 (:name, :holiday_date, :is_recurring, :country_code, :description, :category, :is_active, :created_by)";

        $stmt = $this->db->prepare($query);

        // Sanitize
        $data['is_recurring'] = $data['is_recurring'] ?? 0;
        $data['country_code'] = $data['country_code'] ?? 'PH';
        $data['category'] = $data['category'] ?? 'national';
        $data['is_active'] = $data['is_active'] ?? 1;

        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':holiday_date', $data['holiday_date']);
        $stmt->bindParam(':is_recurring', $data['is_recurring']);
        $stmt->bindParam(':country_code', $data['country_code']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':is_active', $data['is_active']);
        $stmt->bindParam(':created_by', $data['created_by']);

        return $stmt->execute();
    }

    /**
     * Update holiday
     */
    public function update($id, $data)
    {
        $query = "UPDATE {$this->table} SET ";
        $updates = [];

        foreach ($data as $key => $value) {
            if (in_array($key, ['name', 'holiday_date', 'is_recurring', 'description', 'category', 'is_active'])) {
                $updates[] = "$key = :$key";
            }
        }

        $query .= implode(', ', $updates) . " WHERE id = :id";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);

        foreach ($data as $key => $value) {
            if (in_array($key, ['name', 'holiday_date', 'is_recurring', 'description', 'category', 'is_active'])) {
                $stmt->bindParam(":$key", $data[$key]);
            }
        }

        return $stmt->execute();
    }

    /**
     * Delete holiday (soft delete)
     */
    public function delete($id)
    {
        $query = "UPDATE {$this->table} SET is_active = 0 WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    /**
     * Get recurring holidays for a year
     */
    public function getRecurringHolidaysForYear($year)
    {
        $query = "SELECT 
                    id,
                    COALESCE(NULLIF(name, ''), description, 'Holiday') as name,
                    holiday_date,
                    is_recurring,
                    country_code,
                    description,
                    category,
                    is_active,
                    created_by,
                    created_at,
                    updated_at
                  FROM {$this->table}
                 WHERE is_active = 1
                 AND is_recurring = 1
                 AND YEAR(holiday_date) <= :year
                 ORDER BY MONTH(holiday_date), DAY(holiday_date)";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':year', $year);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Log API sync
     */
    public function logSync($totalHolidays, $countryCode = 'PH')
    {
        $query = "INSERT INTO {$this->syncTable}
                 (sync_date, total_holidays, country_code)
                 VALUES (CURDATE(), :total_holidays, :country_code)
                 ON DUPLICATE KEY UPDATE
                 total_holidays = :total_holidays,
                 last_synced = CURRENT_TIMESTAMP";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':total_holidays', $totalHolidays);
        $stmt->bindParam(':country_code', $countryCode);

        return $stmt->execute();
    }

    /**
     * Get last sync info
     */
    public function getLastSync($countryCode = 'PH')
    {
        $query = "SELECT * FROM {$this->syncTable}
                 WHERE country_code = :country_code
                 ORDER BY last_synced DESC
                 LIMIT 1";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':country_code', $countryCode);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Clear holidays for year (for re-sync)
     */
    public function clearHolidaysForYear($year, $countryCode = 'PH')
    {
        $query = "DELETE FROM {$this->table}
                 WHERE YEAR(holiday_date) = :year
                 AND country_code = :country_code
                 AND is_recurring = 0";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':country_code', $countryCode);

        return $stmt->execute();
    }

    /**
     * Bulk insert holidays
     */
    public function bulkInsert($holidays, $createdBy = null)
    {
        try {
            $this->db->beginTransaction();

            $query = "INSERT INTO {$this->table}
                     (name, holiday_date, is_recurring, country_code, description, category, is_active, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($query);

            foreach ($holidays as $holiday) {
                $stmt->execute([
                    $holiday['name'] ?? '',
                    $holiday['holiday_date'] ?? '',
                    $holiday['is_recurring'] ?? 0,
                    $holiday['country_code'] ?? 'PH',
                    $holiday['description'] ?? '',
                    $holiday['category'] ?? 'national',
                    1, // is_active
                    $createdBy
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
