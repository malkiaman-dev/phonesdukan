<?php
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', realpath(dirname(__DIR__, 2)) ?: dirname(__DIR__, 2));
}

$functionsFile = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'functions.php';
if (is_file($functionsFile)) {
    require_once $functionsFile;
}

$helpersFile = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'helpers.php';
if (is_file($helpersFile)) {
    require_once $helpersFile;
}

if (!defined('BASE_PATH') || !defined('BASE_URL')) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $scriptFilename = realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '';
    $projectRoot = PROJECT_ROOT;

    $basePath = '';

    if ($scriptName !== '' && $scriptFilename !== '' && stripos($scriptFilename, $projectRoot) === 0) {
        $scriptDirUrl = trim(str_replace('\\', '/', dirname($scriptName)), '/');
        $relativeDirFs = trim(str_replace('\\', '/', substr(dirname($scriptFilename), strlen($projectRoot))), '/');

        if ($relativeDirFs !== '') {
            $suffix = '/' . $relativeDirFs;
            if (substr($scriptDirUrl, -strlen($suffix)) === $suffix) {
                // e.g. URL dir "phonesdukan/admin", FS dir "admin" → base is "phonesdukan"
                $scriptDirUrl = substr($scriptDirUrl, 0, -strlen($suffix));
            } elseif ($scriptDirUrl === $relativeDirFs) {
                // URL dir equals FS dir exactly (e.g. both "admin") — site is at web root
                $scriptDirUrl = '';
            }
        }

        $basePath = $scriptDirUrl === '' ? '' : '/' . trim($scriptDirUrl, '/');
    }

    if ($basePath === '' && $scriptName !== '') {
        $segments = array_values(array_filter(explode('/', trim($scriptName, '/'))));
        $firstSegment = $segments[0] ?? '';
        $secondSegment = $segments[1] ?? '';

        if (strtolower($firstSegment) === 'admin' && strpos($secondSegment, '.php') !== false) {
            $basePath = '';
        } else {
        $basePath = (strpos($firstSegment, '.php') !== false || $firstSegment === '')
            ? ''
            : '/' . $firstSegment;
        }
    }

    if (!defined('BASE_PATH')) {
        define('BASE_PATH', $basePath);
    }

    if (!defined('BASE_URL')) {
        define('BASE_URL', BASE_PATH === '' ? '/' : BASE_PATH . '/');
    }
}

if (!defined('OUTPUT_URL_REWRITE_ENABLED')) {
    define('OUTPUT_URL_REWRITE_ENABLED', true);

    ob_start(function ($buffer) {
        if (BASE_PATH === '' || stripos($buffer, '<html') === false) {
            return $buffer;
        }

        $baseTrim = trim(BASE_PATH, '/');

        $buffer = preg_replace_callback(
            '/\b(href|src|action|poster)\s*=\s*(["\'])\/(?!\/)([^"\']*)\2/i',
            function ($m) use ($baseTrim) {
                $path = $m[3];
                if ($path === '' || strpos($path, $baseTrim . '/') === 0 || $path === $baseTrim) {
                    return $m[0];
                }

                return $m[1] . '=' . $m[2] . BASE_PATH . '/' . ltrim($path, '/') . $m[2];
            },
            $buffer
        );

        $buffer = preg_replace_callback(
            '/url\(("|\')?\/(?!\/)([^)"\']+)(\1)?\)/i',
            function ($m) use ($baseTrim) {
                $quote = $m[1] ?? '';
                $path = $m[2];
                if ($path === '' || strpos($path, $baseTrim . '/') === 0 || $path === $baseTrim) {
                    return $m[0];
                }

                return 'url(' . $quote . BASE_PATH . '/' . ltrim($path, '/') . $quote . ')';
            },
            $buffer
        );

        return $buffer;
    });
}
