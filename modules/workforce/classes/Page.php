<?php
class Page {
    private $default = 'dashboard-overview';
    private $pagesDir;
    private $allowed = [];

    private $labels = [
        'dashboard-overview' => 'Dashboard & Metrics',
        'attrition-turnover' => 'Attrition & Turnover',
        'diversity-inclusion' => 'Diversity & Inclusion',
        'predictive-analytics' => 'Predictive Analytics',
        'custom-reports' => 'Custom Reports',
        'turnover-risk-indicator' => 'Turnover risk indicator',
        'predicted-skill-competency-needs' => 'Predicted skill / Competency needs',
        'implementation-guide' => 'Implementation Guide',
    ];

    private $sections = [
        'analytics' => [
            'dashboard-overview',
            'attrition-turnover',
            'diversity-inclusion',
            'predictive-analytics',
            'custom-reports',
        ],
        'resources' => [
            'turnover-risk-indicator',
            'predicted-skill-competency-needs',
            'implementation-guide',
        ],
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
        echo '<h3>Analytics</h3>';
        foreach ($this->sections['analytics'] as $p) {
            $this->renderLink($p);
        }
        echo '<h3>Resources</h3>';
        foreach ($this->sections['resources'] as $p) {
            $this->renderLink($p);
        }
    }

    private function renderLink($p) {
        $label = $this->labels[$p] ?? ucwords(str_replace('-', ' ', $p));
        $icons = [
            'dashboard-overview' => 'fa-chart-line',
            'attrition-turnover' => 'fa-arrow-trend-down',
            'diversity-inclusion' => 'fa-people-group',
            'predictive-analytics' => 'fa-wand-magic-sparkles',
            'custom-reports' => 'fa-file-lines',
            'turnover-risk-indicator' => 'fa-circle-play',
            'predicted-skill-competency-needs' => 'fa-book-open',
            'implementation-guide' => 'fa-circle-check',
        ];
        $icon = $icons[$p] ?? 'fa-file-lines';
        $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        if (in_array($p, $this->allowed)) {
            $class = $this->isActive($p) ? 'active-menu-link' : 'menu-link';
            $url = '?page=' . rawurlencode($p);
            echo "<li><a href=\"{$url}\" class=\"{$class}\"><i class=\"fa-solid {$icon}\" aria-hidden=\"true\"></i><span>{$safeLabel}</span></a></li>";
        } else {
            echo "<li><a href=\"#\" class=\"menu-link\"><i class=\"fa-solid {$icon}\" aria-hidden=\"true\"></i><span>{$safeLabel}</span></a></li>";
        }
    }
}