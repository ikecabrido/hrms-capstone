<?php
/**
 * AIAuthoring - role-aware entry points into the admin AI Assistant.
 *
 * The AI Assistant remains administrator-only. These helpers only render a
 * launcher when the current Learning session is an administrator, so trainers
 * and trainees never see a link that would end in a 403 response.
 */
class AIAuthoring
{
    /**
     * @return array{title: string, copy: string}|null
     */
    private static function contextConfig(string $context): ?array
    {
        $configs = [
            'course' => [
                'title' => 'Create a new course with AI',
                'copy' => 'Generate a draft outline, lessons, quizzes, and evaluation, then review it in the course builder.',
            ],
            'learning-path' => [
                'title' => 'Create courses for this learning path with AI',
                'copy' => 'Generate draft courses with the assistant, then add the reviewed courses to this path.',
            ],
        ];

        return $configs[$context] ?? null;
    }

    public static function isAdmin(): bool
    {
        $role = strtolower((string) ($_SESSION['learning_role'] ?? ''));

        return $role === 'admin'
            || !empty($_SESSION['is_admin'])
            || !empty($_SESSION['admin_access']);
    }

    public static function callout(string $context): string
    {
        if (!self::isAdmin()) {
            return '';
        }

        $config = self::contextConfig($context);
        if ($config === null) {
            return '';
        }

        $href = '?page=admin/ai-assistant&ai_from=' . rawurlencode($context);

        return '<div class="ai-authoring-callout" role="note">'
            . '<span class="ai-authoring-callout-icon" aria-hidden="true"><i class="fas fa-wand-magic-sparkles"></i></span>'
            . '<div class="ai-authoring-callout-copy">'
            . '<strong>' . htmlspecialchars($config['title'], ENT_QUOTES) . '</strong>'
            . '<p>' . htmlspecialchars($config['copy'], ENT_QUOTES) . '</p>'
            . '</div>'
            . '<a class="ai-authoring-callout-action" href="' . htmlspecialchars($href, ENT_QUOTES) . '" target="_blank" rel="noopener">'
            . 'Open AI Assistant <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i>'
            . '</a>'
            . '</div>';
    }

    public static function button(string $context): string
    {
        if (!self::isAdmin()) {
            return '';
        }

        $config = self::contextConfig($context);
        if ($config === null) {
            return '';
        }

        $href = '?page=admin/ai-assistant&ai_from=' . rawurlencode($context);

        return '<a class="cs-circ-btn cs-circ-btn-ai" href="' . htmlspecialchars($href, ENT_QUOTES) . '"'
            . ' target="_blank" rel="noopener" title="Open AI Assistant" aria-label="Open AI Assistant">'
            . '<i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i>'
            . '<span class="cs-tooltip">AI Assistant</span></a>';
    }
}
