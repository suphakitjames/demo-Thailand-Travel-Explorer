<?php

/**
 * =====================================================
 * Logout Handler
 * =====================================================
 */

require_once __DIR__ . '/../../config/config.php';
require_once INCLUDES_PATH . '/functions.php';

start_session();

// Get user info before destroying session
$userId = get_user_id();

// Log activity if user was logged in
if ($userId) {
    try {
        $logStmt = db()->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent)
            VALUES (?, 'logout', 'ออกจากระบบ', ?, ?)
        ");
        $logStmt->execute([$userId, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);

        // Clear remember token
        $clearToken = db()->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
        $clearToken->execute([$userId]);
    } catch (PDOException $e) {
        // Log error but don't block logout
        error_log("Logout logging error: " . $e->getMessage());
    }
}

// Clear remember me cookies
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/', '', false, true);
}

// Destroy session
$_SESSION = [];

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Start new session for flash message
start_session();
set_flash('success', 'ออกจากระบบเรียบร้อยแล้ว');

// Redirect to home
redirect(BASE_URL);
