<?php
/**
 * Shared bootstrap for the role profile pages (admin / instructor / learner).
 *
 * Including this file from a profile page populates:
 *   $pdo          — a live PDO connection (or null when the DB is unavailable)
 *   $profileId    — the signed-in employee id
 *   $profileData  — a normalised array of everything the shared partials render
 *   $gradebook    — the learner grade-book summary (avg score + mastered/gap skills)
 *
 * Role pages may set $profileRoleLabel before including to control the label
 * shown under the member's name (e.g. "Administrator").
 */

require_once dirname(__DIR__, 3) . '/classes/Employee.php';
require_once dirname(__DIR__, 3) . '/classes/UserProfile.php';
require_once dirname(__DIR__, 3) . '/classes/UserSession.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$profileId = (int) ($employeeClass->getEmployeeId() ?? 0);

$pdo = null;
try {
    $pdo = (new Database())->getConnection();
} catch (Throwable $e) {
    $pdo = null;
}

// ─── Employee record (HR-owned fields are read-only on the profile) ───────────
$employee = [
    'employee_id'       => $profileId,
    'employee_code'     => '',
    'first_name'        => '',
    'last_name'         => '',
    'email'             => '',
    'mobile_no'         => '',
    'phone_no'          => '',
    'current_address'   => '',
    'permanent_address' => '',
    'position_name'     => '',
    'department_name'   => '',
];

if ($pdo && $profileId > 0) {
    try {
        $stmt = $pdo->prepare(
            "SELECT e.employee_id, e.employee_code, e.first_name, e.last_name, e.email,
                    e.mobile_no, e.phone_no, e.current_address, e.permanent_address,
                    p.position_name, d.department_name
             FROM em_employees e
             LEFT JOIN em_positions p ON p.position_id = e.position_id
             LEFT JOIN em_departments d ON d.department_id = e.department_id
             WHERE e.employee_id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $profileId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $employee = array_merge($employee, $row);
        }
    } catch (Throwable $e) {
        // keep defaults
    }
}

// ─── Bio / photo extras (ld_user_profile) ────────────────────────────────────
$userProfileRow = ['bio' => null, 'profile_photo' => null];
try {
    $userProfileRow = (new UserProfile($pdo))->getForEmployee($profileId);
} catch (Throwable $e) {
    // keep defaults
}

$bio = trim((string) ($userProfileRow['bio'] ?? ''));
$photoFile = $userProfileRow['profile_photo'] ?? null;
$photoUrl = ($photoFile !== null && $photoFile !== '')
    ? 'uploads/profile-photos/' . rawurlencode($photoFile)
    : null;

$fullName = trim((string) $employee['first_name'] . ' ' . (string) $employee['last_name']);
if ($fullName === '') {
    $fullName = 'Unknown';
}

$initials = strtoupper(substr((string) $employee['first_name'], 0, 1) . substr((string) $employee['last_name'], 0, 1));
if ($initials === '') {
    $initials = '??';
}

// ─── Profile completion meter ────────────────────────────────────────────────
$completionItems = [
    ['key' => 'photo',   'label' => 'Profile photo',  'done' => $photoFile !== null && $photoFile !== ''],
    ['key' => 'bio',     'label' => 'Short bio',      'done' => $bio !== ''],
    ['key' => 'mobile',  'label' => 'Mobile number',  'done' => trim((string) $employee['mobile_no']) !== ''],
    ['key' => 'address', 'label' => 'Current address', 'done' => trim((string) $employee['current_address']) !== ''],
];

$completionDone = 0;
foreach ($completionItems as $item) {
    if ($item['done']) {
        $completionDone++;
    }
}
$completionPercent = (int) round($completionDone / max(1, count($completionItems)) * 100);

// ─── Security: active sessions + previous login ──────────────────────────────
$sessions = [];
$previousLogin = $_SESSION['ld_prev_login'] ?? null;
try {
    $sessions = (new UserSession($pdo))->listActive($profileId, UserSession::currentToken());
} catch (Throwable $e) {
    $sessions = [];
}

// ─── Grade-book summary (only rendered by the admin / learner pages) ─────────
$gradebook = [
    'avg_score'    => 0.0,
    'graded_count' => 0,
    'mastered'     => [],
    'gaps'         => [],
    'score_pct'    => 0.0,
];

if ($pdo && $profileId > 0) {
    try {
        $stmt = $pdo->prepare(
            "SELECT ROUND(COALESCE(AVG(final_score), 0), 1) AS avg_score, COUNT(*) AS graded_count
             FROM ld_grade WHERE learner_id = :lid"
        );
        $stmt->execute([':lid' => $profileId]);
        $gradeRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $gradebook['avg_score'] = (float) ($gradeRow['avg_score'] ?? 0);
        $gradebook['graded_count'] = (int) ($gradeRow['graded_count'] ?? 0);

        $allSkills = $pdo->query("SELECT id, name FROM ld_skill WHERE status = 'active' ORDER BY name ASC")
            ->fetchAll(PDO::FETCH_ASSOC);

        $completedStmt = $pdo->prepare("SELECT course_id FROM ld_enrollment WHERE learner_id = :lid AND status = 'completed'");
        $completedStmt->execute([':lid' => $profileId]);
        $completedCourseIds = array_map('intval', $completedStmt->fetchAll(PDO::FETCH_COLUMN));

        $masteredIds = [];
        if (!empty($completedCourseIds)) {
            $masteredStmt = $pdo->query(
                "SELECT DISTINCT cs.skill_id FROM ld_course_skill cs
                 WHERE cs.course_id IN (" . implode(',', $completedCourseIds) . ")"
            );
            $masteredIds = array_map('intval', $masteredStmt->fetchAll(PDO::FETCH_COLUMN));
        }

        foreach ($allSkills as $skill) {
            if (in_array((int) $skill['id'], $masteredIds, true)) {
                $gradebook['mastered'][] = $skill['name'];
            } else {
                $gradebook['gaps'][] = $skill['name'];
            }
        }

        $gradebook['score_pct'] = max(0, min(100, (float) $gradebook['avg_score']));
    } catch (Throwable $e) {
        // keep defaults
    }
}

// ─── Normalised payload used by every shared partial ─────────────────────────
$profileData = [
    'id'          => $profileId,
    'employee'    => $employee,
    'full_name'   => $fullName,
    'initials'    => $initials,
    'bio'         => $bio,
    'photo_file'  => $photoFile,
    'photo_url'   => $photoUrl,
    'role_label'  => isset($profileRoleLabel) ? (string) $profileRoleLabel : 'Member',
    'contact'     => [
        'email'           => (string) ($employee['email'] ?? ''),
        'mobile_no'       => (string) ($employee['mobile_no'] ?? ''),
        'phone_no'        => (string) ($employee['phone_no'] ?? ''),
        'current_address' => (string) ($employee['current_address'] ?? ''),
    ],
    'completion'  => [
        'percent' => $completionPercent,
        'done'    => $completionDone,
        'total'   => count($completionItems),
        'items'   => $completionItems,
    ],
    'sessions'        => $sessions,
    'previous_login'  => $previousLogin,
];
