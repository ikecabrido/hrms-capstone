<?php
/**
 * AIContentRenderer - strict, dependency-free Markdown-to-HTML renderer for
 * AI-generated lesson content.
 *
 * All source text is escaped before any Markdown formatting is applied. The
 * renderer never emits links, images, raw HTML, scripts, iframes, event
 * handlers, or attributes. The final HTML is passed through an allow-list so
 * only headings, paragraphs, lists, emphasis, blockquotes, and simple code can
 * reach the learner lesson page.
 */
class AIContentRenderer
{
    private const ALLOWED_TAGS = '<p><br><h1><h2><h3><ul><ol><li><strong><em><blockquote><pre><code>';

    public static function render(string $markdown): string
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
        $markdown = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $markdown) ?? '';
        $lines = explode("\n", $markdown);

        $html = [];
        $paragraph = [];
        $listType = null;
        $listItems = [];
        $quote = [];
        $code = [];
        $inCode = false;

        $flushParagraph = static function () use (&$paragraph, &$html): void {
            if ($paragraph === []) {
                return;
            }
            $html[] = '<p>' . self::inline(implode(' ', $paragraph)) . '</p>';
            $paragraph = [];
        };

        $flushList = static function () use (&$listType, &$listItems, &$html): void {
            if ($listType === null || $listItems === []) {
                $listType = null;
                $listItems = [];
                return;
            }
            $tag = $listType;
            $items = '';
            foreach ($listItems as $item) {
                $items .= '<li>' . $item . '</li>';
            }
            $html[] = '<' . $tag . '>' . $items . '</' . $tag . '>';
            $listType = null;
            $listItems = [];
        };

        $flushQuote = static function () use (&$quote, &$html): void {
            if ($quote === []) {
                return;
            }
            $html[] = '<blockquote><p>' . self::inline(implode(' ', $quote)) . '</p></blockquote>';
            $quote = [];
        };

        $flushCode = static function () use (&$code, &$html): void {
            $body = htmlspecialchars(implode("\n", $code), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html[] = '<pre><code>' . $body . '</code></pre>';
            $code = [];
        };

        foreach ($lines as $line) {
            if (preg_match('/^\s*```/', $line) === 1) {
                if ($inCode) {
                    $flushCode();
                    $inCode = false;
                } else {
                    $flushParagraph();
                    $flushList();
                    $flushQuote();
                    $inCode = true;
                    $code = [];
                }
                continue;
            }

            if ($inCode) {
                $code[] = $line;
                continue;
            }

            if (trim($line) === '') {
                $flushParagraph();
                $flushList();
                $flushQuote();
                continue;
            }

            if (preg_match('/^(#{1,3})\s+(.*)$/', $line, $matches) === 1) {
                $flushParagraph();
                $flushList();
                $flushQuote();
                $level = strlen($matches[1]);
                $html[] = '<h' . $level . '>' . self::inline(trim($matches[2])) . '</h' . $level . '>';
                continue;
            }

            if (preg_match('/^>\s?(.*)$/', $line, $matches) === 1) {
                $flushParagraph();
                $flushList();
                $quote[] = trim($matches[1]);
                continue;
            }

            if (preg_match('/^[-*]\s+(.*)$/', $line, $matches) === 1) {
                $flushParagraph();
                $flushQuote();
                if ($listType !== 'ul') {
                    $flushList();
                    $listType = 'ul';
                }
                $listItems[] = self::inline(trim($matches[1]));
                continue;
            }

            if (preg_match('/^\d+\.\s+(.*)$/', $line, $matches) === 1) {
                $flushParagraph();
                $flushQuote();
                if ($listType !== 'ol') {
                    $flushList();
                    $listType = 'ol';
                }
                $listItems[] = self::inline(trim($matches[1]));
                continue;
            }

            $flushList();
            $flushQuote();
            $paragraph[] = trim($line);
        }

        $flushParagraph();
        $flushList();
        $flushQuote();
        if ($inCode) {
            $flushCode();
        }

        return strip_tags(implode("\n", $html), self::ALLOWED_TAGS);
    }

    private static function inline(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escaped = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped) ?? $escaped;
        $escaped = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $escaped) ?? $escaped;
        $escaped = preg_replace('/`([^`]+)`/s', '<code>$1</code>', $escaped) ?? $escaped;

        return $escaped;
    }
}
