<?php
/**
 * Admin AI Assistant shell.
 *
 * This page is intentionally read-only in P3. It establishes the admin
 * navigation, section structure, setup readiness, and empty states before the
 * secure configuration and generation endpoints arrive.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['employee_id'])) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

require_once dirname(__DIR__, 4) . '/database/db.php';
require_once __DIR__ . '/../../classes/LearningRole.php';
require_once __DIR__ . '/../../classes/CSRF.php';
require_once __DIR__ . '/../../classes/AIConfig.php';
require_once __DIR__ . '/../../classes/AISecretBox.php';
require_once __DIR__ . '/../../classes/AIAudit.php';
require_once __DIR__ . '/../../classes/AIAdminSettings.php';
require_once __DIR__ . '/../../classes/AIApiKeyStore.php';
require_once __DIR__ . '/../../classes/AIModelCatalog.php';
require_once __DIR__ . '/../../classes/AIAnalytics.php';
require_once __DIR__ . '/../../classes/ProfileLink.php';

try {
    $aiPdo = (new Database())->getConnection();
    if (LearningRole::forEmployee($aiPdo, (int) $_SESSION['employee_id']) !== 'admin') {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
} catch (Throwable $e) {
    http_response_code(503);
    echo 'AI Assistant is unavailable.';
    exit;
}

$aiSchema = [
    'ready' => false,
    'required' => AIConfig::REQUIRED_TABLES,
    'missing' => AIConfig::REQUIRED_TABLES,
];
try {
    $aiSchema = AIConfig::schemaStatus($aiPdo);
} catch (Throwable $e) {
    // Keep the page usable and show the setup state below.
}

$aiStats = [
    'jobs' => 0,
    'ready' => 0,
    'applied' => 0,
    'cost' => 0.0,
];
$aiActiveKey = null;
$aiDefaultModel = null;
$aiFallbackModel = null;
$aiModelCount = 0;
$aiRecentJobs = [];
$aiJobs = [];
$aiOwnerOptions = [];
$aiEnabledModels = [];
$aiSettings = AIConfig::settingDefaults();
$aiModelCostMode = AIConfig::DEFAULT_MODEL_COST_MODE;
$aiModelCostModeOptions = AIAdminSettings::definitions()['model_cost_mode']['options'] ?? [];
$aiKeys = [];
$aiModels = [];
$aiAuditEntries = [];
$aiMasterKey = AISecretBox::status();
$aiAnalyticsDays = 30;
$aiRequestedRange = isset($_GET['ai_range']) ? (string) $_GET['ai_range'] : '30';
if (in_array($aiRequestedRange, ['7', '30', '90'], true)) {
    $aiAnalyticsDays = (int) $aiRequestedRange;
}
$aiFrom = isset($_GET['ai_from']) ? (string) $_GET['ai_from'] : '';
if (!in_array($aiFrom, ['course', 'learning-path'], true)) {
    $aiFrom = '';
}
$aiAnalytics = [
    'days' => $aiAnalyticsDays,
    'range_label' => 'Last ' . $aiAnalyticsDays . ' days',
    'jobs' => 0,
    'cost' => 0.0,
    'failures' => 0,
    'applied' => 0,
    'tokens' => 0,
    'daily' => [],
    'max_jobs' => 1,
    'max_cost' => 0.01,
    'models' => [],
    'statuses' => [],
    'recent_failures' => [],
];

if ($aiSchema['ready']) {
    try {
        $aiStats['jobs'] = (int) $aiPdo->query('SELECT COUNT(*) FROM ld_ai_course_job')->fetchColumn();
        $aiStats['ready'] = (int) $aiPdo->query("SELECT COUNT(*) FROM ld_ai_course_job WHERE status = 'ready'")->fetchColumn();
        $aiStats['applied'] = (int) $aiPdo->query("SELECT COUNT(*) FROM ld_ai_course_job WHERE status = 'applied'")->fetchColumn();
        $aiStats['cost'] = (float) ($aiPdo->query('SELECT COALESCE(SUM(estimated_cost_usd), 0) FROM ld_ai_course_job')->fetchColumn() ?: 0);

        $aiAudit = new AIAudit($aiPdo);
        $aiKeyStore = new AIApiKeyStore($aiPdo, null, $aiAudit);
        $aiModelStore = new AIModelCatalog($aiPdo, $aiAudit);
        $aiSettingsStore = new AIAdminSettings($aiPdo, $aiAudit);

        $aiActiveKey = $aiKeyStore->activeMetadata();
        $aiKeys = $aiKeyStore->listKeys();
        $aiModels = $aiModelStore->listAll();
        $aiEnabledModels = $aiModelStore->listEnabled();
        $aiOwnerOptions = $aiPdo->query(
            "SELECT e.employee_id,
                    CONCAT(e.first_name, ' ', e.last_name) AS name,
                    COALESCE(lr.learning_role, IF(e.role_id = 7, 'instructor', 'admin')) AS learning_role
               FROM em_employees e
               LEFT JOIN ld_user_role lr ON lr.employee_id = e.employee_id
              WHERE e.employment_status = 'Active'
                AND (e.role_id IN (1, 7) OR lr.learning_role IN ('admin', 'instructor'))
              ORDER BY e.first_name ASC, e.last_name ASC
              LIMIT 500"
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $aiSettings = $aiSettingsStore->getAll();
        $aiModelCostMode = AIConfig::normalizeModelCostMode(
            $aiSettings['model_cost_mode'] ?? AIConfig::DEFAULT_MODEL_COST_MODE
        );
        $aiAuditEntries = $aiAudit->recent(25);
        $aiModelModeWhere = AIConfig::modelModeWhere($aiModelCostMode);

        $aiDefaultModel = $aiPdo->query(
            "SELECT model_slug, display_name
               FROM ld_ai_model
              WHERE provider = 'openrouter' AND enabled = 1 AND is_default = 1
                AND {$aiModelModeWhere}
              ORDER BY id DESC
              LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC) ?: null;

        $aiFallbackModel = $aiPdo->query(
            "SELECT model_slug, display_name
               FROM ld_ai_model
              WHERE provider = 'openrouter' AND enabled = 1 AND is_fallback = 1
                AND {$aiModelModeWhere}
              ORDER BY id DESC
              LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC) ?: null;

        $aiModelCount = (int) $aiPdo->query(
            "SELECT COUNT(*) FROM ld_ai_model
              WHERE provider = 'openrouter' AND enabled = 1
                AND {$aiModelModeWhere}"
        )->fetchColumn();

        $aiJobs = $aiPdo->query(
            "SELECT j.id, j.status, j.model, j.estimated_cost_usd, j.created_at,
                    j.prompt_tokens, j.completion_tokens, j.total_steps, j.completed_steps,
                    j.learner_id, j.course_owner_id,
                    CONCAT(l.first_name, ' ', l.last_name) AS learner_name,
                    CONCAT(o.first_name, ' ', o.last_name) AS owner_name
               FROM ld_ai_course_job j
               LEFT JOIN em_employees l ON l.employee_id = j.learner_id
               LEFT JOIN em_employees o ON o.employee_id = j.course_owner_id
              ORDER BY j.id DESC
              LIMIT 50"
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $aiRecentJobs = array_slice($aiJobs, 0, 5);
        $aiAnalytics = (new AIAnalytics($aiPdo))->dashboard($aiAnalyticsDays);
    } catch (Throwable $e) {
        // A schema can be present while an optional query is not. Keep the
        // shell visible and let the setup checklist explain what is missing.
    }
}

$aiKeyTested = $aiActiveKey && (($aiActiveKey['last_test_status'] ?? '') === 'success');
$aiConfigured = $aiSchema['ready'] && $aiMasterKey['configured'] && $aiKeyTested && $aiDefaultModel;
$aiReadyCount = ($aiSchema['ready'] ? 1 : 0) + ($aiKeyTested ? 1 : 0) + ($aiDefaultModel ? 1 : 0);

if (!$aiSchema['ready']) {
    $aiHeroState = 'blocked';
    $aiHeroLabel = 'Database setup required';
} elseif (!$aiMasterKey['configured']) {
    $aiHeroState = 'blocked';
    $aiHeroLabel = 'Master key required';
} elseif (!$aiActiveKey) {
    $aiHeroState = 'blocked';
    $aiHeroLabel = 'API key required';
} elseif (!$aiKeyTested) {
    $aiHeroState = 'pending';
    $aiHeroLabel = 'Test the API key';
} elseif (!$aiDefaultModel) {
    $aiHeroState = 'pending';
    $aiHeroLabel = 'Choose a default model';
} else {
    $aiHeroState = 'ready';
    $aiHeroLabel = 'Ready to generate';
}

$aiKeyStepClass = !$aiActiveKey ? 'is-blocked' : ($aiKeyTested ? 'is-done' : 'is-pending');
$aiKeyStepIcon = $aiKeyTested ? 'fa-check' : ($aiActiveKey ? 'fa-vial' : 'fa-key');

$aiProfileUrl = static function (int $employeeId, string $targetRole = 'learner'): string {
    return $employeeId > 0 ? LearningProfileLink::url($employeeId, $targetRole) : '';
};

$aiJobStatusLabel = static function (string $status): string {
    return [
        'draft' => 'Draft',
        'generating' => 'Generating',
        'ready' => 'Ready for review',
        'applied' => 'Applied',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
    ][$status] ?? ucfirst($status);
};

$aiPricePerMillion = static function (mixed $price): string {
    return '$' . number_format((float) $price * 1000000, 2);
};

$aiMaskedFingerprint = static function (?string $fingerprint): string {
    $fingerprint = (string) $fingerprint;
    return $fingerprint !== '' ? substr($fingerprint, 0, 12) . '…' : '';
};

$aiCsrfToken = CSRF::getToken();
?>
<div class="module-content ai-assistant-page">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($aiCsrfToken) ?>">
    <section class="home-hero ai-hero">
        <div class="home-hero-main">
            <span class="ai-eyebrow"><i class="fas fa-wand-magic-sparkles"></i> Admin control center</span>
            <h1>AI Assistant</h1>
            <p>Build a trainee-targeted course from a reviewed outline, then apply it as a draft. Provider keys, models, prompts, and spend stay under administrator control.</p>
        </div>
        <div class="ai-hero-status ai-hero-status--<?= htmlspecialchars($aiHeroState, ENT_QUOTES) ?>" role="status">
            <span class="ai-status-dot" aria-hidden="true"></span>
            <?= htmlspecialchars($aiHeroLabel) ?>
        </div>
    </section>

    <?php if ($aiFrom === 'course'): ?>
        <div class="ai-origin-note" role="note">
            <i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i>
            <span>Opened from course authoring. Generate a draft here, then review it in the Course Structure Builder.</span>
            <a href="?page=instructor/elearning-subpage/course">Back to Add Course</a>
        </div>
    <?php elseif ($aiFrom === 'learning-path'): ?>
        <div class="ai-origin-note" role="note">
            <i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i>
            <span>Opened from learning path authoring. Generate draft courses here, then add the reviewed courses to your path.</span>
            <a href="?page=instructor/learning-path">Back to Learning Path</a>
        </div>
    <?php endif; ?>

    <section class="ai-readiness" aria-label="AI setup readiness">
        <div class="ai-readiness-head">
            <div>
                <span class="ai-kicker">Setup readiness</span>
                <h2>Three checks before the first course</h2>
            </div>
            <span class="ai-readiness-count">
                <?= $aiReadyCount ?>/3
            </span>
        </div>
        <ol class="ai-readiness-steps">
            <li class="<?= $aiSchema['ready'] ? 'is-done' : 'is-blocked' ?>">
                <span class="ai-step-index" aria-hidden="true">
                    <i class="fas <?= $aiSchema['ready'] ? 'fa-check' : 'fa-database' ?>"></i>
                </span>
                <div>
                    <strong>Database schema</strong>
                    <span><?= $aiSchema['ready'] ? 'Seven AI tables are ready.' : 'The AI migration has not been applied.' ?></span>
                </div>
            </li>
            <li class="<?= $aiKeyStepClass ?>">
                <span class="ai-step-index" aria-hidden="true">
                    <i class="fas <?= $aiKeyStepIcon ?>"></i>
                </span>
                <div>
                    <strong>Provider key</strong>
                    <span>
                        <?php if ($aiActiveKey): ?>
                            <?= htmlspecialchars($aiActiveKey['label'] ?? 'OpenRouter key') ?> ending <?= htmlspecialchars($aiActiveKey['key_last4'] ?? '') ?>.
                            <?php if ($aiKeyTested): ?>
                                Tested successfully.
                            <?php elseif (($aiActiveKey['last_test_status'] ?? '') === 'failed'): ?>
                                The last test failed; add and test a replacement.
                            <?php else: ?>
                                Test it before generating.
                            <?php endif; ?>
                        <?php elseif (!$aiMasterKey['configured']): ?>
                            Configure the master key before adding a provider key.
                        <?php else: ?>
                            Add an encrypted OpenRouter key before generating.
                        <?php endif; ?>
                    </span>
                </div>
            </li>
            <li class="<?= $aiDefaultModel ? 'is-done' : 'is-pending' ?>">
                <span class="ai-step-index" aria-hidden="true">
                    <i class="fas <?= $aiDefaultModel ? 'fa-check' : 'fa-microchip' ?>"></i>
                </span>
                <div>
                    <strong>Default model</strong>
                    <span>
                        <?php if ($aiDefaultModel): ?>
                            <?= htmlspecialchars($aiDefaultModel['display_name'] ?: $aiDefaultModel['model_slug']) ?>
                        <?php else: ?>
                            Choose the model that will draft the course.
                        <?php endif; ?>
                    </span>
                </div>
            </li>
        </ol>
    </section>

    <div class="tab-container ai-tabs">
        <div class="tab-list ai-tab-list" aria-label="AI Assistant sections">
            <button type="button" class="tab-item active" data-tab="ai-overview"><i class="fas fa-chart-pie"></i> Overview</button>
            <button type="button" class="tab-item" data-tab="ai-generate"><i class="fas fa-wand-magic-sparkles"></i> Generate Course</button>
            <button type="button" class="tab-item" data-tab="ai-jobs"><i class="fas fa-clock-rotate-left"></i> Jobs</button>
            <button type="button" class="tab-item" data-tab="ai-settings"><i class="fas fa-sliders"></i> Settings</button>
            <button type="button" class="tab-item" data-tab="ai-keys"><i class="fas fa-key"></i> API Keys &amp; Models</button>
            <button type="button" class="tab-item" data-tab="ai-prompts"><i class="fas fa-file-lines"></i> Prompts</button>
            <button type="button" class="tab-item" data-tab="ai-audit"><i class="fas fa-shield-halved"></i> Audit Log</button>
        </div>

        <div class="tab-content active" id="ai-overview" data-tab="ai-overview">
            <div class="ai-stat-grid">
                <article class="ai-stat-card">
                    <span class="ai-stat-icon"><i class="fas fa-layer-group"></i></span>
                    <span class="ai-stat-label">Generation jobs</span>
                    <strong><?= number_format($aiStats['jobs']) ?></strong>
                    <span class="ai-stat-note">All admin requests</span>
                </article>
                <article class="ai-stat-card">
                    <span class="ai-stat-icon"><i class="fas fa-pen-to-square"></i></span>
                    <span class="ai-stat-label">Ready for review</span>
                    <strong><?= number_format($aiStats['ready']) ?></strong>
                    <span class="ai-stat-note">Drafts waiting on apply</span>
                </article>
                <article class="ai-stat-card">
                    <span class="ai-stat-icon"><i class="fas fa-circle-check"></i></span>
                    <span class="ai-stat-label">Applied courses</span>
                    <strong><?= number_format($aiStats['applied']) ?></strong>
                    <span class="ai-stat-note">Created as drafts</span>
                </article>
                <article class="ai-stat-card">
                    <span class="ai-stat-icon"><i class="fas fa-coins"></i></span>
                    <span class="ai-stat-label">Estimated spend</span>
                    <strong><?= $aiStats['cost'] > 0 ? '$' . htmlspecialchars(number_format($aiStats['cost'], 2)) : '$0.00' ?></strong>
                    <span class="ai-stat-note">Provider usage to date</span>
                </article>
            </div>

            <div class="ai-overview-grid">
                <section class="ai-panel">
                    <div class="ai-panel-head">
                        <div>
                            <span class="ai-kicker">First run</span>
                            <h2>Set up the assistant</h2>
                        </div>
                    </div>
                    <ol class="ai-checklist">
                        <li class="<?= $aiSchema['ready'] ? 'is-done' : '' ?>">
                            <span class="ai-check-mark"><i class="fas <?= $aiSchema['ready'] ? 'fa-check' : 'fa-minus' ?>"></i></span>
                            <div>
                                <strong>Apply the AI schema</strong>
                                <p><?= $aiSchema['ready'] ? 'The seven AI tables are present.' : 'Import the reviewed migration before adding keys.' ?></p>
                            </div>
                        </li>
                        <li class="<?= $aiKeyTested ? 'is-done' : '' ?>">
                            <span class="ai-check-mark"><i class="fas <?= $aiKeyTested ? 'fa-check' : 'fa-minus' ?>"></i></span>
                            <div>
                                <strong>Add and test an encrypted provider key</strong>
                                <p>
                                    <?php if ($aiKeyTested): ?>
                                        The active key passed its provider test.
                                    <?php elseif ($aiActiveKey): ?>
                                        The active key has not passed a test yet.
                                    <?php else: ?>
                                        The full key is never shown again after saving.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </li>
                        <li class="<?= $aiDefaultModel ? 'is-done' : '' ?>">
                            <span class="ai-check-mark"><i class="fas <?= $aiDefaultModel ? 'fa-check' : 'fa-minus' ?>"></i></span>
                            <div>
                                <strong>Choose a default model</strong>
                                <p><?= $aiDefaultModel ? 'Generation can use the selected model.' : 'Only enabled models can be selected for a job.' ?></p>
                            </div>
                        </li>
                    </ol>
                    <div class="ai-panel-actions">
                        <button type="button" class="ai-btn ai-btn--primary" data-ai-tab-target="ai-settings">
                            Review settings <i class="fas fa-arrow-right"></i>
                        </button>
                        <button type="button" class="ai-btn ai-btn--ghost" data-ai-tab-target="ai-keys">
                            Open keys &amp; models
                        </button>
                    </div>
                </section>

                <section class="ai-panel ai-panel--soft">
                    <div class="ai-panel-head">
                        <div>
                            <span class="ai-kicker">Guardrails</span>
                            <h2>What the assistant will do</h2>
                        </div>
                    </div>
                    <ul class="ai-guardrail-list">
                        <li><i class="fas fa-file-circle-check"></i> Create a new draft course only.</li>
                        <li><i class="fas fa-user-slash"></i> Never enroll a trainee automatically.</li>
                        <li><i class="fas fa-code"></i> Render lessons through safe Markdown.</li>
                        <li><i class="fas fa-lock"></i> Keep provider keys server-side only.</li>
                    </ul>
                    <?php if (!$aiConfigured): ?>
                        <div class="ai-notice ai-notice--info">
                            <i class="fas fa-circle-info"></i>
                            <p>Generation stays disabled until the schema, a tested key, and a default model are ready.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <section class="ai-panel">
                <div class="ai-panel-head">
                    <div>
                        <span class="ai-kicker">Recent activity</span>
                        <h2>Latest generation jobs</h2>
                    </div>
                    <button type="button" class="ai-text-action" data-ai-tab-target="ai-jobs">View all jobs <i class="fas fa-arrow-right"></i></button>
                </div>
                <?php if ($aiRecentJobs): ?>
                    <div class="ai-table-wrap">
                        <table class="ai-table">
                            <thead>
                                <tr>
                                    <th scope="col">Job</th>
                                    <th scope="col">Trainee</th>
                                    <th scope="col">Owner</th>
                                    <th scope="col">Model</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aiRecentJobs as $job): ?>
                                    <tr>
                                        <td>#<?= (int) $job['id'] ?></td>
                                        <td>
                                            <?php if (!empty($job['learner_id']) && $job['learner_name']): ?>
                                                <a href="<?= htmlspecialchars($aiProfileUrl((int) $job['learner_id'], 'learner'), ENT_QUOTES) ?>"><?= htmlspecialchars($job['learner_name']) ?></a>
                                            <?php else: ?>
                                                &mdash;
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($job['course_owner_id']) && $job['owner_name']): ?>
                                                <a href="<?= htmlspecialchars($aiProfileUrl((int) $job['course_owner_id'], 'instructor'), ENT_QUOTES) ?>"><?= htmlspecialchars($job['owner_name']) ?></a>
                                            <?php else: ?>
                                                &mdash;
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($job['model']) ?></td>
                                        <td><span class="ai-status ai-status--<?= htmlspecialchars($job['status'], ENT_QUOTES) ?>"><?= htmlspecialchars($aiJobStatusLabel($job['status'])) ?></span></td>
                                        <td>$<?= htmlspecialchars(number_format((float) $job['estimated_cost_usd'], 2)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="ai-empty">
                        <span class="ai-empty-icon"><i class="fas fa-inbox"></i></span>
                        <h3>No generation jobs yet</h3>
                        <p>The first request will appear here after the provider key and default model are configured.</p>
                    </div>
                <?php endif; ?>
            </section>

            <section class="ai-panel ai-analytics-panel">
                <div class="ai-panel-head ai-panel-head--wrap">
                    <div>
                        <span class="ai-kicker">Usage</span>
                        <h2>Analytics</h2>
                        <p class="ai-panel-sub"><?= htmlspecialchars($aiAnalytics['range_label']) ?> across all generation jobs.</p>
                    </div>
                    <div class="ai-range-switch" role="group" aria-label="Analytics date range">
                        <?php foreach ([7 => '7 days', 30 => '30 days', 90 => '90 days'] as $rangeDays => $rangeLabel): ?>
                            <a class="ai-range-link <?= $aiAnalyticsDays === $rangeDays ? 'is-active' : '' ?>"
                               href="?page=admin/ai-assistant&amp;ai_range=<?= (int) $rangeDays ?>#ai-overview"
                               <?= $aiAnalyticsDays === $rangeDays ? 'aria-current="page"' : '' ?>><?= htmlspecialchars($rangeLabel) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="ai-analytics-summary">
                    <article class="ai-analytics-stat">
                        <span>Jobs</span>
                        <strong><?= number_format((int) $aiAnalytics['jobs']) ?></strong>
                        <small><?= htmlspecialchars($aiAnalytics['range_label']) ?></small>
                    </article>
                    <article class="ai-analytics-stat">
                        <span>Applied</span>
                        <strong><?= number_format((int) $aiAnalytics['applied']) ?></strong>
                        <small>Draft courses created</small>
                    </article>
                    <article class="ai-analytics-stat">
                        <span>Failures</span>
                        <strong><?= number_format((int) $aiAnalytics['failures']) ?></strong>
                        <small>Jobs needing attention</small>
                    </article>
                    <article class="ai-analytics-stat">
                        <span>Estimated spend</span>
                        <strong>$<?= htmlspecialchars(number_format((float) $aiAnalytics['cost'], 4)) ?></strong>
                        <small>Provider usage</small>
                    </article>
                    <article class="ai-analytics-stat">
                        <span>Tokens</span>
                        <strong><?= number_format((int) $aiAnalytics['tokens']) ?></strong>
                        <small>Prompt and completion</small>
                    </article>
                </div>

                <div class="ai-analytics-grid">
                    <section class="ai-analytics-card ai-analytics-card--wide">
                        <div class="ai-analytics-card-head">
                            <h3>Jobs and cost by day</h3>
                            <span class="ai-chart-legend">
                                <span class="ai-chart-key ai-chart-key--jobs">Jobs</span>
                                <span class="ai-chart-key ai-chart-key--cost">Cost</span>
                            </span>
                        </div>
                        <?php if ((int) $aiAnalytics['jobs'] > 0): ?>
                            <div class="ai-chart <?= $aiAnalyticsDays > 30 ? 'is-dense' : '' ?>"
                                 role="img"
                                 aria-label="Daily generation jobs and estimated cost for the <?= htmlspecialchars($aiAnalytics['range_label']) ?>.">
                                <?php foreach ($aiAnalytics['daily'] as $dayIndex => $day): ?>
                                    <?php
                                    $jobHeight = (int) $day['jobs'] > 0
                                        ? max(4, (int) round(((int) $day['jobs'] / (int) $aiAnalytics['max_jobs']) * 100))
                                        : 0;
                                    $costHeight = (float) $day['cost'] > 0
                                        ? max(4, (int) round(((float) $day['cost'] / (float) $aiAnalytics['max_cost']) * 100))
                                        : 0;
                                    $showLabel = $aiAnalyticsDays <= 7
                                        || ($aiAnalyticsDays > 30 ? $dayIndex % 7 === 0 : $dayIndex % 3 === 0)
                                        || $dayIndex === count($aiAnalytics['daily']) - 1;
                                    $dayTitle = $day['day'] . ': ' . (int) $day['jobs'] . ' jobs, $' . number_format((float) $day['cost'], 4) . ' cost';
                                    ?>
                                    <div class="ai-chart-col" title="<?= htmlspecialchars($dayTitle, ENT_QUOTES) ?>">
                                        <div class="ai-chart-bars">
                                            <span class="ai-chart-bar ai-chart-bar--jobs" style="height: <?= $jobHeight ?>%"></span>
                                            <span class="ai-chart-bar ai-chart-bar--cost" style="height: <?= $costHeight ?>%"></span>
                                        </div>
                                        <span class="ai-chart-label"><?= $showLabel ? htmlspecialchars($day['label']) : '' ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="ai-analytics-empty">No generation jobs in this range.</div>
                        <?php endif; ?>
                    </section>

                    <section class="ai-analytics-card">
                        <h3>Status funnel</h3>
                        <ul class="ai-funnel-list">
                            <?php foreach ($aiAnalytics['statuses'] as $status => $statusCount): ?>
                                <?php $statusPercent = (int) $aiAnalytics['jobs'] > 0 ? (int) round(($statusCount / (int) $aiAnalytics['jobs']) * 100) : 0; ?>
                                <li>
                                    <div class="ai-funnel-row">
                                        <span class="ai-status ai-status--<?= htmlspecialchars($status, ENT_QUOTES) ?>"><?= htmlspecialchars($aiJobStatusLabel($status)) ?></span>
                                        <strong><?= number_format((int) $statusCount) ?></strong>
                                    </div>
                                    <span class="ai-funnel-track"><span class="ai-funnel-fill ai-funnel-fill--<?= htmlspecialchars($status, ENT_QUOTES) ?>" style="width: <?= $statusPercent ?>%"></span></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>

                    <section class="ai-analytics-card">
                        <h3>Model usage</h3>
                        <?php if ($aiAnalytics['models']): ?>
                            <div class="ai-table-wrap">
                                <table class="ai-table ai-table--compact">
                                    <thead>
                                        <tr>
                                            <th scope="col">Model</th>
                                            <th scope="col">Jobs</th>
                                            <th scope="col">Tokens</th>
                                            <th scope="col">Cost</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($aiAnalytics['models'] as $modelRow): ?>
                                            <tr>
                                                <td><?= htmlspecialchars((string) $modelRow['model']) ?></td>
                                                <td><?= number_format((int) $modelRow['jobs']) ?></td>
                                                <td><?= number_format((int) $modelRow['tokens']) ?></td>
                                                <td>$<?= htmlspecialchars(number_format((float) $modelRow['cost'], 4)) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="ai-analytics-empty">No model usage yet.</div>
                        <?php endif; ?>
                    </section>

                    <section class="ai-analytics-card ai-analytics-card--wide">
                        <h3>Recent failures</h3>
                        <?php if ($aiAnalytics['recent_failures']): ?>
                            <div class="ai-table-wrap">
                                <table class="ai-table ai-table--compact">
                                    <thead>
                                        <tr>
                                            <th scope="col">Job</th>
                                            <th scope="col">Model</th>
                                            <th scope="col">Created</th>
                                            <th scope="col">Safe error</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($aiAnalytics['recent_failures'] as $failure): ?>
                                            <tr>
                                                <td>#<?= (int) $failure['id'] ?></td>
                                                <td><?= htmlspecialchars((string) $failure['model']) ?></td>
                                                <td><?= htmlspecialchars((string) $failure['created_at']) ?></td>
                                                <td><?= htmlspecialchars(mb_substr((string) ($failure['error_message'] ?? 'No safe error recorded.'), 0, 140)) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="ai-analytics-empty">No failures in this range.</div>
                        <?php endif; ?>
                    </section>
                </div>

                <div class="ai-panel-actions">
                    <a class="ai-btn ai-btn--ghost"
                       href="pages/admin/ai-assistant/ajax/export-jobs.php?range=<?= (int) $aiAnalyticsDays ?>">
                        <i class="fas fa-file-csv"></i> Export jobs CSV
                    </a>
                </div>
            </section>
        </div>

        <div class="tab-content" id="ai-generate" data-tab="ai-generate">
            <section class="ai-panel ai-context-panel"
                     data-ai-context-panel
                     data-ai-search-endpoint="pages/admin/ai-assistant/ajax/trainee-search.php"
                     data-ai-context-endpoint="pages/admin/ai-assistant/ajax/trainee-context.php">
                <div class="ai-panel-head">
                    <div>
                        <span class="ai-kicker">Step 1</span>
                        <h2>Choose a trainee and review the evidence</h2>
                    </div>
                    <span class="ai-lock-note"><i class="fas fa-user-shield"></i> Admin only</span>
                </div>
                <p class="ai-context-intro">
                    Search for an active trainee, review the profile-safe evidence, and exclude any source you do not want sent to the model.
                    Contact details, salary, and identifiers are never included in the generation context.
                </p>

                <?php if (!$aiSchema['ready']): ?>
                    <div class="ai-notice ai-notice--danger" role="alert">
                        <i class="fas fa-triangle-exclamation"></i>
                        <p>The AI schema must be applied before trainee context can be prepared.</p>
                    </div>
                <?php elseif (!$aiConfigured): ?>
                    <div class="ai-notice ai-notice--info">
                        <i class="fas fa-circle-info"></i>
                        <p>You can prepare and review the trainee context now. Generation stays disabled until the key and default model are ready.</p>
                    </div>
                <?php endif; ?>

                <div class="ai-context-grid">
                    <div class="ai-context-search">
                        <label class="ai-context-label" for="ai-trainee-search">Find a trainee</label>
                        <div class="ai-search ai-search--context">
                            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                            <input id="ai-trainee-search"
                                   type="search"
                                   placeholder="Search by name, department, or position"
                                   autocomplete="off"
                                   data-ai-trainee-search>
                        </div>
                        <p class="ai-context-hint" data-ai-search-status role="status" aria-live="polite">Type at least two characters.</p>
                        <div class="ai-trainee-results" data-ai-trainee-results role="listbox" aria-label="Trainee search results"></div>
                        <div class="ai-selected-trainee" data-ai-selected-trainee hidden></div>
                        <div class="ai-context-privacy">
                            <i class="fas fa-lock" aria-hidden="true"></i>
                            <span>Only active trainees are searchable. The prompt uses role, course, skill, and performance evidence only.</span>
                        </div>
                    </div>

                    <div class="ai-context-preview" data-ai-context-preview aria-live="polite">
                        <div class="ai-empty ai-empty--compact">
                            <span class="ai-empty-icon"><i class="fas fa-user-check"></i></span>
                            <h3>Select a trainee</h3>
                            <p>The context preview will show skill gaps, course history, assessment results, and any limited-evidence warnings.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="ai-panel ai-wizard-panel"
                     data-ai-wizard
                     data-ai-job-endpoint="pages/admin/ai-assistant/ajax/job.php">
                <div class="ai-panel-head">
                    <div>
                        <span class="ai-kicker">Step 2</span>
                        <h2>Build the course brief</h2>
                    </div>
                    <span class="ai-lock-note"><i class="fas fa-wand-magic-sparkles"></i> Admin only</span>
                </div>
                <p class="ai-context-intro">
                    Choose who owns the draft, describe the learning goal, and shape the outline. The assistant creates a new draft only; it never enrolls the trainee or publishes the course.
                </p>

                <?php if (!$aiConfigured): ?>
                    <div class="ai-notice ai-notice--info">
                        <i class="fas fa-circle-info"></i>
                        <p>Generation stays disabled until the schema, a tested key, and a default model allowed by the current cost mode are ready. You can still prepare the trainee context above.</p>
                    </div>
                <?php endif; ?>

                <div class="ai-wizard-trainee" data-ai-wizard-trainee aria-live="polite">
                    <span class="ai-wizard-trainee-icon"><i class="fas fa-user-check" aria-hidden="true"></i></span>
                    <div>
                        <strong>No trainee selected</strong>
                        <p>Select a trainee in Step 1 to unlock the brief.</p>
                    </div>
                </div>

                <form id="ai-course-form" class="ai-form ai-wizard-form" data-skip="true" data-ai-course-form>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($aiCsrfToken) ?>">
                    <div class="ai-form-grid">
                        <label class="ai-field">
                            <span>Course owner</span>
                            <select name="course_owner_id" required <?= !$aiConfigured ? 'disabled' : '' ?>>
                                <?php foreach ($aiOwnerOptions as $owner): ?>
                                    <option value="<?= (int) $owner['employee_id'] ?>"
                                        <?= (int) $owner['employee_id'] === (int) ($_SESSION['employee_id'] ?? 0) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) $owner['name']) ?>
                                        (<?= htmlspecialchars(LearningRole::label((string) ($owner['learning_role'] ?? ''))) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small>The active trainer or admin responsible for the draft.</small>
                        </label>
                        <label class="ai-field">
                            <span>Model</span>
                            <select name="model" <?= !$aiConfigured ? 'disabled' : '' ?>>
                                <option value="">Use the default eligible model</option>
                                <?php foreach ($aiEnabledModels as $model): ?>
                                    <option value="<?= htmlspecialchars((string) $model['model_slug']) ?>">
                                        <?= htmlspecialchars((string) ($model['display_name'] ?: $model['model_slug'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small>Only enabled OpenRouter models allowed by the current cost mode (<?= htmlspecialchars(AIConfig::modelCostModeLabel($aiModelCostMode)) ?>) can be selected.</small>
                        </label>
                        <label class="ai-field ai-field--wide">
                            <span>Learning goal</span>
                            <textarea name="goal" rows="3" maxlength="1500" required
                                      placeholder="Example: Help this trainee apply data-privacy rules confidently in daily HR work."
                                      <?= !$aiConfigured ? 'disabled' : '' ?>></textarea>
                            <small>Describe the performance outcome, not just the topic.</small>
                        </label>
                        <label class="ai-field">
                            <span>Modules</span>
                            <input type="number" name="module_count" min="1" max="<?= (int) $aiSettings['max_modules'] ?>"
                                   step="1" value="<?= min(4, (int) $aiSettings['max_modules']) ?>"
                                   <?= !$aiConfigured ? 'disabled' : '' ?>>
                        </label>
                        <label class="ai-field">
                            <span>Lessons per module</span>
                            <input type="number" name="lessons_per_module" min="1" max="<?= (int) $aiSettings['max_lessons_per_module'] ?>"
                                   step="1" value="<?= min(3, (int) $aiSettings['max_lessons_per_module']) ?>"
                                   <?= !$aiConfigured ? 'disabled' : '' ?>>
                        </label>
                        <label class="ai-field">
                            <span>Difficulty</span>
                            <select name="difficulty" <?= !$aiConfigured ? 'disabled' : '' ?>>
                                <option value="beginner">Beginner</option>
                                <option value="intermediate" selected>Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </label>
                        <label class="ai-field ai-field--wide">
                            <span>Source notes <em>(optional)</em></span>
                            <textarea name="source_notes" rows="3" maxlength="4000"
                                      placeholder="Paste approved policy excerpts or internal guidance. Do not paste secrets or unnecessary personal data."
                                      <?= !$aiConfigured ? 'disabled' : '' ?>></textarea>
                            <small>These notes are sent to the provider as approved source material.</small>
                        </label>
                    </div>
                    <div class="ai-form-actions ai-wizard-form-actions">
                        <button type="submit" class="ai-btn ai-btn--primary" data-ai-generate-outline
                                <?= !$aiConfigured ? 'disabled' : '' ?>>
                            <i class="fas fa-wand-magic-sparkles"></i> Generate outline
                        </button>
                        <span class="ai-form-status" data-ai-wizard-form-status role="status" aria-live="polite"></span>
                    </div>
                </form>

                <div class="ai-wizard-empty" data-ai-wizard-empty>
                    <span class="ai-empty-icon"><i class="fas fa-route"></i></span>
                    <h3>Select a trainee to begin</h3>
                    <p>The outline editor, generation progress, and quality review will appear here.</p>
                </div>

                <div class="ai-wizard-workspace" data-ai-wizard-workspace hidden>
                    <div class="ai-job-header" data-ai-job-header></div>

                    <section class="ai-wizard-section" data-ai-outline-section hidden>
                        <div class="ai-wizard-section-head">
                            <div>
                                <span class="ai-kicker">Step 3</span>
                                <h3>Review and edit the outline</h3>
                            </div>
                            <span class="ai-wizard-badge">Outline draft</span>
                        </div>
                        <p class="ai-context-intro">Edit titles and descriptions before expansion. Generation stops here until you approve the outline.</p>
                        <div data-ai-outline-editor></div>
                        <div class="ai-form-actions">
                            <button type="button" class="ai-btn ai-btn--primary" data-ai-approve-outline>
                                <i class="fas fa-check"></i> Approve outline and start generation
                            </button>
                            <span class="ai-form-status" data-ai-outline-status role="status" aria-live="polite"></span>
                        </div>
                    </section>

                    <section class="ai-wizard-section" data-ai-progress-section hidden>
                        <div class="ai-wizard-section-head">
                            <div>
                                <span class="ai-kicker">Step 4</span>
                                <h3>Generation progress</h3>
                            </div>
                            <span class="ai-wizard-badge" data-ai-progress-badge>Working</span>
                        </div>
                        <div data-ai-progress></div>
                        <div class="ai-wizard-actions" data-ai-progress-actions></div>
                    </section>

                    <section class="ai-wizard-section" data-ai-review-section hidden>
                        <div class="ai-wizard-section-head">
                            <div>
                                <span class="ai-kicker">Step 5</span>
                                <h3>Review the generated draft</h3>
                            </div>
                            <span class="ai-wizard-badge" data-ai-review-badge>Ready for review</span>
                        </div>
                        <div data-ai-review></div>
                    </section>
                </div>
            </section>
        </div>

        <div class="tab-content" id="ai-jobs" data-tab="ai-jobs">
            <section class="ai-panel" data-ai-jobs-panel
                     data-ai-job-endpoint="pages/admin/ai-assistant/ajax/job.php">
                <div class="ai-panel-head">
                    <div>
                        <span class="ai-kicker">History</span>
                        <h2>Generation jobs</h2>
                    </div>
                    <button type="button" class="ai-btn ai-btn--ghost ai-btn--small" data-ai-refresh-jobs>
                        <i class="fas fa-rotate"></i> Refresh
                    </button>
                </div>
                <div data-ai-jobs-list>
                    <?php if ($aiJobs): ?>
                        <div class="ai-table-wrap">
                            <table class="ai-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Job</th>
                                        <th scope="col">Trainee</th>
                                        <th scope="col">Owner</th>
                                        <th scope="col">Model</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Progress</th>
                                        <th scope="col">Cost</th>
                                        <th scope="col">Created</th>
                                        <th scope="col"><span class="sr-only">Actions</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($aiJobs as $job): ?>
                                        <tr data-ai-job-row="<?= (int) $job['id'] ?>">
                                            <td>#<?= (int) $job['id'] ?></td>
                                            <td>
                                                <?php if (!empty($job['learner_id']) && $job['learner_name']): ?>
                                                    <a href="<?= htmlspecialchars($aiProfileUrl((int) $job['learner_id'], 'learner'), ENT_QUOTES) ?>"><?= htmlspecialchars($job['learner_name']) ?></a>
                                                <?php else: ?>
                                                    &mdash;
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($job['course_owner_id']) && $job['owner_name']): ?>
                                                    <a href="<?= htmlspecialchars($aiProfileUrl((int) $job['course_owner_id'], 'instructor'), ENT_QUOTES) ?>"><?= htmlspecialchars($job['owner_name']) ?></a>
                                                <?php else: ?>
                                                    &mdash;
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($job['model']) ?></td>
                                            <td><span class="ai-status ai-status--<?= htmlspecialchars($job['status'], ENT_QUOTES) ?>"><?= htmlspecialchars($aiJobStatusLabel($job['status'])) ?></span></td>
                                            <td><?= (int) $job['completed_steps'] ?>/<?= (int) $job['total_steps'] ?></td>
                                            <td>$<?= htmlspecialchars(number_format((float) $job['estimated_cost_usd'], 2)) ?></td>
                                            <td><?= htmlspecialchars((string) $job['created_at']) ?></td>
                                            <td>
                                                <button type="button" class="ai-text-action" data-ai-open-job="<?= (int) $job['id'] ?>">
                                                    Open <i class="fas fa-arrow-right"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="ai-empty" data-ai-jobs-empty>
                            <span class="ai-empty-icon"><i class="fas fa-clock-rotate-left"></i></span>
                            <h3>No jobs to show</h3>
                            <p>Jobs will list their status, trainee, owner, model, token usage, and estimated cost.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="tab-content" id="ai-settings" data-tab="ai-settings">
            <section class="ai-panel">
                <div class="ai-panel-head">
                    <div>
                        <span class="ai-kicker">Configuration</span>
                        <h2>Generation defaults</h2>
                    </div>
                    <span class="ai-lock-note"><i class="fas fa-lock"></i> Admin only</span>
                </div>

                <?php if (!$aiMasterKey['configured']): ?>
                    <div class="ai-notice ai-notice--danger" role="alert">
                        <i class="fas fa-triangle-exclamation"></i>
                        <p><strong>Master key required.</strong> <?= htmlspecialchars($aiMasterKey['message']) ?> Provider keys cannot be saved until encryption is configured.</p>
                    </div>
                <?php endif; ?>

                <form id="ai-settings-form" class="ai-form" data-skip="true"
                      data-ai-endpoint="pages/admin/ai-assistant/ajax/settings.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($aiCsrfToken) ?>">

                    <div class="ai-form-section">
                        <div class="ai-form-section-head">
                            <span class="ai-kicker">Provider</span>
                            <h3>Connection</h3>
                        </div>
                        <div class="ai-form-grid">
                            <label class="ai-field ai-field--wide">
                                <span>Base URL</span>
                                <input type="url" name="base_url" value="<?= htmlspecialchars((string) $aiSettings['base_url']) ?>" required>
                                <small>Only the OpenRouter API host is accepted so the key cannot be redirected.</small>
                            </label>
                            <label class="ai-field">
                                <span>Request timeout (seconds)</span>
                                <input type="number" name="request_timeout" min="5" max="300" step="1" value="<?= (int) $aiSettings['request_timeout'] ?>" required>
                            </label>
                            <label class="ai-field">
                                <span>Max retries per step</span>
                                <input type="number" name="max_retries" min="0" max="5" step="1" value="<?= (int) $aiSettings['max_retries'] ?>" required>
                            </label>
                            <label class="ai-field">
                                <span>Temperature</span>
                                <input type="number" name="temperature" min="0" max="1" step="0.05" value="<?= htmlspecialchars((string) $aiSettings['temperature']) ?>" required>
                            </label>
                        </div>
                    </div>

                    <div class="ai-form-section">
                        <div class="ai-form-section-head">
                            <span class="ai-kicker">Model policy</span>
                            <h3>Cost mode</h3>
                        </div>
                        <div class="ai-form-grid">
                            <label class="ai-field ai-field--wide">
                                <span>Model cost mode</span>
                                <select name="model_cost_mode" required>
                                    <?php foreach ($aiModelCostModeOptions as $modeValue => $modeLabel): ?>
                                        <option value="<?= htmlspecialchars((string) $modeValue, ENT_QUOTES) ?>"
                                            <?= $aiModelCostMode === $modeValue ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string) $modeLabel) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small>Free models only is the default. Paid models cannot be tested or used while it is active. Changing this mode refreshes the page so the catalog and generator use the new policy.</small>
                            </label>
                        </div>
                    </div>

                    <div class="ai-form-section">
                        <div class="ai-form-section-head">
                            <span class="ai-kicker">Draft shape</span>
                            <h3>Course limits</h3>
                        </div>
                        <div class="ai-form-grid">
                            <label class="ai-field">
                                <span>Max modules</span>
                                <input type="number" name="max_modules" min="1" max="20" step="1" value="<?= (int) $aiSettings['max_modules'] ?>" required>
                            </label>
                            <label class="ai-field">
                                <span>Max lessons per module</span>
                                <input type="number" name="max_lessons_per_module" min="1" max="20" step="1" value="<?= (int) $aiSettings['max_lessons_per_module'] ?>" required>
                            </label>
                            <label class="ai-field">
                                <span>Max quiz questions per module</span>
                                <input type="number" name="max_quiz_questions_per_module" min="1" max="30" step="1" value="<?= (int) $aiSettings['max_quiz_questions_per_module'] ?>" required>
                            </label>
                            <label class="ai-field">
                                <span>Max evaluation questions</span>
                                <input type="number" name="max_evaluation_questions" min="1" max="50" step="1" value="<?= (int) $aiSettings['max_evaluation_questions'] ?>" required>
                            </label>
                        </div>
                    </div>

                    <div class="ai-form-section">
                        <div class="ai-form-section-head">
                            <span class="ai-kicker">Guardrails</span>
                            <h3>Calls and cost</h3>
                        </div>
                        <div class="ai-form-grid">
                            <label class="ai-field">
                                <span>Max calls per job</span>
                                <input type="number" name="max_calls_per_job" min="1" max="30" step="1" value="<?= (int) $aiSettings['max_calls_per_job'] ?>" required>
                            </label>
                            <label class="ai-field">
                                <span>Max output tokens per step</span>
                                <input type="number" name="max_output_tokens_per_step" min="500" max="20000" step="100" value="<?= (int) $aiSettings['max_output_tokens_per_step'] ?>" required>
                            </label>
                            <label class="ai-field">
                                <span>Jobs per admin per day</span>
                                <input type="number" name="jobs_per_admin_per_day" min="1" max="100" step="1" value="<?= (int) $aiSettings['jobs_per_admin_per_day'] ?>" required>
                            </label>
                            <label class="ai-field">
                                <span>Monthly cost cap (USD)</span>
                                <input type="number" name="monthly_cost_cap_usd" min="0" max="1000" step="0.01" value="<?= htmlspecialchars((string) $aiSettings['monthly_cost_cap_usd']) ?>" required>
                            </label>
                            <label class="ai-field">
                                <span>Per-job cost cap (USD)</span>
                                <input type="number" name="per_job_cost_cap_usd" min="0" max="100" step="0.01" value="<?= htmlspecialchars((string) $aiSettings['per_job_cost_cap_usd']) ?>" required>
                            </label>
                            <label class="ai-field">
                                <span>Job retention (days)</span>
                                <input type="number" name="job_retention_days" min="7" max="365" step="1" value="<?= (int) $aiSettings['job_retention_days'] ?>" required>
                            </label>
                        </div>
                    </div>

                    <div class="ai-form-section">
                        <div class="ai-form-section-head">
                            <span class="ai-kicker">Access</span>
                            <h3>Admin-only MVP</h3>
                        </div>
                        <div class="ai-form-grid">
                            <label class="ai-field ai-field--check">
                                <input type="checkbox" name="allow_trainer_access" value="1" <?= !empty($aiSettings['allow_trainer_access']) ? 'checked' : '' ?> disabled>
                                <span>Allow trainer access (reserved)</span>
                            </label>
                            <label class="ai-field ai-field--check">
                                <input type="checkbox" name="allow_custom_prompt_editing" value="1" <?= !empty($aiSettings['allow_custom_prompt_editing']) ? 'checked' : '' ?> disabled>
                                <span>Allow custom prompt editing (reserved)</span>
                            </label>
                        </div>
                    </div>

                    <div class="ai-form-actions">
                        <button type="submit" class="ai-btn ai-btn--primary">
                            <i class="fas fa-floppy-disk"></i> Save settings
                        </button>
                        <span class="ai-form-status" data-ai-form-status role="status"></span>
                    </div>
                </form>

                <div class="ai-notice ai-notice--info">
                    <i class="fas fa-circle-info"></i>
                    <p>Default and fallback models are chosen in <button type="button" class="ai-inline-link" data-ai-tab-target="ai-keys">API Keys &amp; Models</button> so only enabled models can be selected.</p>
                </div>
            </section>
        </div>

        <div class="tab-content" id="ai-keys" data-tab="ai-keys">
            <div class="ai-split-grid ai-split-grid--keys">
                <section class="ai-panel">
                    <div class="ai-panel-head">
                        <div>
                            <span class="ai-kicker">Provider access</span>
                            <h2>API key</h2>
                        </div>
                        <span class="ai-lock-note"><i class="fas fa-lock"></i> Encrypted</span>
                    </div>

                    <?php if (!$aiMasterKey['configured']): ?>
                        <div class="ai-notice ai-notice--danger" role="alert">
                            <i class="fas fa-triangle-exclamation"></i>
                            <p><strong>Encryption is not configured.</strong> <?= htmlspecialchars($aiMasterKey['message']) ?> The key form stays disabled until the master key is available.</p>
                        </div>
                    <?php endif; ?>

                    <?php if ($aiActiveKey): ?>
                        <div class="ai-key-card ai-key-card--active">
                            <span class="ai-key-icon"><i class="fas fa-key"></i></span>
                            <div class="ai-key-card-body">
                                <strong><?= htmlspecialchars($aiActiveKey['label'] ?? 'OpenRouter key') ?></strong>
                                <span>••••••••<?= htmlspecialchars($aiActiveKey['key_last4'] ?? '') ?></span>
                                <small>Fingerprint <?= htmlspecialchars($aiMaskedFingerprint($aiActiveKey['key_fingerprint'] ?? '')) ?></small>
                            </div>
                            <span class="ai-status ai-status--<?= htmlspecialchars($aiActiveKey['last_test_status'] ?? 'never', ENT_QUOTES) ?>">
                                <?= htmlspecialchars(ucfirst($aiActiveKey['last_test_status'] ?? 'never')) ?>
                            </span>
                        </div>
                        <div class="ai-key-actions">
                            <button type="button" class="ai-btn ai-btn--ghost" data-ai-action="key-test"
                                    data-ai-endpoint="pages/admin/ai-assistant/ajax/test-key.php"
                                    data-key-id="<?= (int) $aiActiveKey['id'] ?>">
                                <i class="fas fa-plug-circle-check"></i> Test key
                            </button>
                            <button type="button" class="ai-btn ai-btn--ghost" data-ai-action="key-disable"
                                    data-ai-endpoint="pages/admin/ai-assistant/ajax/keys.php"
                                    data-key-id="<?= (int) $aiActiveKey['id'] ?>">
                                Disable
                            </button>
                            <button type="button" class="ai-btn ai-btn--danger" data-ai-action="key-revoke"
                                    data-ai-endpoint="pages/admin/ai-assistant/ajax/keys.php"
                                    data-key-id="<?= (int) $aiActiveKey['id'] ?>">
                                Revoke
                            </button>
                        </div>
                        <p class="ai-muted-copy">The full key is never returned after saving. Add a replacement below, test it, then activate it.</p>
                    <?php else: ?>
                        <div class="ai-empty ai-empty--compact">
                            <span class="ai-empty-icon"><i class="fas fa-key"></i></span>
                            <h3>No active API key</h3>
                            <p>Keys are encrypted before storage. Only a fingerprint and the last four characters remain visible.</p>
                        </div>
                    <?php endif; ?>

                    <form id="ai-key-form" class="ai-form ai-form--compact" data-skip="true"
                          data-ai-endpoint="pages/admin/ai-assistant/ajax/keys.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($aiCsrfToken) ?>">
                        <input type="hidden" name="action" value="create">
                        <div class="ai-form-section-head">
                            <span class="ai-kicker"><?= $aiActiveKey ? 'Rotation' : 'Setup' ?></span>
                            <h3><?= $aiActiveKey ? 'Add a replacement key' : 'Add the first key' ?></h3>
                        </div>
                        <label class="ai-field">
                            <span>Label</span>
                            <input type="text" name="label" maxlength="100" value="OpenRouter key" autocomplete="off" <?= !$aiMasterKey['configured'] ? 'disabled' : '' ?>>
                        </label>
                        <label class="ai-field">
                            <span>OpenRouter API key</span>
                            <input type="password" name="api_key" maxlength="512" autocomplete="new-password" spellcheck="false"
                                   placeholder="sk-or-..." required <?= !$aiMasterKey['configured'] ? 'disabled' : '' ?>>
                            <small>The key is encrypted immediately. It will not be shown again.</small>
                        </label>
                        <div class="ai-form-actions">
                            <button type="submit" class="ai-btn ai-btn--primary" <?= !$aiMasterKey['configured'] ? 'disabled' : '' ?>>
                                <i class="fas fa-key"></i> <?= $aiActiveKey ? 'Save replacement' : 'Save key' ?>
                            </button>
                            <span class="ai-form-status" data-ai-form-status role="status"></span>
                        </div>
                        <p class="ai-form-hint">The first key becomes active. Later keys stay disabled until you test and activate them.</p>
                    </form>

                    <?php if ($aiKeys): ?>
                        <div class="ai-table-wrap">
                            <table class="ai-table">
                                <caption class="ai-sr-only">Provider key history</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Key</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Last test</th>
                                        <th scope="col"><span class="ai-sr-only">Actions</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($aiKeys as $keyRow): ?>
                                        <?php
                                        $keyStatus = (string) ($keyRow['status'] ?? 'disabled');
                                        $keyTestStatus = (string) ($keyRow['last_test_status'] ?? 'never');
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($keyRow['label'] ?? 'OpenRouter key') ?></strong>
                                                <span class="ai-table-sub">••••<?= htmlspecialchars($keyRow['key_last4'] ?? '') ?> · <?= htmlspecialchars($aiMaskedFingerprint($keyRow['key_fingerprint'] ?? '')) ?></span>
                                            </td>
                                            <td><span class="ai-badge ai-badge--<?= htmlspecialchars($keyStatus, ENT_QUOTES) ?>"><?= htmlspecialchars(ucfirst($keyStatus)) ?></span></td>
                                            <td>
                                                <span class="ai-badge ai-badge--<?= htmlspecialchars($keyTestStatus, ENT_QUOTES) ?>"><?= htmlspecialchars(ucfirst($keyTestStatus)) ?></span>
                                                <?php if (!empty($keyRow['last_tested_at'])): ?>
                                                    <span class="ai-table-sub"><?= htmlspecialchars(date('M j, H:i', strtotime((string) $keyRow['last_tested_at']))) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="ai-table-actions">
                                                <?php if ($keyStatus !== 'revoked'): ?>
                                                    <button type="button" class="ai-icon-btn" title="Test key"
                                                            data-ai-action="key-test"
                                                            data-ai-endpoint="pages/admin/ai-assistant/ajax/test-key.php"
                                                            data-key-id="<?= (int) $keyRow['id'] ?>">
                                                        <i class="fas fa-plug-circle-check"></i><span class="ai-sr-only">Test</span>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($keyStatus === 'disabled'): ?>
                                                    <button type="button" class="ai-icon-btn" title="Activate key"
                                                            data-ai-action="key-activate"
                                                            data-ai-endpoint="pages/admin/ai-assistant/ajax/keys.php"
                                                            data-key-id="<?= (int) $keyRow['id'] ?>">
                                                        <i class="fas fa-circle-check"></i><span class="ai-sr-only">Activate</span>
                                                    </button>
                                                <?php elseif ($keyStatus === 'active'): ?>
                                                    <button type="button" class="ai-icon-btn" title="Disable key"
                                                            data-ai-action="key-disable"
                                                            data-ai-endpoint="pages/admin/ai-assistant/ajax/keys.php"
                                                            data-key-id="<?= (int) $keyRow['id'] ?>">
                                                        <i class="fas fa-circle-pause"></i><span class="ai-sr-only">Disable</span>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($keyStatus !== 'revoked'): ?>
                                                    <button type="button" class="ai-icon-btn ai-icon-btn--danger" title="Revoke key"
                                                            data-ai-action="key-revoke"
                                                            data-ai-endpoint="pages/admin/ai-assistant/ajax/keys.php"
                                                            data-key-id="<?= (int) $keyRow['id'] ?>">
                                                        <i class="fas fa-ban"></i><span class="ai-sr-only">Revoke</span>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="ai-panel">
                    <div class="ai-panel-head">
                        <div>
                            <span class="ai-kicker">Model catalog</span>
                            <h2>Models</h2>
                        </div>
                        <div class="ai-panel-actions">
                            <span class="ai-count-pill"><?= number_format($aiModelCount) ?> eligible</span>
                            <button type="button" class="ai-btn ai-btn--ghost ai-btn--small" data-ai-action="sync-models"
                                    data-ai-endpoint="pages/admin/ai-assistant/ajax/sync-models.php">
                                <i class="fas fa-rotate"></i> Sync catalog
                            </button>
                        </div>
                    </div>

                    <div class="ai-notice ai-notice--info">
                        <i class="fas fa-scale-balanced"></i>
                        <p>
                            <strong>Model cost mode: <?= htmlspecialchars(AIConfig::modelCostModeLabel($aiModelCostMode)) ?>.</strong>
                            Only models allowed by this mode can be enabled, tested, or used for generation.
                            <?= $aiModelCostMode === 'free_only' ? 'Paid models are blocked and are never tested.' : '' ?>
                        </p>
                    </div>

                    <div class="ai-model-summary">
                        <div class="ai-model-card">
                            <span class="ai-model-icon"><i class="fas fa-star"></i></span>
                            <div>
                                <span class="ai-kicker">Default</span>
                                <strong><?= $aiDefaultModel ? htmlspecialchars($aiDefaultModel['display_name'] ?: $aiDefaultModel['model_slug']) : 'Not selected' ?></strong>
                                <span><?= $aiDefaultModel ? htmlspecialchars($aiDefaultModel['model_slug']) : 'Choose an eligible enabled model below.' ?></span>
                            </div>
                        </div>
                        <div class="ai-model-card">
                            <span class="ai-model-icon"><i class="fas fa-life-ring"></i></span>
                            <div>
                                <span class="ai-kicker">Fallback</span>
                                <strong><?= $aiFallbackModel ? htmlspecialchars($aiFallbackModel['display_name'] ?: $aiFallbackModel['model_slug']) : 'Not selected' ?></strong>
                                <span><?= $aiFallbackModel ? htmlspecialchars($aiFallbackModel['model_slug']) : 'Optional, but recommended.' ?></span>
                            </div>
                        </div>
                    </div>

                    <details class="ai-details">
                        <summary><i class="fas fa-plus"></i> Add a model manually</summary>
                        <form id="ai-model-form" class="ai-form ai-form--compact" data-skip="true"
                              data-ai-endpoint="pages/admin/ai-assistant/ajax/models.php">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($aiCsrfToken) ?>">
                            <input type="hidden" name="action" value="add">
                            <div class="ai-form-grid">
                                <label class="ai-field ai-field--wide">
                                    <span>Model slug</span>
                                    <input type="text" name="model_slug" maxlength="160" placeholder="google/gemma-4-31b-it:free" required>
                                </label>
                                <label class="ai-field ai-field--wide">
                                    <span>Display name</span>
                                    <input type="text" name="display_name" maxlength="160" placeholder="Gemma 4 31B (free)">
                                </label>
                                <label class="ai-field">
                                    <span>Context length</span>
                                    <input type="number" name="context_length" min="0" step="1" value="0">
                                </label>
                                <label class="ai-field">
                                    <span>Prompt price / 1M (USD)</span>
                                    <input type="number" name="prompt_price_per_million" min="0" max="100000" step="0.000001" value="0" required>
                                </label>
                                <label class="ai-field">
                                    <span>Completion price / 1M (USD)</span>
                                    <input type="number" name="completion_price_per_million" min="0" max="100000" step="0.000001" value="0" required>
                                </label>
                                <label class="ai-field ai-field--check">
                                    <input type="checkbox" name="enabled" value="1" checked>
                                    <span>Enable after adding</span>
                                </label>
                            </div>
                            <p class="ai-form-hint">Use 0 for a free model. Sync the catalog for authoritative OpenRouter prices.</p>
                            <div class="ai-form-actions">
                                <button type="submit" class="ai-btn ai-btn--primary ai-btn--small">
                                    <i class="fas fa-plus"></i> Add model
                                </button>
                                <span class="ai-form-status" data-ai-form-status role="status"></span>
                            </div>
                        </form>
                    </details>

                    <?php if ($aiModels): ?>
                        <label class="ai-search">
                            <span class="ai-sr-only">Filter models</span>
                            <i class="fas fa-search"></i>
                            <input type="search" data-ai-model-search placeholder="Filter by name or slug">
                        </label>
                        <div class="ai-table-wrap ai-table-wrap--models">
                            <table class="ai-table ai-model-table">
                                <caption class="ai-sr-only">OpenRouter model catalog</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Model</th>
                                        <th scope="col">Context</th>
                                        <th scope="col">Prompt / 1M</th>
                                        <th scope="col">Completion / 1M</th>
                                        <th scope="col">State</th>
                                        <th scope="col"><span class="ai-sr-only">Actions</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($aiModels as $model): ?>
                                        <?php
                                        $modelEnabled = (int) ($model['enabled'] ?? 0) === 1;
                                        $modelDefault = (int) ($model['is_default'] ?? 0) === 1;
                                        $modelFallback = (int) ($model['is_fallback'] ?? 0) === 1;
                                        $modelFree = AIConfig::isFreeModel($model);
                                        $modelAllowed = AIConfig::modelAllowedForMode($model, $aiModelCostMode);
                                        ?>
                                        <tr data-ai-model-row data-ai-model-search="<?= htmlspecialchars(strtolower((string) ($model['display_name'] ?? '') . ' ' . (string) ($model['model_slug'] ?? ''))) ?>">
                                            <td>
                                                <strong><?= htmlspecialchars($model['display_name'] ?: $model['model_slug']) ?></strong>
                                                <span class="ai-table-sub"><?= htmlspecialchars($model['model_slug']) ?></span>
                                            </td>
                                            <td><?= (int) $model['context_length'] > 0 ? number_format((int) $model['context_length']) : '—' ?></td>
                                            <td><?= htmlspecialchars($aiPricePerMillion($model['prompt_price'] ?? 0)) ?></td>
                                            <td><?= htmlspecialchars($aiPricePerMillion($model['completion_price'] ?? 0)) ?></td>
                                            <td>
                                                <span class="ai-badge ai-badge--<?= $modelFree ? 'free' : 'paid' ?>"><?= $modelFree ? 'Free' : 'Paid' ?></span>
                                                <?php if ($modelDefault): ?><span class="ai-badge ai-badge--default">Default</span><?php endif; ?>
                                                <?php if ($modelFallback): ?><span class="ai-badge ai-badge--fallback">Fallback</span><?php endif; ?>
                                                <span class="ai-badge ai-badge--<?= $modelEnabled ? 'enabled' : 'disabled' ?>"><?= $modelEnabled ? 'Enabled' : 'Disabled' ?></span>
                                                <?php if (!$modelAllowed): ?><span class="ai-badge ai-badge--blocked">Blocked</span><?php endif; ?>
                                            </td>
                                            <td class="ai-table-actions">
                                                <?php if (!$modelAllowed): ?>
                                                    <span class="ai-icon-btn is-disabled" title="Blocked by <?= htmlspecialchars(AIConfig::modelCostModeLabel($aiModelCostMode)) ?>">
                                                        <i class="fas fa-lock"></i><span class="ai-sr-only">Blocked by the current model cost mode</span>
                                                    </span>
                                                    <?php if ($modelEnabled): ?>
                                                        <button type="button" class="ai-icon-btn" title="Disable model"
                                                                data-ai-action="model-disable"
                                                                data-ai-endpoint="pages/admin/ai-assistant/ajax/models.php"
                                                                data-model-id="<?= (int) $model['id'] ?>">
                                                            <i class="fas fa-circle-pause"></i><span class="ai-sr-only">Disable</span>
                                                        </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if ($modelEnabled): ?>
                                                        <button type="button" class="ai-icon-btn" title="Test model with the active key"
                                                                data-ai-action="model-test"
                                                                data-ai-endpoint="pages/admin/ai-assistant/ajax/test-model.php"
                                                                data-model-id="<?= (int) $model['id'] ?>">
                                                            <i class="fas fa-flask"></i><span class="ai-sr-only">Test model</span>
                                                        </button>
                                                        <button type="button" class="ai-icon-btn" title="Disable model"
                                                                data-ai-action="model-disable"
                                                                data-ai-endpoint="pages/admin/ai-assistant/ajax/models.php"
                                                                data-model-id="<?= (int) $model['id'] ?>">
                                                            <i class="fas fa-circle-pause"></i><span class="ai-sr-only">Disable</span>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="ai-icon-btn" title="Enable model"
                                                                data-ai-action="model-enable"
                                                                data-ai-endpoint="pages/admin/ai-assistant/ajax/models.php"
                                                                data-model-id="<?= (int) $model['id'] ?>">
                                                            <i class="fas fa-circle-check"></i><span class="ai-sr-only">Enable</span>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($modelEnabled && !$modelDefault): ?>
                                                        <button type="button" class="ai-icon-btn" title="Make default"
                                                                data-ai-action="model-default"
                                                                data-ai-endpoint="pages/admin/ai-assistant/ajax/models.php"
                                                                data-model-id="<?= (int) $model['id'] ?>">
                                                            <i class="fas fa-star"></i><span class="ai-sr-only">Make default</span>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($modelEnabled && !$modelDefault && !$modelFallback): ?>
                                                        <button type="button" class="ai-icon-btn" title="Make fallback"
                                                                data-ai-action="model-fallback"
                                                                data-ai-endpoint="pages/admin/ai-assistant/ajax/models.php"
                                                                data-model-id="<?= (int) $model['id'] ?>">
                                                            <i class="fas fa-life-ring"></i><span class="ai-sr-only">Make fallback</span>
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="ai-form-hint" data-ai-model-empty hidden>No models match that filter.</p>
                    <?php else: ?>
                        <div class="ai-empty ai-empty--compact">
                            <span class="ai-empty-icon"><i class="fas fa-microchip"></i></span>
                            <h3>No models in the catalog</h3>
                            <p>Sync the OpenRouter catalog or add a model manually, then choose a default and fallback.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>

        <div class="tab-content" id="ai-prompts" data-tab="ai-prompts">
            <section class="ai-panel">
                <div class="ai-panel-head">
                    <div>
                        <span class="ai-kicker">Versioned instructions</span>
                        <h2>Prompt templates</h2>
                    </div>
                </div>
                <div class="ai-empty">
                    <span class="ai-empty-icon"><i class="fas fa-file-lines"></i></span>
                    <h3>No prompt versions yet</h3>
                    <p>System, outline, module, and evaluation prompts will be versioned here. Jobs will record the version they used.</p>
                </div>
            </section>
        </div>

        <div class="tab-content" id="ai-audit" data-tab="ai-audit">
            <section class="ai-panel">
                <div class="ai-panel-head">
                    <div>
                        <span class="ai-kicker">Traceability</span>
                        <h2>Audit log</h2>
                    </div>
                    <span class="ai-count-pill"><?= number_format(count($aiAuditEntries)) ?> recent</span>
                </div>
                <?php if ($aiAuditEntries): ?>
                    <div class="ai-table-wrap">
                        <table class="ai-table">
                            <caption class="ai-sr-only">Recent AI configuration audit events</caption>
                            <thead>
                                <tr>
                                    <th scope="col">When</th>
                                    <th scope="col">Admin</th>
                                    <th scope="col">Action</th>
                                    <th scope="col">Entity</th>
                                    <th scope="col">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aiAuditEntries as $entry): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(date('M j, H:i', strtotime((string) $entry['created_at']))) ?></td>
                                        <td><?= htmlspecialchars($entry['employee_name'] ?: ('Employee ' . (string) $entry['employee_id'])) ?></td>
                                        <td><code><?= htmlspecialchars((string) $entry['action']) ?></code></td>
                                        <td>
                                            <?= htmlspecialchars((string) ($entry['entity_type'] ?? '—')) ?>
                                            <?php if (!empty($entry['entity_id'])): ?>
                                                <span class="ai-table-sub">#<?= htmlspecialchars((string) $entry['entity_id']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="ai-table-sub"><?= htmlspecialchars((string) ($entry['ip_address'] ?? '')) ?></span>
                                            <?php if (!empty($entry['metadata_json'])): ?>
                                                <code class="ai-audit-meta"><?= htmlspecialchars(mb_substr((string) $entry['metadata_json'], 0, 160)) ?></code>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="ai-empty">
                        <span class="ai-empty-icon"><i class="fas fa-shield-halved"></i></span>
                        <h3>No audit events yet</h3>
                        <p>Key changes, model choices, settings updates, job actions, and sensitive access attempts will be recorded here.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>
<script src="js/admin/ai-assistant.js?v=<?= (int) filemtime(__DIR__ . '/../../js/admin/ai-assistant.js') ?>"></script>
