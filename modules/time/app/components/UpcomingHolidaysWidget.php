<?php

namespace App\Components;

use App\Helpers\HolidayHelper;
use App\Models\Holiday;

class UpcomingHolidaysWidget
{
    private $holiday;
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
        $this->holiday = new Holiday($database);
        HolidayHelper::init($database);
    }

    /**
     * Render upcoming holidays widget
     */
    public function render()
    {
        $upcoming = $this->holiday->getUpcomingHolidays(30);
        $nextHoliday = HolidayHelper::getNextHoliday();
        $daysUntilNext = HolidayHelper::daysUntilNextHoliday();
        $lastSync = $this->holiday->getLastSync('PH');

        ob_start();
        ?>
        <div class="card card-outline card-primary ta-holidays-widget">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-calendar-alt"></i> Upcoming Holidays
                </h3>
                <div class="card-tools">
                    <button class="btn btn-tool" id="syncHolidaysBtn" title="Sync from API">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>

            <div class="card-body">
                <?php if ($nextHoliday): ?>
                    <div class="holiday-countdown mb-3 p-3 bg-light rounded">
                        <div class="text-center">
                            <h5 class="mb-2"><?php echo htmlspecialchars($nextHoliday['name']); ?></h5>
                            <div class="countdown-timer">
                                <span class="badge badge-danger" style="font-size: 1.5rem; padding: 10px 15px;">
                                    <?php echo $daysUntilNext; ?> days
                                </span>
                            </div>
                            <small class="text-muted">
                                <?php echo date('F j, Y', strtotime($nextHoliday['holiday_date'])); ?>
                            </small>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="holiday-list">
                    <?php if (!empty($upcoming)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($upcoming, 0, 5) as $holiday): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            <?php echo htmlspecialchars($holiday['name']); ?>
                                            <span class="badge badge-<?php echo $this->getCategoryBadgeClass($holiday['category']); ?> ml-2">
                                                <?php echo ucfirst($holiday['category']); ?>
                                            </span>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo date('M j, Y', strtotime($holiday['holiday_date'])); ?>
                                            <?php if ($holiday['is_recurring']): ?>
                                                <i class="fas fa-sync-alt ml-1" title="Recurring holiday"></i>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="text-right">
                                        <span class="badge badge-info">
                                            <?php echo HolidayHelper::daysUntilDate($holiday['holiday_date']); ?> days
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($upcoming) > 5): ?>
                            <div class="mt-2 text-center">
                                <small class="text-muted">
                                    +<?php echo count($upcoming) - 5; ?> more holidays
                                </small>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No upcoming holidays in the next 30 days
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($lastSync): ?>
                    <div class="mt-3 pt-3 border-top">
                        <small class="text-muted">
                            <i class="fas fa-sync"></i> Last synced: 
                            <?php echo date('M j, Y h:i A', strtotime($lastSync['last_synced'])); ?>
                            (<?php echo $lastSync['total_holidays']; ?> holidays)
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <link rel="stylesheet" href="../assets/css/upcoming-holidays-widget.css">
        <script src="../assets/js/upcoming-holidays-widget.js"></script>
        <?php

        return ob_get_clean();
    }

    /**
     * Get badge class for category
     */
    private function getCategoryBadgeClass($category)
    {
        $classes = [
            'national' => 'danger',
            'regional' => 'warning',
            'optional' => 'info',
            'special' => 'secondary'
        ];

        return $classes[$category] ?? 'secondary';
    }

    /**
     * Render as JSON for AJAX
     */
    public function renderJson()
    {
        $upcoming = $this->holiday->getUpcomingHolidays(30);
        $nextHoliday = HolidayHelper::getNextHoliday();
        $lastSync = $this->holiday->getLastSync('PH');

        $formatted = [];
        foreach ($upcoming as $holiday) {
            $formatted[] = HolidayHelper::formatHoliday($holiday);
        }

        return [
            'next_holiday' => $nextHoliday ? HolidayHelper::formatHoliday($nextHoliday) : null,
            'upcoming' => $formatted,
            'count' => count($upcoming),
            'last_sync' => $lastSync['last_synced'] ?? null
        ];
    }
}
