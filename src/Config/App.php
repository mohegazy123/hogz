<?php

namespace App\Config;

class App {
    private static $config = [];

    public static function init() {
        self::$config = [
            'app_name' => $_ENV['APP_NAME'] ?? 'ERP System',
            'app_url' => $_ENV['APP_URL'] ?? 'http://localhost',
            'app_debug' => $_ENV['APP_DEBUG'] ?? true,
            'app_secret' => $_ENV['APP_SECRET'] ?? 'default_secret_key',
            'session_lifetime' => $_ENV['SESSION_LIFETIME'] ?? 120
        ];

        // Set error reporting based on debug mode
        if (self::$config['app_debug']) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        } else {
            error_reporting(0);
            ini_set('display_errors', 0);
        }

        // Initialize session
        session_start([
            'cookie_lifetime' => self::$config['session_lifetime'] * 60,
            'cookie_httponly' => true,
            'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'use_strict_mode' => true
        ]);
    }

    public static function get($key, $default = null) {
        return self::$config[$key] ?? $default;
    }
}