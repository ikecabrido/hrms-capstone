<?php
class Page {
    private $default = 'dashboard';
    private $pagesDir;
    private $allowed = [];

    private $labels = [
        'dashboard' => 'Dashboard',
        'absence_late_management' => 'Absence & Late Management',
        'shifts' => 'Shift Management',
        'qr_scanner' => 'Attendance Scanner',
        'employee_qr_list'=> 'Employee QR',
        'schedule_calendar'=> 'Schedule Calendar',
        'holidays'=> 'Holidays',
        'leave_approvals' => 'Leave Management',
    ];

    private $sections = [
        'top'             => ['dashboard'],
        'Attendance'  => ['qr_scanner', 'employee_qr_list', 'absence_late_management'],
        'Scheduling & Shifts'      => ['shifts', 'schedule_calendar'],
        'Leave & Holidays'       => ['holidays', 'leave_approvals']
    ];

    public function __construct($pagesDir = null) {
        $this->pagesDir = $pagesDir ?? dirname(__DIR__) . '/pages';
        $this->discoverPages();
    }

    private function discoverPages() {
        if (!is_dir($this->pagesDir)) return;
        foreach (glob($this->pagesDir . '/*.php') as $file) {
            $this->allowed[] = basename($file, '.php');
        }
    }

    public function getPage() {
        if (!empty($_GET['page']) && in_array($_GET['page'], $this->allowed)) {
            return $_GET['page'];
        }
        return $this->default;
    }

    public function render() {
        $page = $this->getPage();
        $file = $this->pagesDir . '/' . $page . '.php';
        if (file_exists($file)) {
            include $file;
        } else {
            include $this->pagesDir . '/' . $this->default . '.php';
        }
    }

    public function isActive($page) {
        return $this->getPage() === $page;
    }

    public function getAllowedPages() {
        return $this->allowed;
    }

    public function renderNav() {
        // Top section (no heading/separator)
        foreach ($this->sections['top'] as $p) {
            $this->renderLink($p);
        }

        // Grouped sections
        $sectionOrder = ['Attendance', 'Scheduling & Shifts', 'Leave & Holidays'];
        foreach ($sectionOrder as $section) {
            echo '<div class="separator"></div>';
            echo '<h3>' . ucwords(str_replace('-', ' ', $section)) . '</h3>';
            foreach ($this->sections[$section] as $p) {
                $this->renderLink($p);
            }
        }
    }

    private function renderLink($p) {
        $label = $this->labels[$p] ?? ucwords(str_replace('-', ' ', $p));
        if (in_array($p, $this->allowed)) {
            $class = $this->isActive($p) ? 'active-menu-link' : 'menu-link';
            echo "<li><a href=\"?page={$p}\" data-page=\"{$p}\" class=\"{$class}\">{$label}</a></li>";
        } else {
            echo "<li><a href=\"#\" class=\"menu-link\">{$label}</a></li>";
        }
    }
}