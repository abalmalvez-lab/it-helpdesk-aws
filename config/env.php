<?php
/**
 * Environment Configuration Loader
 * Supports .env file AND system-level environment variables (AWS EC2/Lambda).
 * On AWS, env vars set via /etc/environment or systemd take priority.
 */

function loadEnv($path = null) {
    if ($path === null) {
        $path = dirname(__DIR__) . '/.env';
    }

    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;

        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if (preg_match('/^"(.*)"$/', $value, $m)) $value = $m[1];
        if (preg_match("/^'(.*)'$/", $value, $m)) $value = $m[1];

        // System env vars (AWS-set) take priority
        if (!getenv($key)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
    return true;
}

loadEnv();
