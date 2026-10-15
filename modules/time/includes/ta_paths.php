<?php
/**
 * Resolve the Time module URL path relative to the web root.
 *
 * Handles: module under the document root, module at the site root, symlinked or
 * unusual document root layouts, and requests served from a nested script path.
 */
function ta_base_path(): string
{
    static $cachedPath = null;
    if ($cachedPath !== null) {
        return $cachedPath;
    }

    $normalizePath = static function (string $path): string {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path) ?: $path;
        $path = rtrim($path, '/');

        return $path === '' ? '/' : $path;
    };

    $moduleDir = realpath(__DIR__ . '/..');
    if ($moduleDir === false) {
        $cachedPath = '';
        return $cachedPath;
    }

    $moduleDir = $normalizePath($moduleDir);
    $moduleName = basename($moduleDir);

    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    if ($documentRoot !== '') {
        $realRoot = realpath($documentRoot);
        $documentRoot = $realRoot !== false ? $normalizePath($realRoot) : $normalizePath($documentRoot);
    }

    if ($documentRoot !== '') {
        $moduleLower = strtolower($moduleDir);
        $docRootLower = strtolower($documentRoot);

        if ($moduleLower === $docRootLower) {
            $cachedPath = '';
            return $cachedPath;
        }

        $prefix = $documentRoot . '/';
        $matchPos = stripos($moduleDir, $prefix);
        if ($matchPos === 0 || $moduleLower === $docRootLower) {
            $relativePath = substr($moduleDir, strlen($documentRoot));
            $relativePath = $normalizePath($relativePath);
            $cachedPath = ($relativePath === '/' || $relativePath === '') ? '' : $relativePath;
            return $cachedPath;
        }
    }

    $scriptName = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
    if ($scriptName !== '') {
        $scriptName = $normalizePath($scriptName);
        $segments = array_values(array_filter(explode('/', trim($scriptName, '/')), static fn ($segment) => $segment !== ''));

        for ($i = count($segments); $i > 0; $i--) {
            $candidate = '/' . implode('/', array_slice($segments, 0, $i));
            if (basename($candidate) === $moduleName) {
                $cachedPath = rtrim($candidate, '/');
                if ($cachedPath === '/' || $cachedPath === '') {
                    $cachedPath = '';
                }
                return $cachedPath;
            }
        }
    }

    $cachedPath = '';
    return $cachedPath;
}
