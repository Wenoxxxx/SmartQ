<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load .env if not already loaded
if (!isset($_SERVER['GOOGLE_CLIENT_ID']) && !isset($_ENV['GOOGLE_CLIENT_ID'])) {
    try {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();
    } catch (\Exception $e) {
        // Silently fail if .env not found
    }
}

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', $_SERVER['GOOGLE_CLIENT_ID'] ?? $_ENV['GOOGLE_CLIENT_ID'] ?? '');
define('GOOGLE_CLIENT_SECRET', $_SERVER['GOOGLE_CLIENT_SECRET'] ?? $_ENV['GOOGLE_CLIENT_SECRET'] ?? '');

// Google reCAPTCHA v2 (Checkbox)
define('RECAPTCHA_SITE_KEY', $_SERVER['RECAPTCHA_SITE_KEY'] ?? $_ENV['RECAPTCHA_SITE_KEY'] ?? '');
define('RECAPTCHA_SECRET_KEY', $_SERVER['RECAPTCHA_SECRET_KEY'] ?? $_ENV['RECAPTCHA_SECRET_KEY'] ?? '');
?>
