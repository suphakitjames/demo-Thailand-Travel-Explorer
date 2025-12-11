<?php
/**
 * =====================================================
 * ระบบค้นหาสถานที่ท่องเที่ยว อัจฉริยะ
 * Main Configuration File
 * =====================================================
 */

// ป้องกันการเข้าถึงโดยตรง
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// ======================
// Site Settings
// ======================
define('SITE_NAME', 'ระบบค้นหาสถานที่ท่องเที่ยว อัจฉริยะ');
define('SITE_DESCRIPTION', 'ค้นหาที่เที่ยวทั่วไทย พร้อมรีวิวและวางแผนทริป');
define('SITE_URL', 'http://localhost/new_traval');
define('BASE_URL', SITE_URL);

// ======================
// Directory Paths
// ======================
define('CONFIG_PATH', BASE_PATH . '/config');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('PAGES_PATH', BASE_PATH . '/pages');
define('UPLOADS_PATH', BASE_PATH . '/uploads');

// ======================
// Timezone
// ======================
date_default_timezone_set('Asia/Bangkok');

// ======================
// Session Settings
// ======================
define('SESSION_NAME', 'tourism_session');
define('SESSION_LIFETIME', 3600 * 24 * 7); // 7 days
define('SESSION_PATH', '/');
define('SESSION_SECURE', false); // Set to true in production with HTTPS
define('SESSION_HTTPONLY', true);

// ======================
// Security Settings
// ======================
define('CSRF_TOKEN_NAME', 'csrf_token');
define('HASH_ALGO', PASSWORD_BCRYPT);
define('HASH_COST', 10);

// ======================
// Pagination
// ======================
define('ITEMS_PER_PAGE', 12);

// ======================
// Upload Settings
// ======================
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// ======================
// Error Reporting (Development)
// ======================
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ======================
// Include Database Config
// ======================
require_once CONFIG_PATH . '/database.php';
require_once CONFIG_PATH . '/api_config.php';
