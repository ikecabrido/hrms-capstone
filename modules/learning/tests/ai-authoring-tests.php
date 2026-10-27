<?php
/**
 * Role and markup checks for the AI authoring entry points.
 *
 *     php modules/learning/tests/ai-authoring-tests.php
 */

require_once dirname(__DIR__) . '/classes/AIAuthoring.php';

$coursePage = file_get_contents(dirname(__DIR__) . '/pages/instructor/elearning-subpage/course.php');
$courseChatScript = file_get_contents(dirname(__DIR__) . '/js/course-ai-chat.js');
$courseChatEndpoint = file_get_contents(dirname(__DIR__) . '/pages/admin/ai-assistant/ajax/course-chat.php');
$aiAjaxBootstrap = file_get_contents(dirname(__DIR__) . '/pages/admin/ai-assistant/ajax/_bootstrap.php');
$passed = 0;
$failed = 0;

function check(string $name, bool $condition, string $detail = ''): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "PASS  {$name}\n";
        return;
    }
    $failed++;
    echo "FAIL  {$name}" . ($detail !== '' ? " - {$detail}" : '') . "\n";
}

$_SESSION = [];

check('non-admin sees no course callout', AIAuthoring::callout('course') === '');
check('non-admin sees no course button', AIAuthoring::button('course') === '');

$_SESSION['learning_role'] = 'instructor';
check('instructor sees no course callout', AIAuthoring::callout('course') === '');
check('instructor sees no learning-path callout', AIAuthoring::callout('learning-path') === '');
check('trainer can use the course chat', AIAuthoring::canUseCourseChat());

$_SESSION['learning_role'] = 'learner';
check('learner sees no course callout', AIAuthoring::callout('course') === '');
check('learner sees no course button', AIAuthoring::button('course') === '');
check('learner cannot use the course chat', !AIAuthoring::canUseCourseChat());

$_SESSION['learning_role'] = 'admin';
check('admin can use the course chat', AIAuthoring::canUseCourseChat());
$courseCallout = AIAuthoring::callout('course');
$pathCallout = AIAuthoring::callout('learning-path');
$courseButton = AIAuthoring::button('course');

check('admin sees course callout', str_contains($courseCallout, 'ai-authoring-callout'));
check('course callout names the new course', str_contains($courseCallout, 'Create a new course with AI'));
check('course callout remains focused on full AI authoring', !str_contains($courseCallout, 'data-course-ai-chat-open'));
check('course page has a separate floating chat launcher', str_contains($coursePage, 'class="course-ai-chat-launcher" data-course-ai-chat-open'));
check('course chat reports non-JSON server responses with HTTP status', str_contains($courseChatScript, 'The server returned an unexpected response (HTTP ') && str_contains($courseChatScript, 'response.text()'));
check('course chat displays the upstream provider HTTP status', str_contains($courseChatEndpoint, "'provider_http_status' => (int) (\$result['http_status'] ?? 0)") && str_contains($courseChatScript, 'AI provider HTTP '));
check('AI endpoint substitutes invalid UTF-8 in JSON responses', str_contains($aiAjaxBootstrap, 'JSON_INVALID_UTF8_SUBSTITUTE'));
check('course callout links to AI assistant', str_contains($courseCallout, 'page=admin/ai-assistant'));
check('course callout carries course origin', str_contains($courseCallout, 'ai_from=course'));
check('course callout opens a safe new tab', str_contains($courseCallout, 'target="_blank"') && str_contains($courseCallout, 'rel="noopener"'));
check('admin sees learning-path callout', str_contains($pathCallout, 'Create courses for this learning path with AI'));
check('learning-path callout does not show the course chat', !str_contains($pathCallout, 'data-course-ai-chat-open'));
check('learning-path callout carries path origin', str_contains($pathCallout, 'ai_from=learning-path'));
check('admin sees compact course button', str_contains($courseButton, 'cs-circ-btn-ai'));
check('compact button carries course origin', str_contains($courseButton, 'ai_from=course'));
check('unknown context is ignored', AIAuthoring::callout('unknown') === '' && AIAuthoring::button('unknown') === '');

$_SESSION = ['is_admin' => 1];
check('admin flag alone enables the callout', AIAuthoring::callout('course') !== '');
$_SESSION = ['admin_access' => 1];
check('admin access flag alone enables the callout', AIAuthoring::callout('course') !== '');

$_SESSION = [];

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
