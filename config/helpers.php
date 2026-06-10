<?php
if (!defined('BASE_PATH')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $basePath = rtrim($scriptDir, '/');
    define('BASE_PATH', $basePath);
}

function url (string $path): string {
    $path =ltrim($path, '/');
    return htmlspecialchars(BASE_PATH . '/' . $path, ENT_QUOTES, 'UTF-8');
}

function asset(string $path): string {
    return url($path);
}