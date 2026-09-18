<?php
/**
 * Learning module test harness — page routing, role rules and back links.
 *
 * Plain PHP with no dependencies, so it runs wherever the module runs:
 *
 *     php modules/learning/tests/run-tests.php
 *
 * Exits 0 when everything passes, 1 when something fails. It covers the rules
 * that break silently in this codebase:
 *
 *   1. which pages a role may open (Page::getPage)
 *   2. how a ?back= value is turned into a destination (BackLink)
 *   3. static consistency — every back= value, anchor default, nav entry and
 *      BackLink destination has to point at a page file that really exists
 *   4. the deployment base path (AppBase) — derived correctly for a subdirectory,
 *      a renamed folder, the document root and a CLI run, and never hardcoded
 */

$moduleRoot = dirname(__DIR__);
$pagesDir = $moduleRoot . '/pages';

// error_log() is used by the router for refused pages; keep the run quiet.
ini_set('error_log', DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null');

// Start the session before any output so the router's session_start() is a no-op.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $moduleRoot . '/classes/Page.php';

/* -------------------------------------------------------------------------
 * Test helpers
 * ---------------------------------------------------------------------- */

$passed = 0;
$failed = 0;
$failures = [];

function section(string $name): void
{
    echo "\n" . $name . "\n" . str_repeat('-', min(78, strlen($name))) . "\n";
}

function check(string $label, bool $ok, string $detail = ''): void
{
    global $passed, $failed, $failures;

    if ($ok) {
        $passed++;
        echo '  ok   ' . $label . "\n";

        return;
    }

    $failed++;
    $failures[] = $label . ($detail !== '' ? ' — ' . $detail : '');
    echo '  FAIL ' . $label . ($detail !== '' ? ' — ' . $detail : '') . "\n";
}

function same(string $label, $expected, $actual): void
{
    check($label, $expected === $actual, 'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
}

/** Point the next router call at a role and a query string. */
function request(string $role, ?string $page, array $extra = []): void
{
    $_SESSION['learning_role'] = $role;
    $_GET = $extra;
    if ($page !== null) {
        $_GET['page'] = $page;
    }
}

/** Every routable page slug in the module (AJAX endpoints included — they are discoverable). */
function pageSlugs(string $pagesDir): array
{
    $slugs = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($pagesDir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($pagesDir) + 1));
        if (strpos($relative, 'rainz/') === 0 || strpos($relative, 'rainz\\') === 0) {
            continue;
        }
        $slugs[] = substr($relative, 0, -4);
    }

    sort($slugs);

    return $slugs;
}

/** Source files to scan, excluding docs, vendored assets and this harness. */
function sourceFiles(string $moduleRoot, array $extensions = ['php', 'js', 'html']): array
{
    $skip = ['rainz', 'assets', 'css', 'errors', 'tests'];
    $files = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($moduleRoot, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile() || !in_array($file->getExtension(), $extensions, true)) {
            continue;
        }
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($moduleRoot) + 1));
        foreach ($skip as $dir) {
            if (strpos($relative, $dir . '/') === 0) {
                continue 2;
            }
        }
        $files[$relative] = file_get_contents($file->getPathname());
    }

    ksort($files);

    return $files;
}

$pageController = new Page($pagesDir);
$slugs = pageSlugs($pagesDir);
$sources = sourceFiles($moduleRoot);

/* -------------------------------------------------------------------------
 * 1. Router — which pages a role may open
 * ---------------------------------------------------------------------- */

section('Page router — role rules');

$roleTrees = [
    'admin'      => ['admin/', 'instructor/', 'public/'],
    'instructor' => ['instructor/', 'public/'],
    'learner'    => ['learner/', 'public/'],
];

$wrong = [];
foreach ($roleTrees as $role => $prefixes) {
    foreach ($slugs as $slug) {
        $permitted = false;
        foreach ($prefixes as $prefix) {
            if (strpos($slug, $prefix) === 0) {
                $permitted = true;
                break;
            }
        }

        request($role, $slug);
        $got = $pageController->getPage();
        $expected = $permitted ? $slug : 'dashboard-overview';

        if ($got !== $expected) {
            $wrong[] = $role . ' ' . $slug . ' -> ' . $got;
        }
    }
}
check(
    'all ' . count($slugs) . ' pages open for their own role tree and fall back otherwise',
    $wrong === [],
    implode('; ', array_slice($wrong, 0, 4))
);

request('learner', 'learner/study');
same('own-tree page is served', 'learner/study', $pageController->getPage());

request('learner', 'admin/user');
same('another role\'s page falls back to the dashboard', 'dashboard-overview', $pageController->getPage());

request('admin', 'instructor/certificate');
same('admin reaches the instructor tree it borrows in its nav', 'instructor/certificate', $pageController->getPage());

request('learner', null);
same('no page requested serves the dashboard', 'dashboard-overview', $pageController->getPage());

request('learner', '../database/db');
same('path traversal does not resolve', 'dashboard-overview', $pageController->getPage());

request('learner', 'admin\\user');
same('backslash separators do not resolve', 'dashboard-overview', $pageController->getPage());

request('learner', 'LEARNER/STUDY');
same('page names are matched exactly, not case-insensitively', 'dashboard-overview', $pageController->getPage());

$_SESSION['learning_role'] = 'learner';
$_GET = ['page' => ['learner/study']];
same('array page parameter does not resolve', 'dashboard-overview', $pageController->getPage());

/* -------------------------------------------------------------------------
 * 2. Back links — resolving a destination
 * ---------------------------------------------------------------------- */

section('BackLink — destination resolution');

same('no ?back= keeps the page default', 'learner/catalog', BackLink::resolve('learner/catalog', ''));
same('a known destination is honoured', 'learner/study', BackLink::resolve('learner/catalog', 'learner/study'));
same('a parent record is honoured', 'instructor/elearning-subpage/module?id=7', BackLink::resolve('instructor/elearning', 'instructor/elearning-subpage/module?id=7'));
same('case and padding are normalised', 'learner/study', BackLink::resolve('learner/catalog', '  LEARNER/Study '));
same('a page outside the map falls back', 'learner/catalog', BackLink::resolve('learner/catalog', 'learner/notes'));
same('a staff page is a valid destination', 'admin/user', BackLink::resolve('learner/catalog', 'admin/user'));
same('traversal falls back', 'learner/catalog', BackLink::resolve('learner/catalog', '../../database/db'));
same('an extra parameter is refused', 'learner/catalog', BackLink::resolve('learner/catalog', 'learner/study?evil=1'));
same('a non-numeric id is refused', 'instructor/elearning', BackLink::resolve('instructor/elearning', 'instructor/elearning-subpage/module?id=x'));
same('an array value falls back', 'learner/catalog', BackLink::resolve('learner/catalog', ['learner/study']));
same('the whitelist answers for a plain page', true, BackLink::isKnown('learner/study'));
same('the whitelist answers for a record', true, BackLink::isKnown('learner/study-subpage/course?id=3'));
same('the whitelist refuses an unknown page', false, BackLink::isKnown('admin/user-subpage/learner'));
same('the whitelist refuses junk', false, BackLink::isKnown('learner/study?<script>'));

same('a page label reads as a back link', 'Back to Skill Gap', BackLink::label('learner/skill-gap'));
same('a record label reads as a back link', 'Back to Module', BackLink::label('instructor/elearning-subpage/module?id=7'));
same('an unknown page still produces a label', 'Back', BackLink::label('learner/nope'));

same('url encodes the page path', 'index.php?page=learner%2Fstudy', BackLink::url('learner/study'));
same('url carries the record id', 'index.php?page=instructor%2Felearning-subpage%2Fmodule&id=7', BackLink::url('instructor/elearning-subpage/module?id=7'));

$anchor = BackLink::anchor('learner/study', 'class="back"');
check(
    'anchor renders a complete link',
    strpos($anchor, 'href="index.php?page=learner%2Fstudy"') !== false
        && strpos($anchor, 'class="back"') !== false
        && strpos($anchor, '>') !== false
        && substr($anchor, -4) === '</a>',
    $anchor
);
check('anchor label comes from the map', strpos($anchor, 'Back to Study') !== false, $anchor);
check('anchor content is escaped', strpos(BackLink::anchor('learner/study'), '<script') === false);

/* -------------------------------------------------------------------------
 * 3. Static consistency across the module
 * ---------------------------------------------------------------------- */

section('Static consistency');

// 3a. every ?back= value emitted by a linker must be a known destination
$badBack = [];
foreach ($sources as $file => $contents) {
    if (!preg_match_all('/back=([A-Za-z0-9_\-_.\/]+)/', $contents, $matches)) {
        continue;
    }
    foreach ($matches[1] as $value) {
        $value = rtrim($value, '.');
        if (!BackLink::isKnown($value)) {
            $badBack[] = $file . ' -> ' . $value;
        }
    }
}
check('every back= value is a whitelisted destination', $badBack === [], implode('; ', array_slice($badBack, 0, 5)));

// 3b. every BackLink::anchor()/resolve() default must be a known destination
$badDefault = [];
foreach ($sources as $file => $contents) {
    if (!preg_match_all('/BackLink::(?:anchor|resolve)\(\s*\'([^\']+)\'/', $contents, $matches)) {
        continue;
    }
    foreach ($matches[1] as $value) {
        if (!BackLink::isKnown($value)) {
            $badDefault[] = $file . ' -> ' . $value;
        }
    }
}
check('every BackLink default is a whitelisted destination', $badDefault === [], implode('; ', array_slice($badDefault, 0, 5)));

// 3c. every destination must be a page that exists
$missingDestination = [];
foreach (array_keys(BackLink::destinations()) as $destination) {
    if (!file_exists($pagesDir . '/' . $destination . '.php')) {
        $missingDestination[] = $destination;
    }
}
check('every BackLink destination is a real page', $missingDestination === [], implode(', ', $missingDestination));

// 3d. every page linked anywhere must exist
$missingPage = [];
foreach ($sources as $file => $contents) {
    if (!preg_match_all('/(?:page=|page\'\]\s*=\s*\'|page\'=>\s*\')([A-Za-z0-9_\-]+\/[A-Za-z0-9_\-.\/]+)/', $contents, $matches)) {
        continue;
    }
    foreach ($matches[1] as $slug) {
        $slug = rtrim($slug, './');

        // A composed path such as page=learner/catalog-subpage/{$type} is captured
        // only up to its first dynamic character, so skip it as a directory prefix.
        foreach ($slugs as $known) {
            if (strpos($known, $slug . '/') === 0) {
                continue 2;
            }
        }
        if (preg_match('/\.(php|js|css|png|jpg|pdf)$/', $slug)) {
            continue; // a file fetched by path, not a routed page
        }
        if (!in_array($slug, $slugs, true)) {
            $missingPage[] = $file . ' -> ' . $slug;
        }
    }
}
check('every linked page exists', $missingPage === [], implode('; ', array_slice($missingPage, 0, 5)));

// 3e. every nav entry must exist and be openable by the role that shows it
$navSource = $sources['classes/Page.php'] ?? '';
$badNav = [];
$navByRole = [];
if (preg_match_all("/'([a-z]+)'\s*=>\s*\[(.*?)\n\s*\],/s", $navSource, $roleBlocks, PREG_SET_ORDER)) {
    foreach ($roleBlocks as $block) {
        $role = $block[1];
        if (!isset($roleTrees[$role])) {
            continue; // not a nav config block
        }
        if (!preg_match_all("/'page'\s*=>\s*'([^']+)'/", $block[2], $links)) {
            continue;
        }
        foreach ($links[1] as $slug) {
            $navByRole[$role][] = $slug;

            if (!file_exists($pagesDir . '/' . $slug . '.php')) {
                $badNav[] = $role . ' nav -> missing page ' . $slug;
                continue;
            }
            $permitted = false;
            foreach ($roleTrees[$role] as $prefix) {
                if (strpos($slug, $prefix) === 0) {
                    $permitted = true;
                    break;
                }
            }
            if (!$permitted) {
                $badNav[] = $role . ' nav -> ' . $slug . ' outside its own tree';
            }
        }
    }
}
check('every nav entry exists and is inside its role tree', $badNav === [] && $navSource !== '', implode('; ', $badNav));

// 3f. the learned admin role also gets the instructor nav, so every instructor nav
// entry has to stay open to admin — checked against the nav config, not a sample.
$adminNavInstructorEntries = [];
foreach ($navByRole['instructor'] ?? [] as $slug) {
    request('admin', $slug);
    if ($pageController->getPage() !== $slug) {
        $adminNavInstructorEntries[] = $slug;
    }
}
check('instructor pages shown in the admin nav stay open to admin', $adminNavInstructorEntries === [], implode(', ', $adminNavInstructorEntries));

/* -------------------------------------------------------------------------
 * 4. Application base path
 * ---------------------------------------------------------------------- */

section('Application base — derived, never hardcoded');

require_once dirname($moduleRoot, 2) . '/includes/app-base.php';

/**
 * Run the base derivation against a simulated request. AppBase caches what it
 * derives, so the cache is cleared on the way in and on the way out.
 */
function withServer(array $server, callable $fn)
{
    $saved = $_SERVER;
    $cache = new ReflectionProperty(AppBase::class, 'webPath');
    $cache->setValue(null, null);
    $_SERVER = $server;

    try {
        return $fn();
    } finally {
        $_SERVER = $saved;
        $cache->setValue(null, null);
    }
}

same(
    'a CLI run has no web path',
    '',
    withServer([], function () { return AppBase::webPath(); })
);
same(
    'a CLI run still resolves a root-relative path',
    '/modules/learning/index.php',
    withServer([], function () { return AppBase::urlFor('modules/learning/index.php'); })
);
same(
    'the folder name comes from the request',
    '/hrms-capstone',
    withServer(['SCRIPT_NAME' => '/hrms-capstone/modules/learning/index.php'], function () { return AppBase::webPath(); })
);
same(
    'a renamed folder needs no code change',
    '/learning-dev',
    withServer(['SCRIPT_NAME' => '/learning-dev/modules/learning/index.php'], function () { return AppBase::webPath(); })
);
same(
    'an app at the document root has an empty base',
    '',
    withServer(['SCRIPT_NAME' => '/modules/learning/index.php'], function () { return AppBase::webPath(); })
);
same(
    'document root wins when it mirrors the filesystem',
    '/' . basename(AppBase::root()),
    withServer(
        ['DOCUMENT_ROOT' => dirname(AppBase::root()), 'SCRIPT_NAME' => '/ignored/modules/learning/index.php'],
        function () { return AppBase::webPath(); }
    )
);
same(
    'links are root-relative through the base',
    '/hrms-capstone/auth/logout.php',
    withServer(['SCRIPT_NAME' => '/hrms-capstone/modules/learning/index.php'], function () { return AppBase::pathFor('auth/logout.php'); })
);
same(
    'absolute URLs carry host and base',
    'http://localhost/hrms-capstone',
    withServer(
        ['SCRIPT_NAME' => '/hrms-capstone/modules/learning/index.php', 'HTTP_HOST' => 'localhost'],
        function () { return AppBase::url(); }
    )
);
same(
    'https is detected for share links',
    'https://example.test/base/modules/learning/index.php?page=learner/study',
    withServer(
        [
            'SCRIPT_NAME' => '/base/modules/learning/index.php',
            'HTTP_HOST' => 'example.test',
            'HTTPS' => 'on',
        ],
        function () { return AppBase::urlFor('modules/learning/index.php') . '?page=learner/study'; }
    )
);

// 4b. no file may name a deployment folder again
$repoRoot = dirname($moduleRoot, 2);
$hardcoded = [];
$scan = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($repoRoot, FilesystemIterator::SKIP_DOTS),
        function (SplFileInfo $file) {
            if (!$file->isDir()) {
                return true;
            }
            // rainz/ holds historical SQL snapshots; tests/ names a folder on purpose
            // (to prove the derivation ignores the name); app-base.php documents the rule.
            return !in_array($file->getFilename(), ['rainz', 'tests', '.git', 'node_modules', 'vendor'], true);
        }
    )
);
foreach ($scan as $file) {
    if (!$file->isFile() || !in_array($file->getExtension(), ['php', 'js'], true)) {
        continue;
    }
    $path = str_replace('\\', '/', $file->getPathname());
    if (substr($path, -strlen('includes/app-base.php')) === 'includes/app-base.php') {
        continue;
    }
    $contents = file_get_contents($path);
    if (preg_match('#(["\']/(?:itsar|hrms-capstone)[a-z-]*/|htdocs/(?:itsar|hrms-capstone))#i', $contents, $m)) {
        $hardcoded[] = str_replace($repoRoot . DIRECTORY_SEPARATOR, '', $file->getPathname()) . ' -> ' . $m[1];
    }
}
check('no hardcoded deployment base remains', $hardcoded === [], implode('; ', array_slice($hardcoded, 0, 5)));

/* -------------------------------------------------------------------------
 * Summary
 * ---------------------------------------------------------------------- */

echo "\n" . str_repeat('=', 78) . "\n";
if ($failed === 0) {
    echo 'PASS — ' . $passed . " checks\n";
    exit(0);
}

echo 'FAIL — ' . $failed . ' of ' . ($passed + $failed) . " checks failed:\n";
foreach ($failures as $failure) {
    echo '  - ' . $failure . "\n";
}
exit(1);
