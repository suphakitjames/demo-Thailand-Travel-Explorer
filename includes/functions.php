<?php

/**
 * =====================================================
 * Helper Functions
 * =====================================================
 */

// ======================
// Security Functions
// ======================

/**
 * Sanitize output to prevent XSS
 * @param string $string
 * @return string
 */
function h($string)
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF Token
 * @return string
 */
function csrf_token()
{
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Generate CSRF Token Input Field
 * @return string HTML input field
 */
function csrf_field()
{
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . csrf_token() . '">';
}

/**
 * Verify CSRF Token
 * @param string $token Token from form
 * @return bool
 */
function verify_csrf($token)
{
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Regenerate CSRF Token
 */
function regenerate_csrf()
{
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
}

// ======================
// Session & Auth Functions
// ======================

/**
 * Start secure session
 */
function start_session()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => SESSION_PATH,
            'secure' => SESSION_SECURE,
            'httponly' => SESSION_HTTPONLY,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

/**
 * Check if user is logged in
 * @return bool
 */
function is_logged_in()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged in user
 * @return array|null User data or null
 */
function current_user()
{
    if (!is_logged_in()) {
        return null;
    }

    static $user = null;

    if ($user === null) {
        $stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }

    return $user;
}

/**
 * Get current user ID
 * @return int|null
 */
function get_user_id()
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Check if current user has specific role
 * @param string|array $roles
 * @return bool
 */
function has_role($roles)
{
    $user = current_user();
    if (!$user) return false;

    if (is_string($roles)) {
        $roles = [$roles];
    }

    return in_array($user['role'], $roles);
}

/**
 * Require login - redirect if not logged in
 * @param string $redirect URL to redirect after login
 */
function require_login($redirect = null)
{
    if (!is_logged_in()) {
        if ($redirect) {
            $_SESSION['redirect_after_login'] = $redirect;
        }
        redirect(BASE_URL . '/pages/user/login.php');
    }
}

/**
 * Require specific role
 * @param string|array $roles Required roles
 */
function require_role($roles)
{
    require_login();

    if (!has_role($roles)) {
        set_flash('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        redirect(BASE_URL);
    }
}

// ======================
// Redirect & URL Functions
// ======================

/**
 * Safe redirect
 * @param string $url URL to redirect
 * @param int $statusCode HTTP status code
 */
function redirect($url, $statusCode = 302)
{
    header("Location: " . $url, true, $statusCode);
    exit;
}

/**
 * Get current URL
 * @return string
 */
function current_url()
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Generate asset URL
 * @param string $path Path relative to assets folder
 * @return string
 */
function asset($path)
{
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

// ======================
// Flash Messages
// ======================

/**
 * Set flash message
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message content
 */
function set_flash($type, $message)
{
    $_SESSION['flash_messages'][$type][] = $message;
}

/**
 * Get flash messages
 * @param string|null $type Specific type or null for all
 * @return array
 */
function get_flash($type = null)
{
    if ($type) {
        $messages = $_SESSION['flash_messages'][$type] ?? [];
        unset($_SESSION['flash_messages'][$type]);
        return $messages;
    }

    $messages = $_SESSION['flash_messages'] ?? [];
    $_SESSION['flash_messages'] = [];
    return $messages;
}

/**
 * Check if there are flash messages
 * @param string|null $type
 * @return bool
 */
function has_flash($type = null)
{
    if ($type) {
        return !empty($_SESSION['flash_messages'][$type]);
    }
    return !empty($_SESSION['flash_messages']);
}

/**
 * Display flash messages with Tailwind CSS styling
 * @return string HTML
 */
function display_flash()
{
    $output = '';
    $messages = get_flash();

    $styles = [
        'success' => 'bg-green-100 border-green-500 text-green-700',
        'error' => 'bg-red-100 border-red-500 text-red-700',
        'warning' => 'bg-yellow-100 border-yellow-500 text-yellow-700',
        'info' => 'bg-blue-100 border-blue-500 text-blue-700'
    ];

    $icons = [
        'success' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
        'error' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
        'warning' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
        'info' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>'
    ];

    foreach ($messages as $type => $msgs) {
        $style = $styles[$type] ?? $styles['info'];
        $icon = $icons[$type] ?? $icons['info'];

        foreach ($msgs as $msg) {
            $output .= '<div class="border-l-4 p-4 mb-4 ' . $style . '" role="alert">';
            $output .= '<div class="flex items-center">';
            $output .= '<div class="flex-shrink-0">' . $icon . '</div>';
            $output .= '<div class="ml-3"><p class="text-sm">' . h($msg) . '</p></div>';
            $output .= '</div></div>';
        }
    }

    return $output;
}

// ======================
// Formatting Functions
// ======================

/**
 * Format date in Thai
 * @param string $date
 * @param string $format
 * @return string
 */
function format_date_thai($date, $format = 'd M Y')
{
    $thaiMonths = [
        1 => 'ม.ค.',
        2 => 'ก.พ.',
        3 => 'มี.ค.',
        4 => 'เม.ย.',
        5 => 'พ.ค.',
        6 => 'มิ.ย.',
        7 => 'ก.ค.',
        8 => 'ส.ค.',
        9 => 'ก.ย.',
        10 => 'ต.ค.',
        11 => 'พ.ย.',
        12 => 'ธ.ค.'
    ];

    $timestamp = strtotime($date);
    $day = date('j', $timestamp);
    $month = $thaiMonths[(int)date('n', $timestamp)];
    $year = date('Y', $timestamp) + 543; // Buddhist Era

    return "$day $month $year";
}

/**
 * Format number with commas
 * @param float $number
 * @param int $decimals
 * @return string
 */
function format_number($number, $decimals = 0)
{
    return number_format($number, $decimals, '.', ',');
}

/**
 * Format price in Thai Baht
 * @param float $amount
 * @return string
 */
function format_price($amount)
{
    if ($amount == 0) {
        return 'ฟรี';
    }
    return '฿' . format_number($amount);
}

/**
 * Generate slug from text
 * @param string $text
 * @return string
 */
function generate_slug($text)
{
    $text = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    return strtolower($text);
}

// ======================
// Validation Functions
// ======================

/**
 * Validate email
 * @param string $email
 * @return bool
 */
function is_valid_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate URL
 * @param string $url
 * @return bool
 */
function is_valid_url($url)
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Get POST data safely
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function post($key, $default = null)
{
    return $_POST[$key] ?? $default;
}

/**
 * Get GET data safely
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function get($key, $default = null)
{
    return $_GET[$key] ?? $default;
}
