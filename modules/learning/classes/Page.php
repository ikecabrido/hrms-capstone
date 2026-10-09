<?php
require_once __DIR__ . '/dberror.php';
require_once __DIR__ . '/backlink.php';
require_once __DIR__ . '/LearningRole.php';

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
            ['label' => 'Workforce Inbox', 'page' => 'admin/workforce-integration'],
            ['label' => 'Calendar', 'page' => 'admin/calendar'],
            ['label' => 'Moderation', 'page' => 'admin/moderation'],
            ['label' => 'Notifications', 'page' => 'admin/notification'],
            ['label' => 'Settings', 'page' => 'admin/settings'],
            ['label' => 'Profile', 'page' => 'admin/profile'],
        ],
        'instructor' => [
            ['label' => 'Home', 'page' => 'instructor/instructor-home'],
            ['label' => 'E-Learning', 'page' => 'instructor/elearning'],
            ['label' => 'Progress', 'page' => 'instructor/progress-dashboard'],
            ['label' => 'Learners', 'page' => 'instructor/manage-learners'],
            ['label' => 'Analytics', 'page' => 'instructor/analytics'],
            ['label' => 'Grade Book', 'page' => 'instructor/gradebook'],
            ['label' => 'Calendar', 'page' => 'instructor/calendar'],
            ['label' => 'Timeline', 'page' => 'instructor/learner-timeline'],
            ['label' => 'Trainings', 'page' => 'instructor/training'],
            ['label' => 'Training Requests', 'page' => 'instructor/training-requests'],
            ['label' => 'Certificates', 'page' => 'instructor/certificate'],
            ['label' => 'Skill Gaps', 'page' => 'instructor/instructor-skill-gap'],
            ['label' => 'Notification', 'page' => 'instructor/notification'],
            ['label' => 'Profile', 'page' => 'instructor/profile'],
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
            ['label' => 'Profile', 'page' => 'learner/profile'],
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
            $this->allowed[] = $relative;
        }
    }

    public function getPage() {
        $requested = isset($_GET['page']) && is_string($_GET['page']) ? $_GET['page'] : '';

        if ($requested !== '' && in_array($requested, $this->allowed, true)) {
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
        echo '<style>
            .page-content.has-learner-tools {
                display: grid;
                grid-template-columns: 220px minmax(0, 1fr);
                align-items: start;
                gap: 1rem;
            }
            .page-content.has-learner-tools:has(> .learner-tools-shell:not(.is-open)) {
                grid-template-columns: 34px minmax(0, 1fr);
            }
            @media (min-width: 769px) {
                body:has(.sidebar:not(.hidden)) .page-content.has-learner-tools {
                    width: calc(100% + 3rem);
                    max-width: none;
                    margin-left: -3rem;
                }
                body:has(.sidebar.hidden) .page-content.has-learner-tools {
                    width: 100vw;
                    max-width: none;
                    margin-left: calc(50% - 50vw);
                }
            }
            .learner-tools-shell {
                position: sticky;
                top: calc(var(--header-height, 60px) + 1rem);
                width: 220px;
                pointer-events: none;
                transition: width 0.2s ease;
            }
            .learner-tools-shell:not(.is-open) {
                width: 34px;
            }
            .learner-tools-panel {
                position: relative;
                width: 100%;
                max-height: calc(100vh - var(--header-height, 60px) - 2rem);
                overflow-y: auto;
                padding: 0.9rem;
                border: 1px solid rgba(32, 0, 130, 0.08);
                background: var(--surface, #fff);
                border-radius: 10px;
                box-shadow: 0 8px 18px rgba(32, 0, 130, 0.12);
                pointer-events: auto;
                z-index: 1;
                transition: opacity 0.2s ease, transform 0.2s ease;
            }
            .learner-tools-shell:not(.is-open) .learner-tools-panel {
                display: none;
                opacity: 0;
                pointer-events: none;
            }
            .learner-tools-toggle {
                position: absolute;
                top: 0;
                left: 100%;
                display: flex;
                width: 34px;
                height: 40px;
                align-items: center;
                justify-content: center;
                border: 1px solid var(--border, #d8dbe2);
                border-left: 0;
                border-radius: 0 8px 8px 0;
                background: var(--surface, #fff);
                color: var(--primary, #2f2a71);
                box-shadow: 3px 3px 12px rgba(32, 0, 130, 0.1);
                cursor: pointer;
                pointer-events: auto;
                z-index: 2;
                transition: left 0.2s ease, background 0.2s ease;
            }
            .learner-tools-shell:not(.is-open) .learner-tools-toggle {
                left: 0;
                border-left: 1px solid var(--border, #d8dbe2);
                border-radius: 0 8px 8px 0;
            }
            .learner-tools-toggle:hover {
                background: var(--bg-subtle, #f4f5f8);
            }
            .learner-tools-toggle i {
                transition: transform 0.2s ease;
            }
            .learner-tools-shell:not(.is-open) .learner-tools-toggle i {
                transform: rotate(180deg);
            }
            .learner-tools-panel-title {
                margin: 0 0 0.75rem;
                color: var(--muted, #6b7280);
                font-size: 0.72rem;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }
            .learner-tools-panel nav {
                display: grid;
                gap: 0.35rem;
            }
            .learner-tools-panel-link {
                display: flex;
                align-items: center;
                gap: 0.6rem;
                padding: 0.7rem 0.8rem;
                border-radius: 10px;
                text-decoration: none;
                color: var(--text, #1f2937);
                background: rgba(255,255,255,0.6);
                border: 1px solid transparent;
                font-size: 0.84rem;
                font-weight: 700;
            }
            .learner-tools-panel-link:hover {
                background: rgba(32, 0, 130, 0.06);
                border-color: rgba(32, 0, 130, 0.08);
            }
            .learner-tools-panel-link.active {
                background: var(--primary, #2f2a71);
                color: #fff;
            }
            .learner-tools-backdrop {
                display: none;
            }
            @media (max-width: 768px) {
                .page-content.has-learner-tools,
                .page-content.has-learner-tools:has(> .learner-tools-shell:not(.is-open)) {
                    display: block;
                }
                .learner-tools-shell {
                    position: fixed;
                    top: calc(var(--header-height, 60px) + 0.5rem);
                    left: 0;
                    width: min(280px, calc(100vw - 48px));
                    z-index: 1001;
                    isolation: isolate;
                    transition: none;
                }
                .learner-tools-shell:not(.is-open) {
                    width: min(280px, calc(100vw - 48px));
                }
                .learner-tools-panel {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    max-height: calc(100vh - var(--header-height, 60px) - 1rem);
                    box-sizing: border-box;
                }
                .learner-tools-shell:not(.is-open) .learner-tools-panel {
                    display: block;
                    opacity: 0;
                    visibility: hidden;
                    transform: translateX(-100%);
                }
                .learner-tools-shell.is-open .learner-tools-panel {
                    opacity: 1;
                    visibility: visible;
                    transform: translateX(0);
                }
                .learner-tools-toggle {
                    left: 100%;
                }
                .learner-tools-shell:not(.is-open) .learner-tools-toggle {
                    left: 0;
                }
                .learner-tools-backdrop {
                    position: fixed;
                    inset: var(--header-height, 60px) 0 0;
                    display: block;
                    border: 0;
                    padding: 0;
                    background: rgba(15, 23, 42, 0.32);
                    opacity: 0;
                    visibility: hidden;
                    pointer-events: none;
                    transition: opacity 0.2s ease, visibility 0.2s ease;
                    z-index: 0;
                }
                .learner-tools-shell.is-open .learner-tools-backdrop {
                    opacity: 1;
                    visibility: visible;
                    pointer-events: auto;
                }
            }
        </style>';
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
            echo '<div class="learner-tools-panel-title">Tools</div>';
            echo '<nav>';
            foreach ($learnerToolsPages as $toolPage) {
                $toolLabel = [
                    'learner/my-learning-path' => 'My Learning Path',
                    'learner/skill-gap' => 'Skill Gap',
                    'learner/study-subpage/skill' => 'My Skills',
                    'learner/knowledge-transfer' => 'Knowledge Transfer',
                ][$toolPage] ?? ucfirst(str_replace('-', ' ', basename($toolPage)));
                $active = $page === $toolPage ? ' active' : '';
                echo '<a href="?page=' . urlencode($toolPage) . '" data-page="' . htmlspecialchars($toolPage, ENT_QUOTES, 'UTF-8') . '" class="learner-tools-panel-link' . $active . '"' . ($page === $toolPage ? ' aria-current="page"' : '') . '>' . htmlspecialchars($toolLabel, ENT_QUOTES, 'UTF-8') . '</a>';
            }
            echo '</nav>';
            echo '</aside>';
            echo '</div>';
            echo '<script>(function(){var drawer=document.getElementById("learner-tools-drawer");var toggle=drawer&&drawer.querySelector(".learner-tools-toggle");if(drawer&&toggle&&window.matchMedia("(max-width: 768px)").matches){drawer.classList.remove("is-open");toggle.setAttribute("aria-expanded","false");toggle.setAttribute("aria-label","Show Tools");toggle.title="Show Tools";}})();</script>';
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
                'instructor/profile',
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