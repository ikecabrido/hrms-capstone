
<?php

class Page
{
    private $default = 'dashboard-overview';
    private $pagesDir;
    private $allowed = [];

    private $labels = [
        'dashboard-overview' => 'Dashboard Overview',
        'job-list'           => 'Job Lists',
        'applications'       => 'Applications',
        'parsed-resume'      => 'Parsed Resumes',
        'schedule-tracker'   => 'Schedule Tracker',
        'interview-results'  => 'Interview Results',
        'offered-list'       => 'Offered Lists',
        'onboarding'         => 'Onboarding',
        'hired-applicants'   => 'Hired Applicants',
    ];

    private $sections = [
        'top'         => ['dashboard-overview'],
        'recruitment' => ['job-list', 'applications', 'parsed-resume'],
        'interviews'  => ['schedule-tracker', 'interview-results'],
        'hiring'      => ['offered-list', 'onboarding', 'hired-applicants'],
    ];

    /*
    |--------------------------------------------------------------------------
    | APPLICATION DISPLAY ROUTES
    |--------------------------------------------------------------------------
    */
    private $applicationRoutes = [
        'applications'          => 'index',
        'application'           => 'show',
        'hired-applicant'       => 'showHiredApplicant',
        'create-new-applicant'  => 'create',
        'archived-applications' => 'archived',
        'offer'                 => 'offer',
        'hired-applicants'      => 'hiredApplicants',
        'offered-list'          => 'offeredList',
    ];

    /*
    |--------------------------------------------------------------------------
    | INTERVIEW DISPLAY ROUTES
    |--------------------------------------------------------------------------
    */
    private $interviewRoutes = [
        'schedule-interview' => 'schedule',
        'interview-result'   => 'result',
    ];

    /*
    |--------------------------------------------------------------------------
    | ONBOARDING DISPLAY ROUTES
    |--------------------------------------------------------------------------
    */
    private $onboardingRoutes = [
        'onboarding-selection' => 'create',
        'onboarding'           => 'tracking',
        'onboarding-tracking'  => 'tracking',
        'onboarding-manage'    => 'manage',
    ];

    /*
    |--------------------------------------------------------------------------
    | ONBOARDING ACTION ROUTES
    |--------------------------------------------------------------------------
    */
    private $onboardingActionRoutes = [
        'onboarding-store'    => 'store',
        'onboarding-progress' => 'saveProgress',
    ];

    /*
    |--------------------------------------------------------------------------
    | INTERVIEW ACTION ROUTES
    |--------------------------------------------------------------------------
    */
    private $interviewActionRoutes = [
        'save-interview'  => 'save',
        'update-result'   => 'updateResult',
        'submit-feedback' => 'submitFeedback',
    ];

    /*
    |--------------------------------------------------------------------------
    | APPLICATION ACTION ROUTES
    |--------------------------------------------------------------------------
    */
    private $actionRoutes = [
        'store-application'    => 'store',
        'approve-application'  => 'approve',
        'reject-application'   => 'reject',
        'restore-application'  => 'restore',
        'send-offer'           => 'sendOffer',
        'accept-offer'         => 'acceptOffer',
        'reject-offer'         => 'rejectOffer',
        'negotiate-offer'      => 'negotiateOffer',
        'update-salary'        => 'updateSalary',
        'mark-ready-for-offer' => 'markReadyForOffer',
        'hire-applicant'       => 'hire',

        // Transfer a hired applicant into em_employees.
        'move-to-employee'     => 'moveToEmployee',
    ];

    public function __construct($pagesDir = null)
    {
        $this->pagesDir = $pagesDir ?? dirname(__DIR__) . '/pages';

        $this->discoverPages();
    }

    /*
    |--------------------------------------------------------------------------
    | DISCOVER NORMAL PAGE FILES
    |--------------------------------------------------------------------------
    */
    private function discoverPages()
    {
        if (!is_dir($this->pagesDir)) {
            return;
        }

        foreach (glob($this->pagesDir . '/*.php') as $file) {
            $page = basename($file, '.php');

            $this->allowed[] = $page;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CURRENT PAGE
    |--------------------------------------------------------------------------
    */
    public function getPage()
    {
        return $_GET['page'] ?? $this->default;
    }

    /*
    |--------------------------------------------------------------------------
    | HANDLE ACTION ROUTES BEFORE HTML OUTPUT
    |--------------------------------------------------------------------------
    */
    public function handleAction()
    {
        $page = $this->getPage();

        /*
         * INTERVIEW ACTIONS
         */
        if (isset($this->interviewActionRoutes[$page])) {
            require_once __DIR__ . '/../controllers/InterviewController.php';

            $controller = new InterviewController();
            $method = $this->interviewActionRoutes[$page];

            if (!method_exists($controller, $method)) {
                die("InterviewController method '" .
                    htmlspecialchars($method, ENT_QUOTES, 'UTF-8') .
                    "' does not exist.");
            }

            $controller->$method();

            return;
        }

        /*
         * ONBOARDING ACTIONS
         */
        if (isset($this->onboardingActionRoutes[$page])) {
            require_once __DIR__ . '/../controllers/OnboardingController.php';

            $controller = new OnboardingController();
            $method = $this->onboardingActionRoutes[$page];

            if (!method_exists($controller, $method)) {
                die("OnboardingController method '" .
                    htmlspecialchars($method, ENT_QUOTES, 'UTF-8') .
                    "' does not exist.");
            }

            $controller->$method();

            return;
        }

        /*
         * APPLICATION ACTIONS
         */
        if (isset($this->actionRoutes[$page])) {
            require_once __DIR__ . '/../controllers/ApplicationController.php';

            $controller = new ApplicationController();
            $method = $this->actionRoutes[$page];

            if (!method_exists($controller, $method)) {
                die("ApplicationController method '" .
                    htmlspecialchars($method, ENT_QUOTES, 'UTF-8') .
                    "' does not exist.");
            }

            $controller->$method();

            return;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER CURRENT PAGE
    |--------------------------------------------------------------------------
    */
    public function render()
    {
        $page = $this->getPage();

        /*
         * ACTION ROUTES DO NOT RENDER A VIEW
         */
        if (
            isset($this->actionRoutes[$page]) ||
            isset($this->interviewActionRoutes[$page]) ||
            isset($this->onboardingActionRoutes[$page])
        ) {
            return;
        }

        /*
         * INTERVIEW DISPLAY ROUTES
         */
        if (isset($this->interviewRoutes[$page])) {
            require_once __DIR__ . '/../controllers/InterviewController.php';

            $controller = new InterviewController();
            $method = $this->interviewRoutes[$page];

            if (!method_exists($controller, $method)) {
                die("InterviewController method '" .
                    htmlspecialchars($method, ENT_QUOTES, 'UTF-8') .
                    "' does not exist.");
            }

            $controller->$method();

            return;
        }

        /*
         * ONBOARDING DISPLAY ROUTES
         */
        if (isset($this->onboardingRoutes[$page])) {
            require_once __DIR__ . '/../controllers/OnboardingController.php';

            $controller = new OnboardingController();
            $method = $this->onboardingRoutes[$page];

            if (!method_exists($controller, $method)) {
                die("OnboardingController method '" .
                    htmlspecialchars($method, ENT_QUOTES, 'UTF-8') .
                    "' does not exist.");
            }

            $controller->$method();

            return;
        }

        /*
         * APPLICATION DISPLAY ROUTES
         */
        if (isset($this->applicationRoutes[$page])) {
            require_once __DIR__ . '/../controllers/ApplicationController.php';

            $controller = new ApplicationController();
            $method = $this->applicationRoutes[$page];

            if (!method_exists($controller, $method)) {
                die("ApplicationController method '" .
                    htmlspecialchars($method, ENT_QUOTES, 'UTF-8') .
                    "' does not exist.");
            }

            $controller->$method();

            return;
        }

        /*
         * NORMAL PAGE FILES
         */
        if (!in_array($page, $this->allowed, true)) {
            $page = $this->default;
        }

        $file = $this->pagesDir . '/' . $page . '.php';

        if (file_exists($file)) {
            include $file;
        } else {
            include $this->pagesDir . '/' . $this->default . '.php';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SIDEBAR ACTIVE STATE
    |--------------------------------------------------------------------------
    */
    public function isActive($page)
    {
        $currentPage = $this->getPage();

        /*
         * Keep Hired Applicants active while viewing
         * an individual hired applicant.
         */
        if (
            $page === 'hired-applicants' &&
            $currentPage === 'hired-applicant'
        ) {
            return true;
        }

        return $currentPage === $page;
    }

    /*
    |--------------------------------------------------------------------------
    | GET ALLOWED PAGES
    |--------------------------------------------------------------------------
    */
    public function getAllowedPages()
    {
        return $this->allowed;
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER SIDEBAR NAVIGATION
    |--------------------------------------------------------------------------
    */
    public function renderNav()
    {
        foreach ($this->sections['top'] as $page) {
            $this->renderLink($page);
        }

        $sectionOrder = [
            'recruitment',
            'interviews',
            'hiring',
        ];

        foreach ($sectionOrder as $section) {
            echo '<div class="separator"></div>';

            echo '<h3>' .
                htmlspecialchars(
                    ucwords(str_replace('-', ' ', $section)),
                    ENT_QUOTES,
                    'UTF-8'
                ) .
                '</h3>';

            foreach ($this->sections[$section] as $page) {
                $this->renderLink($page);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER ONE SIDEBAR LINK
    |--------------------------------------------------------------------------
    */
    private function renderLink($page)
    {
        $label = $this->labels[$page]
            ?? ucwords(str_replace('-', ' ', $page));

        if (in_array($page, $this->allowed, true)) {
            $class = $this->isActive($page)
                ? 'active-menu-link'
                : 'menu-link';

            echo '<li>';

            echo '<a href="?page="' .
                htmlspecialchars($page, ENT_QUOTES, 'UTF-8') .
                '" ';

            echo 'data-page="' .
                htmlspecialchars($page, ENT_QUOTES, 'UTF-8') .
                '" ';

            echo 'class="' . $class . '">';

            echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

            echo '</a>';

            echo '</li>';
        } else {
            echo '<li>';

            echo '<a href="#" class="menu-link">';

            echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

            echo '</a>';

            echo '</li>';
        }
    }
}
