<?php
/**
 * VAST STUDIO - Modern Streetwear & Minimalist Fashion
 * Core Configuration & Global Helpers
 */

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/database.php';

// Global App Constants
define('APP_NAME', 'VAST STUDIO');
define('APP_SLOGAN', 'Streetwear Core');
define('APP_TAGLINE', 'Modern Streetwear & Minimalist Aesthetics');
define('APP_HOTLINE', '0988.123.456');
define('APP_EMAIL', 'contact@vaststudio.vn');
define('APP_YEAR', date('Y'));
define('APP_VERSION', '2.0.0');

// Helper to check active navigation item
if (!function_exists('navActive')) {
    function navActive($current, $target) {
        return ($current === $target) ? 'active' : '';
    }
}

// Helper to format VND Currency
if (!function_exists('formatVND')) {
    function formatVND($amount) {
        return number_format((float)$amount, 0, ',', '.') . ' ₫';
    }
}
