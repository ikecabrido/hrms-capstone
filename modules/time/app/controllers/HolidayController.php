<?php

namespace App\Controllers;

require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../models/Holiday.php';
require_once __DIR__ . '/../services/NagerDateService.php';

use App\Models\Holiday;
use App\Services\NagerDateService;
use Exception;

class HolidayController
{
    private $holiday;
    private $nagerService;
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
        $this->holiday = new Holiday($database);
        $this->nagerService = new NagerDateService($database, 'PH');
    }

    /**
     * Get all holidays
     */
    public function getAllHolidays()
    {
        try {
            $filters = [];
            
            if (isset($_GET['year'])) {
                $filters['year'] = intval($_GET['year']);
            }
            if (isset($_GET['month'])) {
                $filters['month'] = intval($_GET['month']);
            }

            $holidays = $this->holiday->getAllHolidays($filters);

            return $this->response(true, 'Holidays retrieved', [
                'holidays' => $holidays,
                'total' => count($holidays)
            ]);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    /**
     * Get upcoming holidays
     */
    public function getUpcomingHolidays()
    {
        try {
            $days = isset($_GET['days']) ? intval($_GET['days']) : 30;
            $holidays = $this->holiday->getUpcomingHolidays($days);

            return $this->response(true, 'Upcoming holidays retrieved', [
                'holidays' => $holidays,
                'total' => count($holidays)
            ]);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    /**
     * Get holidays by date range
     */
    public function getHolidaysByRange()
    {
        try {
            $startDate = $_GET['startDate'] ?? null;
            $endDate = $_GET['endDate'] ?? null;

            if (!$startDate || !$endDate) {
                return $this->response(false, 'Start date and end date required');
            }

            $holidays = $this->holiday->getHolidaysByRange($startDate, $endDate);

            return $this->response(true, 'Holidays retrieved', [
                'holidays' => $holidays,
                'total' => count($holidays)
            ]);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    /**
     * Check if specific date is a holiday
     */
    public function isHoliday()
    {
        try {
            $date = $_GET['date'] ?? null;

            if (!$date) {
                return $this->response(false, 'Date required');
            }

            $isHoliday = $this->holiday->isHoliday($date);
            $holidayData = null;

            if ($isHoliday) {
                $holidayData = $this->holiday->getHolidayByDate($date);
            }

            return $this->response(true, 'Holiday check completed', [
                'isHoliday' => $isHoliday,
                'holiday' => $holidayData
            ]);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    /**
     * Create holiday
     */
    public function create()
    {
        try {
            $data = $this->getJsonInput();

            // Validate required fields
            if (!isset($data['name']) || !isset($data['holiday_date'])) {
                return $this->response(false, 'Name and holiday date are required');
            }

            $data['created_by'] = $_SESSION['user_id'] ?? null;
            
            if ($this->holiday->create($data)) {
                return $this->response(true, 'Holiday created successfully');
            } else {
                return $this->response(false, 'Failed to create holiday');
            }
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    /**
     * Update holiday
     */
    public function update()
    {
        try {
            $data = $this->getJsonInput();
            $id = $data['id'] ?? null;

            if (!$id) {
                return $this->response(false, 'Holiday ID required');
            }

            unset($data['id']);

            if ($this->holiday->update($id, $data)) {
                return $this->response(true, 'Holiday updated successfully');
            } else {
                return $this->response(false, 'Failed to update holiday');
            }
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    /**
     * Delete holiday
     */
    public function delete()
    {
        try {
            $id = $_GET['id'] ?? null;

            if (!$id) {
                return $this->response(false, 'Holiday ID required');
            }

            if ($this->holiday->delete($id)) {
                return $this->response(true, 'Holiday deleted successfully');
            } else {
                return $this->response(false, 'Failed to delete holiday');
            }
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    /**
     * Sync holidays from Nager.Date API
     */
    public function syncHolidays()
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            $result = $this->nagerService->syncHolidays($userId);

            if ($result['success']) {
                // Also fix any empty names
                $this->nagerService->fixEmptyHolidayNames();
                
                return $this->response(true, $result['message'], [
                    'synced_count' => $result['count'],
                    'last_sync' => $this->nagerService->getLastSyncTime()
                ]);
            } else {
                return $this->response(false, $result['message']);
            }
        } catch (Exception $e) {
            return $this->response(false, 'Sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Fix empty holiday names (helper endpoint)
     */
    public function fixEmptyNames()
    {
        try {
            $fixed = $this->nagerService->fixEmptyHolidayNames();
            return $this->response(true, "Fixed $fixed holiday names", ['fixed_count' => $fixed]);
        } catch (Exception $e) {
            return $this->response(false, 'Fix failed: ' . $e->getMessage());
        }
    }

    /**
     * Get sync info
     */
    public function getSyncInfo()
    {
        try {
            $lastSync = $this->holiday->getLastSync('PH');

            return $this->response(true, 'Sync info retrieved', [
                'lastSync' => $lastSync
            ]);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    /**
     * Get JSON input
     */
    private function getJsonInput()
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    /**
     * Response formatter
     */
    /**
     * Get all data needed for the holiday page
     */
    public function getPageData()
    {
        $allHolidays = [];
        $upcomingHolidays = [];
        
        try {
            // Check if Holiday model exists and has methods
            if (!$this->holiday) {
                return json_encode([
                    'success' => true,
                    'message' => 'Holiday system ready',
                    'holidays' => [],
                    'upcoming' => [],
                    'total' => 0,
                    'upcomingCount' => 0
                ]);
            }
            
            // Try to get all holidays - safely
            try {
                $allHolidays = @$this->holiday->getAllHolidays();
                if (!is_array($allHolidays)) {
                    $allHolidays = [];
                }
            } catch (Throwable $e) {
                // Table might not exist yet
                $allHolidays = [];
            }
            
            // Try to get upcoming holidays - safely
            try {
                $upcomingHolidays = @$this->holiday->getUpcomingHolidays(5);
                if (!is_array($upcomingHolidays)) {
                    $upcomingHolidays = [];
                }
            } catch (Throwable $e) {
                // Table might not exist yet
                $upcomingHolidays = [];
            }
        } catch (Throwable $e) {
            // Fail silently, return empty data
            error_log('Holiday error: ' . $e->getMessage());
        }

        // Always return success with valid data structure
        return json_encode([
            'success' => true,
            'message' => 'Holiday page data retrieved',
            'holidays' => $allHolidays,
            'upcoming' => $upcomingHolidays,
            'total' => count($allHolidays),
            'upcomingCount' => count($upcomingHolidays)
        ]);
    }

    private function response($success, $message, $data = [])
    {
        header('Content-Type: application/json');
        
        return json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
    }
}
