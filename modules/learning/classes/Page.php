<?php
require_once __DIR__ . '/dberror.php';
require_once __DIR__ . '/backlink.php';

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
            ['label' => 'Learning Paths', 'page' => 'admin/learning-path'],
            ['label' => 'Grade Book', 'page' => 'admin/gradebook'],
            ['label' => 'Knowledge Transfer', 'page' => 'admin/knowledge-transfer'],
            ['label' => 'Analytics', 'page' => 'admin/analytics'],
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
            ['label' => 'My Learning Path', 'page' => 'learner/my-learning-path'],
            ['label' => 'Skill Gap', 'page' => 'learner/skill-gap'],
            ['label' => 'My Skills', 'page' => 'learner/study-subpage/skill'],
            ['label' => 'Results', 'page' => 'learner/result'],
            ['label' => 'Analytics', 'page' => 'learner/analytics'],
            ['label' => 'Calendar', 'page' => 'learner/calendar'],
            ['label' => 'Notes', 'page' => 'learner/notes'],
            ['label' => 'Knowledge Transfer', 'page' => 'learner/knowledge-transfer'],
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
    public function isPageAllowedForRole($page) {
        $role = $this->getLearningRole();
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

        if (isset($_SESSION['employee_id'])) {
            $role = $this->resolveLearningRoleFromDb((int) $_SESSION['employee_id']);
            $_SESSION['learning_role'] = $role;
            return $role;
        }

        $_SESSION['learning_role'] = 'learner';
        return 'learner';
    }

    private function resolveLearningRoleFromDb(int $employeeId): string {
        try {
            $database = new Database();
            $pdo = $database->getConnection();

            $stmt = $pdo->prepare("
                SELECT e.employee_id, e.role_id, e.department_id, r.role_name, d.department_name
                FROM em_employees e
                LEFT JOIN em_roles r ON r.role_id = e.role_id
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                WHERE e.employee_id = :eid AND e.employment_status = 'Active'
                LIMIT 1
            ");
            $stmt->execute([':eid' => $employeeId]);
            $emp = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$emp) {
                return 'learner';
            }

            $roleName = strtolower($emp['role_name'] ?? '');
            $deptName = strtolower($emp['department_name'] ?? '');

            // System administrators always get admin access
            if ($emp['role_id'] == 1 || stripos($roleName, 'system admin') !== false) {
                return 'admin';
            }

            // L&D admins - specific employee IDs that should have admin access regardless of role
            // These are legacy designations for L&D module administration
            $ldAdminEmployeeIds = [35, 1018];
            if (in_array($employeeId, $ldAdminEmployeeIds, true)) {
                return 'admin';
            }

            // Learning and Development staff / instructors
            if ($emp['role_id'] == 7 || stripos($roleName, 'learning') !== false || stripos($deptName, 'instructor') !== false) {
                return 'instructor';
            }

            return 'learner';
        } catch (Throwable $e) {
            error_log('[learning] Role resolution failed: ' . $e->getMessage());
            return 'learner';
        }
    }

    public function getDashboardFile() {
        $role = $this->getLearningRole();
        $roleFile = $this->rolePages[$role] ?? $this->rolePages['learner'];
        return $this->pagesDir . '/' . $roleFile;
    }

    public function render() {
        $page = $this->getPage();
        if ($page === $this->default) {
            $file = $this->getDashboardFile();
        } else {
            $file = $this->pagesDir . '/' . $page . '.php';
        }
        echo '<div class="page-content" data-page="' . htmlspecialchars($page) . '">';

        // Buffer the page so a database failure captured while rendering it can be
        // surfaced as a visible notice instead of hiding behind an empty result set.
        ob_start();
        if (file_exists($file)) {
            include $file;
        } else {
            include $this->getDashboardFile();
        }
        $pageOutput = ob_get_clean();

        if (class_exists('DbError') && DbError::hasErrors()) {
            echo DbError::renderBanner();
        }

        echo $pageOutput;
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