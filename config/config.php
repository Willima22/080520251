<?php
/**
 * Configuration file
 * Contains application constants and settings
 */

// Error reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Session configuration
session_start();

// Timezone settings
date_default_timezone_set('America/Sao_Paulo'); // Brazil timezone for display

// Application constants
define('APP_NAME', 'AW7 Postagens');
define('APP_VERSION', '1.0.0');
define('APP_URL', (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']);
define('WEBHOOK_URL', 'https://automacao2.aw7agencia.com.br/webhook/agendarpostagem');

// File upload settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 1073741824); // 1GB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/mov', 'video/avi']);

// Create upload directory if not exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Function to convert Brazil time to UTC
function convertToUTC($date, $time) {
    $datetime = new DateTime($date . ' ' . $time, new DateTimeZone('America/Sao_Paulo'));
    $datetime->setTimezone(new DateTimeZone('UTC'));
    return $datetime->format('Y-m-d\TH:i:s\Z'); // ISO 8601 format
}

// Function to convert UTC to Brazil time
function convertToBrazilTime($utcDateTime) {
    $datetime = new DateTime($utcDateTime, new DateTimeZone('UTC'));
    $datetime->setTimezone(new DateTimeZone('America/Sao_Paulo'));
    return $datetime->format('Y-m-d H:i:s');
}

// Function to sanitize inputs
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit;
}

// Flash message handling
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Administrador';
}
