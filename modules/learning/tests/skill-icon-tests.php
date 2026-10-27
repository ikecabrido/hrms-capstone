<?php
require_once dirname(__DIR__) . '/classes/skill.php';

$passed = 0;
$failed = 0;

function checkSkillIcon(string $label, bool $condition): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "PASS {$label}\n";
        return;
    }

    $failed++;
    echo "FAIL {$label}\n";
}

checkSkillIcon('default icon is in the available collection', Skill::isValidIcon(Skill::DEFAULT_ICON));
checkSkillIcon('icon collection is searchable with labels', count(Skill::ICON_OPTIONS) >= 30 && isset(Skill::ICON_OPTIONS['fas fa-users']));
checkSkillIcon('valid Font Awesome icon is accepted', Skill::isValidIcon('fas fa-brain'));
checkSkillIcon('unlisted Font Awesome solid icon is accepted', Skill::isValidIcon('fas fa-award'));
checkSkillIcon('malformed icon class is refused', !Skill::isValidIcon('fas fa--award'));
checkSkillIcon('image URL is refused', !Skill::isValidIcon('https://example.test/icon.svg'));
checkSkillIcon('HTML injection is refused', !Skill::isValidIcon('fas fa-star" onmouseover="alert(1)'));

try {
    $pdo = (new Database())->getConnection();
    $pdo->beginTransaction();
    $name = 'Icon picker test ' . bin2hex(random_bytes(6));
    $skillModel = new Skill($pdo);
    $invalidResult = $skillModel->create([
        'name' => $name . ' invalid',
        'icon' => 'https://example.test/icon.svg',
    ]);
    checkSkillIcon('unapproved icon cannot be saved', empty($invalidResult['success']));
    $injectionResult = $skillModel->create([
        'name' => $name . ' injection',
        'icon' => 'fas fa-star" onmouseover="alert(1)',
    ]);
    checkSkillIcon('injected icon class cannot be saved', empty($injectionResult['success']));
    $result = $skillModel->create([
        'name' => $name,
        'description' => 'Temporary icon persistence test.',
        'icon' => 'fas fa-award',
        'status' => 'active',
    ]);
    $stmt = $pdo->prepare('SELECT icon FROM ld_skill WHERE id = :id');
    $stmt->execute([':id' => (int) $result['id']]);
    checkSkillIcon('custom icon is persisted with the skill', $stmt->fetchColumn() === 'fas fa-award');
    $skillRow = $skillModel->getById((int) $result['id']);
    checkSkillIcon('skill detail returns its custom icon', ($skillRow['icon'] ?? '') === 'fas fa-award');
    $updateResult = $skillModel->update([
        'id' => (int) $result['id'],
        'name' => $name,
        'description' => 'Temporary icon persistence test.',
        'status' => 'active',
    ]);
    $skillRow = $skillModel->getById((int) $result['id']);
    checkSkillIcon('editing skill details without an icon preserves its icon', !empty($updateResult['success']) && ($skillRow['icon'] ?? '') === 'fas fa-award');
    $iconUpdateResult = $skillModel->update([
        'id' => (int) $result['id'],
        'name' => $name,
        'description' => 'Temporary icon persistence test.',
        'status' => 'active',
        'icon' => 'fas fa-users',
    ]);
    $skillRow = $skillModel->getById((int) $result['id']);
    checkSkillIcon('skill icon can be updated to another approved icon', !empty($iconUpdateResult['success']) && ($skillRow['icon'] ?? '') === 'fas fa-users');
    $pdo->rollBack();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    checkSkillIcon('selected icon is persisted with the skill', false);
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
}

echo PHP_EOL . "{$passed} passed, {$failed} failed." . PHP_EOL;
exit($failed === 0 ? 0 : 1);
