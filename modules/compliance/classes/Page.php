<?php
class Page
{
    private $default = 'dashboard-overview';
    private $pagesDir;
    private $allowed = [];

    private $labels = [
        'dashboard-overview' => 'Dashboard Overview',
        'sent-history' => 'Sent History',

        'government-registration' => 'Government Registration',

        'sss-contribution' => 'SSS Compliance',
        'philhealth-contributions' => 'PhilHealth Compliance',
        'pagibig_monitoring' => 'Pag-IBIG Compliance',
        'bir-monitoring' => 'BIR Compliance',
        'salary-compliance' => 'Salary Compliance',
        'labor-compliance' => 'Labor Law Resources',
        'salary-adjustments' => 'Salary Compliance & Adjustments',
        'policy-management' => 'Policy Management',
        'policy-create' => 'Create Policy',
        'policy-view' => 'Policy Details',
        'policy-acknowledge' => 'My Policies',
        'policy-read' => 'Read Policy',
        'acknowledgement-report' => 'Acknowledgement Report',

        'generate-document' => 'Generate Document',
        'document-requests' => 'Document Services',

        'employee-documents' => 'Document Management',
        'legal-documents' => 'Document Management',

        'handbook-acknowledge' => 'Handbook Acknowledgement',
        'incident-reports' => 'Incident Management',
        'incident-workflow' => 'Incident Workflow',
        'case-records' => 'Complaint Management',
        'complaint-workflow' => 'Complaint Workflow',

        'risk-register' => 'Risk Assessment',

        'exit-acknowledgement' => 'Exit Acknowledgement',
        'exit-documents' => 'Exit Management',

        'audit-trail' => 'Reporting',
    ];

    private $sections = [
        'top' => ['dashboard-overview'],

        'labor-law-compliance' => [
            'government-registration',
            'sss-contribution',
            'philhealth-contributions',
            'pagibig_monitoring',
            'bir-monitoring',
            'salary-compliance',
            'labor-compliance',
        ],

        'policy-documentation' => [
            'document-requests',
            'employee-documents',
            'policy-management',
        ],

        'employee-self-service' => [
            'handbook-acknowledge',
            'policy-acknowledge',
        ],

        'incident-reporting' => [
            'incident-reports',
            'case-records',
        ],

        'risk-assessment' => [
            'risk-register',
        ],

        'exit-acknowledgement' => [
            'exit-documents',
        ],

        'audits-reporting' => [
            'audit-trail',
        ],
    ];

    public function __construct($pagesDir = null)
    {
        $this->pagesDir = $pagesDir ?? dirname(__DIR__) . '/pages';
        $this->discoverPages();
    }

    private function discoverPages()
    {
        if (!is_dir($this->pagesDir)) return;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->pagesDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $rel = substr($file->getPathname(), strlen($this->pagesDir) + 1);
                $rel = substr($rel, 0, -4);
                $rel = str_replace(DIRECTORY_SEPARATOR, '/', $rel);
                if (str_starts_with($rel, 'labor-law-compliance/templates/')) {
                    continue;
                }
                $this->allowed[] = $rel;
            }
        }
        sort($this->allowed);
    }

    public function getPage()
    {
        if (!empty($_GET['page']) && in_array($_GET['page'], $this->allowed, true)) {
            return $_GET['page'];
        }
        return $this->default;
    }

    public function render()
    {
        $page = $this->getPage();
        $file = $this->pagesDir . '/' . $page . '.php';

        ob_start();
        if (file_exists($file)) {
            include $file;
        } else {
            include $this->pagesDir . '/' . $this->default . '.php';
        }
        $content = ob_get_clean();

        $label = $this->labels[$page] ?? ucwords(str_replace('-', ' ', $page));
        if (isset($pageTitle) && is_string($pageTitle) && $pageTitle !== '') {
            $label = $pageTitle;
        }

        $skipHeader = false;
        if (isset($skipModuleHeader) && $skipModuleHeader === true) {
            $skipHeader = true;
        }

        if (!$skipHeader) {
            echo '<div class="module-header">';
            if (isset($moduleHeaderImage) && is_string($moduleHeaderImage) && $moduleHeaderImage !== '') {
                echo '<img src="' . htmlspecialchars($moduleHeaderImage) . '" alt="' . htmlspecialchars($label) . '" class="module-header-logo">';
            }
            echo '<h1>' . htmlspecialchars($label) . '</h1>';
            echo '</div>';
        }
        echo $content;
    }

    public function isActive($page)
    {
        return $this->getPage() === $page;
    }

    public function getAllowedPages()
    {
        return $this->allowed;
    }

    private $sectionLabels = [
        'labor-law-compliance' => 'Labor Law Compliance',
        'policy-documentation' => 'Legal Documents',
        'incident-reporting' => 'Incident Reporting',
        'risk-assessment' => 'Risk Assessment',
        'exit-acknowledgement' => 'Exit Acknowledgement',
        'audits-reporting' => 'Reporting',
    ];

    public function renderNav()
    {
        echo '<ul>';
        foreach ($this->sections['top'] as $p) {
            $this->renderLink($p);
        }
        echo '</ul>';

        $sectionOrder = [
            'labor-law-compliance',
            'policy-documentation',
            'incident-reporting',
            'risk-assessment',
            'exit-acknowledgement',
            'audits-reporting',
        ];

        foreach ($sectionOrder as $section) {
            echo '<div class="separator"></div>';
            echo '<h3>' . ($this->sectionLabels[$section] ?? ucwords(str_replace('-', ' ', $section))) . '</h3>';
            echo '<ul>';
            foreach ($this->sections[$section] as $p) {
                $this->renderLink($p);
            }
            echo '</ul>';
        }
    }

    private function renderLink($p)
    {
        $label = $this->labels[$p] ?? ucwords(str_replace('-', ' ', $p));
        if (in_array($p, $this->allowed)) {
            $class = $this->isActive($p) ? 'active-menu-link' : 'menu-link';
            echo "<li><a href=\"?page={$p}\" data-page=\"{$p}\" class=\"{$class}\">{$label}</a></li>";
        } else {
            echo "<li><a href=\"#\" class=\"menu-link\">{$label}</a></li>";
        }
    }
}
