<?php
require_once __DIR__ . '/dberror.php';
require_once __DIR__ . '/backlink.php';
require_once __DIR__ . '/LearningRole.php';
require_once __DIR__ . '/AIAuthoring.php';
require_once __DIR__ . '/ProfileLink.php';

class Page {
    private $default = 'dashboard-overview';
    private $pagesDir;
    private $allowed = [];

    private $rolePages = [
        'admin' => 'admin/admin-home.php',
        'instructor' => 'instructor/instructor-home.php',
        'learner' => 'learner/learner-home.php',
    ];

    private $roleHomePages = [
        'admin' => 'admin/admin-home',
        'instructor' => 'instructor/instructor-home',
        'learner' => 'learner/learner-home',
    ];

    private $allowedRoles = ['admin', 'instructor', 'learner'];

    /**
     * Page trees each role may open. A request for a page outside the role's own
     * tree falls back to that role's dashboard rather than rendering it.
     *
     * admin also gets the instructor tree because its navigation is the admin items
     * merged with the instructor ones — see getNavItems().
     */
    private $rolePagePrefixes = [
        'admin'      => ['admin/', 'instructor/', 'public/'],
        'instructor' => ['instructor/', 'public/'],
        'learner'    => ['learner/', 'public/'],
    ];

    private $navConfig = [
        'admin' => [
            ['label' => 'Home', 'page' => 'admin/admin-home'],
            ['label' => 'User', 'page' => 'admin/user'],
            ['label' => 'Grade Book', 'page' => 'admin/gradebook'],
            ['label' => 'Analytics', 'page' => 'admin/analytics'],
            ['label' => 'AI Assistant', 'page' => 'admin/ai-assistant'],
            ['label' => 'Workforce Inbox', 'page' => 'admin/workforce-integration'],
            ['label' => 'Calendar', 'page' => 'admin/calendar'],
            ['label' => 'Moderation', 'page' => 'admin/moderation'],
            ['label' => 'Notifications', 'page' => 'admin/notification'],
            ['label' => 'Settings', 'page' => 'admin/settings'],
        ],
        'instructor' => [
            ['label' => 'Home', 'page' => 'instructor/instructor-home'],
            ['label' => 'E-Learning', 'page' => 'instructor/elearning'],
            ['label' => 'Analytics', 'page' => 'instructor/analytics'],
            ['label' => 'Grade Book', 'page' => 'instructor/gradebook'],
            ['label' => 'Calendar', 'page' => 'instructor/calendar'],
            ['label' => 'Trainings', 'page' => 'instructor/training'],
            ['label' => 'Training Requests', 'page' => 'instructor/training-requests'],
            ['label' => 'Certificates', 'page' => 'instructor/certificate'],
            ['label' => 'Skill Gaps', 'page' => 'instructor/instructor-skill-gap'],
            ['label' => 'Notification', 'page' => 'instructor/notification'],
        ],
        'learner' => [
            ['label' => 'Home', 'page' => 'learner/learner-home'],
            ['label' => 'Study', 'page' => 'learner/study'],
            ['label' => 'Catalog', 'page' => 'learner/catalog'],
            ['label' => 'Grade Book', 'page' => 'learner/result'],
            ['label' => 'Tool', 'page' => 'learner/my-learning-path'],
            ['label' => 'Calendar', 'page' => 'learner/calendar'],
            ['label' => 'Notes', 'page' => 'learner/notes'],
            ['label' => 'Notifications', 'page' => 'learner/notification'],
        ],
    ];

    public function __construct($pagesDir = null) {
        $this->pagesDir = $pagesDir ?? dirname(__DIR__) . '/pages';
        $this->discoverPages();
    }

    private function discoverPages() {
        if (!is_dir($this->pagesDir)) return;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->pagesDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($this->pagesDir) + 1));
            $relative = substr($relative, 0, -4);

            // AJAX endpoints and shared partials are not pages. Keeping them out
            // of the routed page list prevents them from being included on their
            // own with missing request data or missing parent-page variables.
            if (strpos($relative, '/ajax/') !== false || $relative === 'admin/user-subpage/role-control') {
                continue;
            }

            $this->allowed[] = $relative;
        }
    }

    public function getPage() {
        $requested = isset($_GET['page']) && is_string($_GET['page']) ? $_GET['page'] : '';

        if ($requested !== '' && in_array($requested, $this->allowed, true)) {
            if ($requested === 'instructor/analytics' && $this->getLearningRole() === 'admin') {
                header('Location: ?page=admin/analytics');
                exit;
            }

            if ($this->isPageAllowedForRole($requested)) {
                return $requested;
            }
            error_log('[learning] blocked ' . $requested . ' for role ' . $this->getLearningRole());
        }

        return $this->default;
    }

    /**
     * Whether the current role may open this page. Pages live in per-role trees,
     * so a learner cannot open an admin page and vice versa — the request falls
     * back to the requesting role's dashboard.
     */
    private function isPageAllowedForRole(string $page): bool {
        $role = $this->getLearningRole();
        if (!in_array($role, $this->allowedRoles, true)) {
            return false;
        }
        $prefixes = $this->rolePagePrefixes[$role] ?? $this->rolePagePrefixes['learner'];

        foreach ($prefixes as $prefix) {
            if (strpos($page, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    public function getLearningRole() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['employee_id'])) {
            try {
                $database = new Database();
                $role = LearningRole::forEmployee($database->getConnection(), (int) $_SESSION['employee_id']);
                $_SESSION['learning_role'] = $role;
                return $role;
            } catch (Throwable $e) {
                error_log('[learning] Role resolution failed: ' . $e->getMessage());
                $_SESSION['learning_role'] = 'learner';
                return 'learner';
            }
        }

        $sessionRole = strtolower((string) ($_SESSION['learning_role'] ?? $_SESSION['role'] ?? $_SESSION['user_role'] ?? ''));

        if ($sessionRole !== '' && in_array($sessionRole, $this->allowedRoles, true)) {
            $_SESSION['learning_role'] = $sessionRole;
            return $_SESSION['learning_role'];
        }

        if (!empty($_GET['learning_role']) && in_array($_GET['learning_role'], $this->allowedRoles, true)) {
            $_SESSION['learning_role'] = $_GET['learning_role'];
            return $_SESSION['learning_role'];
        }

        if (!empty($_SESSION['is_admin']) || !empty($_SESSION['admin_access'])) {
            $_SESSION['learning_role'] = 'admin';
            return 'admin';
        }

        $_SESSION['learning_role'] = 'learner';
        return 'learner';
    }

    public function getDashboardFile() {
        $role = $this->getLearningRole();
        $roleFile = $this->rolePages[$role] ?? $this->rolePages['learner'];
        return $this->pagesDir . '/' . $roleFile;
    }

    private function getLearnerToolPages() {
        return [
            'learner/my-learning-path',
            'learner/skill-gap',
            'learner/study-subpage/skill',
            'learner/knowledge-transfer',
        ];
    }

    public function render() {
        $page = $this->getPage();
        if ($page === $this->default) {
            $file = $this->getDashboardFile();
        } else {
            $file = $this->pagesDir . '/' . $page . '.php';
        }
        $learnerToolsPages = $this->getLearnerToolPages();
        $hasLearnerTools = $this->getLearningRole() === 'learner' && in_array($page, $learnerToolsPages, true);
        // Learner Tools drawer styling lives in css/pages/tools.css (loaded by includes/header.php).
        echo '<div class="page-content' . ($hasLearnerTools ? ' has-learner-tools' : '') . '" data-page="' . htmlspecialchars($page) . '">';

        // Buffer the page so a database failure captured while rendering it can be
        // surfaced as a visible notice instead of hiding behind an empty result set.
        ob_start();
        if (file_exists($file)) {
            include $file;
        } else {
            include $this->getDashboardFile();
        }
        $pageOutput = ob_get_clean();

        $errorBanner = class_exists('DbError') && DbError::hasErrors()
            ? DbError::renderBanner()
            : '';

        if ($hasLearnerTools) {
            echo '<div class="learner-tools-shell is-open" id="learner-tools-drawer">';
            echo '<button type="button" class="learner-tools-backdrop" aria-label="Close Tools"></button>';
            echo '<button type="button" class="learner-tools-toggle" aria-controls="learner-tools-panel" aria-expanded="true" aria-label="Hide Tools" title="Hide Tools"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>';
            echo '<aside class="learner-tools-panel" id="learner-tools-panel" aria-label="Tools">';
            echo '<nav>';
            $toolIcons = [
                'learner/my-learning-path' => 'fa-route',
                'learner/skill-gap' => 'fa-chart-line',
                'learner/study-subpage/skill' => 'fa-star',
                'learner/knowledge-transfer' => 'fa-right-left',
            ];
            foreach ($learnerToolsPages as $toolPage) {
                $toolLabel = [
                    'learner/my-learning-path' => 'My Learning Path',
                    'learner/skill-gap' => 'Skill Gap',
                    'learner/study-subpage/skill' => 'My Skills',
                    'learner/knowledge-transfer' => 'Knowledge Transfer',
                ][$toolPage] ?? ucfirst(str_replace('-', ' ', basename($toolPage)));
                $toolIcon = $toolIcons[$toolPage] ?? 'fa-circle-dot';
                $active = $page === $toolPage ? ' active' : '';
                echo '<a href="?page=' . urlencode($toolPage) . '" data-page="' . htmlspecialchars($toolPage, ENT_QUOTES, 'UTF-8') . '" class="learner-tools-panel-link' . $active . '"' . ($page === $toolPage ? ' aria-current="page"' : '') . '>'
                    . '<i class="fas ' . $toolIcon . '" aria-hidden="true"></i>'
                    . '<span>' . htmlspecialchars($toolLabel, ENT_QUOTES, 'UTF-8') . '</span></a>';
            }
            echo '</nav>';
            echo '</aside>';
            echo '</div>';
            // The panel opens on arrival so the tools stay discoverable, then tucks
            // itself away one second later so it stops covering the page content.
            // Reaching for it (pointer or keyboard) cancels that. Small screens keep
            // the old behaviour: closed until asked.
            echo '<script>(function () {
                var drawer = document.getElementById("learner-tools-drawer");
                if (!drawer) return;
                var toggle = drawer.querySelector(".learner-tools-toggle");
                if (!toggle) return;
                function hideTools() {
                    drawer.classList.remove("is-open");
                    toggle.setAttribute("aria-expanded", "false");
                    toggle.setAttribute("aria-label", "Show Tools");
                    toggle.title = "Show Tools";
                }
                if (window.matchMedia("(max-width: 768px)").matches) { hideTools(); return; }
                var autoHide = setTimeout(function () {
                    autoHide = null;
                    if (drawer.classList.contains("is-open")) hideTools();
                }, 1000);
                function keepOpen() { if (autoHide) { clearTimeout(autoHide); autoHide = null; } }
                drawer.addEventListener("pointerdown", keepOpen, true);
                drawer.addEventListener("click", keepOpen, true);
                drawer.addEventListener("pointerenter", keepOpen);
                drawer.addEventListener("focusin", keepOpen);
            })();</script>';
            echo '<div class="learner-tools-content">' . $errorBanner . $pageOutput . '</div>';
        } else {
            echo $errorBanner . $pageOutput;
        }
        echo '</div>';
    }

    public function isActive($page) {
        $currentPage = $this->getPage();
        if ($currentPage === $this->default) {
            $currentPage = $this->roleHomePages[$this->getLearningRole()] ?? $currentPage;
        }
        return $currentPage === $page;
    }

    public function getAllowedPages() {
        return $this->allowed;
    }

    public function getNavItems() {
        $role = $this->getLearningRole();

        if ($role === 'admin') {
            $adminItems = $this->navConfig['admin'];
            $excludedPages = [
                'instructor/instructor-home',
                'instructor/analytics',
                'instructor/manage-learners',
                'instructor/progress-dashboard',
                'instructor/learner-timeline',
                'instructor/notification',
                'instructor/gradebook',
                'instructor/calendar',
                'instructor/training-requests',
            ];
            $instructorItems = array_filter($this->navConfig['instructor'], function ($item) use ($excludedPages) {
                return !in_array($item['page'], $excludedPages);
            });
            return array_merge($adminItems, array_values($instructorItems));
        }

        return $this->navConfig[$role] ?? $this->navConfig['learner'];
    }

    public function renderNav() {
        foreach ($this->getNavItems() as $index => $item) {
            $this->renderLink($item, $index + 1);
        }
    }

    private function renderLink($item, $shortcutNumber = null) {
        $label = $item['label'];
        $page = $item['page'];
        $url = in_array($page, $this->allowed, true) ? '?page=' . urlencode($page) : '#';
        $class = $this->isActive($page) ? 'active-menu-link' : 'menu-link';
        $shortcutAttr = $shortcutNumber !== null ? ' data-shortcut="' . $shortcutNumber . '"' : '';
        echo "<li><a href=\"{$url}\" data-page=\"{$page}\"{$shortcutAttr} class=\"{$class}\">{$label}</a></li>";
    }
}