<?php

class Page
{
    private $default = 'dashboard-overview';
    private $pagesDir;
    private $allowed = [];

    private $labels = [
        'dashboard-overview' => 'Dashboard Overview',
        'account-creation'   => 'Account Creation',
        'job-list'           => 'Job Lists',
        'applications'       => 'Applications',
        'parsed-resume'      => 'Parsed Resumes',
        'schedule-tracker'   => 'Schedule Tracker',
        'interview-results'  => 'Interview Results',
        'offered-list'       => 'Offered Lists',
        'hired-applicants'   => 'Hired Applicants',
        'onboarding'         => 'Onboarding',
    ];

    private $sections = [
        'top'             => ['dashboard-overview'],
        'user-management' => ['account-creation'],
        'recruitment'     => ['job-list', 'applications', 'parsed-resume'],
        'interviews'      => ['schedule-tracker', 'interview-results'],
        'hiring'          => ['offered-list', 'hired-applicants', 'onboarding'],
    ];

    /*
     * APPLICATION DISPLAY ROUTES
     */
    private $applicationRoutes = [
        'applications'          => 'index',
        'application'           => 'show',
        'create-new-applicant'  => 'create',
        'archived-applications' => 'archived',
        'offer'                 => 'offer',
        'hired-applicants'      => 'hiredApplicants',
        'offered-list'          => 'offeredList',
    ];

    /*
     * INTERVIEW DISPLAY ROUTES
     */
    private $interviewRoutes = [
        'schedule-interview' => 'schedule',
        'interview-result'   => 'result',
    ];

    private $onboardingRoutes = [
        'onboarding-selection' => 'create',
        'onboarding'           => 'tracking',
        'onboarding-tracking'  => 'tracking',
        'onboarding-manage'    => 'manage',
    ];

    private $onboardingActionRoutes = [
        'onboarding-store'    => 'store',
        'onboarding-progress' => 'saveProgress',
    ];

    /*
     * INTERVIEW ACTION ROUTES
     */
    private $interviewActionRoutes = [
        'save-interview'   => 'save',
        'update-result'    => 'updateResult',
        'submit-feedback'  => 'submitFeedback',
    ];

    /*
     * APPLICATION ACTION ROUTES
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
    ];

    public function __construct($pagesDir = null)
    {
        $this->pagesDir = $pagesDir ?? dirname(__DIR__) . '/pages';

        $this->discoverPages();
    }

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

    public function getPage()
    {
        return $_GET['page'] ?? $this->default;
    }

    public function handleAction()
    {
        $page = $this->getPage();

        /*
         * INTERVIEW ACTIONS
         */
        if (isset($this->interviewActionRoutes[$page])) {

            require_once __DIR__ .
                '/../controllers/InterviewController.php';

            $controller = new InterviewController();

            $method = $this->interviewActionRoutes[$page];

            if (!method_exists($controller, $method)) {
                die("InterviewController method '" .
                    htmlspecialchars($method) .
                    "' does not exist.");
            }

            $controller->$method();

            return;
        }

        if (isset($this->onboardingActionRoutes[$page])) {
            require_once __DIR__ . '/../controllers/OnboardingController.php';

            $controller = new OnboardingController();
            $method = $this->onboardingActionRoutes[$page];

            if (!method_exists($controller, $method)) {
                die("OnboardingController method '{$method}' does not exist.");
            }

            $controller->$method();
            return;
        }

        /*
         * APPLICATION ACTIONS
         */
        if (isset($this->actionRoutes[$page])) {

            require_once __DIR__ .
                '/../controllers/ApplicationController.php';

            $controller = new ApplicationController();

            $method = $this->actionRoutes[$page];

            if (!method_exists($controller, $method)) {
                die("ApplicationController method '" .
                    htmlspecialchars($method) .
                    "' does not exist.");
            }

            $controller->$method();

            return;
        }
    }

    public function render()
    {
        $page = $this->getPage();

        /*
         * ACTION ROUTES
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

            require_once __DIR__ .
                '/../controllers/InterviewController.php';

            $controller = new InterviewController();

            $method = $this->interviewRoutes[$page];

            if (!method_exists($controller, $method)) {
                die("InterviewController method '{$method}' does not exist.");
            }

            $controller->$method();

            return;
        }

        if (isset($this->onboardingRoutes[$page])) {
            require_once __DIR__ . '/../controllers/OnboardingController.php';

            $controller = new OnboardingController();
            $method = $this->onboardingRoutes[$page];

            if (!method_exists($controller, $method)) {
                die("OnboardingController method '{$method}' does not exist.");
            }

            $controller->$method();
            return;
        }

        /*
         * APPLICATION DISPLAY ROUTES
         */
        if (isset($this->applicationRoutes[$page])) {

            require_once __DIR__ .
                '/../controllers/ApplicationController.php';

            $controller = new ApplicationController();

            $method = $this->applicationRoutes[$page];

            if (!method_exists($controller, $method)) {
                die("ApplicationController method '{$method}' does not exist.");
            }

            $controller->$method();

            return;
        }

        /*
         * NORMAL PAGES
         */
        if (!in_array($page, $this->allowed)) {
            $page = $this->default;
        }

        $file = $this->pagesDir . '/' . $page . '.php';

        if (file_exists($file)) {
            include $file;
        } else {
            include $this->pagesDir . '/' . $this->default . '.php';
        }
    }

    public function isActive($page)
    {
        return $this->getPage() === $page;
    }

    public function getAllowedPages()
    {
        return $this->allowed;
    }

    public function renderNav()
    {
        foreach ($this->sections['top'] as $p) {
            $this->renderLink($p);
        }

        $sectionOrder = [
            'user-management',
            'recruitment',
            'interviews',
            'hiring'
        ];

        foreach ($sectionOrder as $section) {

            echo '<div class="separator"></div>';

            echo '<h3>' .
                ucwords(
                    str_replace('-', ' ', $section)
                ) .
                '</h3>';

            foreach ($this->sections[$section] as $p) {
                $this->renderLink($p);
            }
        }
    }

    private function renderLink($p)
    {
        $label = $this->labels[$p]
            ?? ucwords(
                str_replace('-', ' ', $p)
            );

        if (in_array($p, $this->allowed)) {

            $class = $this->isActive($p)
                ? 'active-menu-link'
                : 'menu-link';

            echo '<li>';

            echo '<a href="?page=' .
                htmlspecialchars($p) .
                '" ';

            echo 'data-page="' .
                htmlspecialchars($p) .
                '" ';

            echo 'class="' .
                $class .
                '">';

            echo htmlspecialchars($label);

            echo '</a>';

            echo '</li>';
        } else {

            echo '<li>';

            echo '<a href="#" class="menu-link">';

            echo htmlspecialchars($label);

            echo '</a>';

            echo '</li>';
        }
    }
}
