<?php

/**
 * =====================================================
 * Login Page
 * =====================================================
 */

require_once __DIR__ . '/../../config/config.php';
require_once INCLUDES_PATH . '/functions.php';

start_session();

// Redirect if already logged in
if (is_logged_in()) {
    redirect(BASE_URL);
}

$errors = [];
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf(post(CSRF_TOKEN_NAME))) {
        $errors[] = 'การยืนยันตัวตนล้มเหลว กรุณาลองใหม่อีกครั้ง';
    } else {
        $email = trim(post('email', ''));
        $password = post('password', '');
        $remember = post('remember') ? true : false;

        // Validation
        if (empty($email)) {
            $errors[] = 'กรุณากรอกอีเมล';
        } elseif (!is_valid_email($email)) {
            $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
        }

        if (empty($password)) {
            $errors[] = 'กรุณากรอกรหัสผ่าน';
        }

        // If no validation errors, attempt login
        if (empty($errors)) {
            try {
                $stmt = db()->prepare("
                    SELECT id, username, email, password, full_name, role, status 
                    FROM users 
                    WHERE email = ?
                ");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Check if user is active
                    if ($user['status'] !== 'active') {
                        if ($user['status'] === 'banned') {
                            $errors[] = 'บัญชีของคุณถูกระงับการใช้งาน';
                        } else {
                            $errors[] = 'บัญชีของคุณยังไม่ได้เปิดใช้งาน';
                        }
                    } else {
                        // Login successful
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_name'] = $user['full_name'];

                        // Update last login
                        $updateStmt = db()->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
                        $updateStmt->execute([$user['id']]);

                        // Regenerate session ID for security
                        session_regenerate_id(true);

                        // Log activity
                        $logStmt = db()->prepare("
                            INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
                            VALUES (?, 'login', 'เข้าสู่ระบบสำเร็จ', ?, ?)
                        ");
                        $logStmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);

                        // Set success message
                        set_flash('success', 'ยินดีต้อนรับ, ' . $user['full_name'] . '!');

                        // Handle remember me
                        if ($remember) {
                            $token = bin2hex(random_bytes(32));
                            $tokenHash = password_hash($token, PASSWORD_BCRYPT);

                            $tokenStmt = db()->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                            $tokenStmt->execute([$tokenHash, $user['id']]);

                            setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
                            setcookie('remember_user', $user['id'], time() + (86400 * 30), '/', '', false, true);
                        }

                        // Redirect to intended URL or home
                        $redirectUrl = $_SESSION['redirect_after_login'] ?? BASE_URL;
                        unset($_SESSION['redirect_after_login']);
                        redirect($redirectUrl);
                    }
                } else {
                    $errors[] = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
                }
            } catch (PDOException $e) {
                $errors[] = 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';
                if (DEBUG_MODE) {
                    $errors[] = $e->getMessage();
                }
            }
        }
    }

    // Regenerate CSRF token after failed attempt
    regenerate_csrf();
}

$pageTitle = 'เข้าสู่ระบบ';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 -mt-16">
    <div class="max-w-md w-full">
        <!-- Login Card -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-primary-500 to-accent-500 px-8 py-10 text-center">
                <div class="w-20 h-20 bg-white/20 backdrop-blur rounded-2xl mx-auto flex items-center justify-center mb-4">
                    <i class="fas fa-user-lock text-white text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-white">เข้าสู่ระบบ</h1>
                <p class="text-white/80 mt-2">ยินดีต้อนรับกลับ! กรุณาเข้าสู่ระบบ</p>
            </div>

            <!-- Form -->
            <div class="px-8 py-8">
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                            <div class="ml-3">
                                <?php foreach ($errors as $error): ?>
                                    <p class="text-red-700 text-sm"><?php echo h($error); ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" data-validate>
                    <?php echo csrf_field(); ?>

                    <!-- Email -->
                    <div class="mb-5">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope text-gray-400 mr-2"></i>อีเมล
                        </label>
                        <input type="email" id="email" name="email"
                            class="form-input"
                            placeholder="your@email.com"
                            value="<?php echo h($email); ?>"
                            required>
                    </div>

                    <!-- Password -->
                    <div class="mb-5">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock text-gray-400 mr-2"></i>รหัสผ่าน
                        </label>
                        <div class="relative">
                            <input type="password" id="password" name="password"
                                class="form-input pr-12"
                                placeholder="••••••••"
                                required>
                            <button type="button" onclick="togglePassword('password')"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye" id="password-toggle-icon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me & Forgot -->
                    <div class="flex items-center justify-between mb-6">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="remember"
                                class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <span class="ml-2 text-sm text-gray-600">จดจำฉัน</span>
                        </label>
                        <a href="#" class="text-sm text-primary-600 hover:text-primary-700">
                            ลืมรหัสผ่าน?
                        </a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full btn btn-gradient py-3.5 text-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        เข้าสู่ระบบ
                    </button>
                </form>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white text-gray-500">หรือ</span>
                    </div>
                </div>

                <!-- Demo Accounts -->
                <div class="bg-gray-50 rounded-xl p-4 mb-4">
                    <p class="text-xs text-gray-500 mb-2 text-center">บัญชีทดสอบ (password: password123)</p>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" onclick="fillDemo('admin@tourism.com')"
                            class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs hover:bg-gray-100 transition">
                            Admin
                        </button>
                        <button type="button" onclick="fillDemo('owner@demo.com')"
                            class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs hover:bg-gray-100 transition">
                            Owner
                        </button>
                        <button type="button" onclick="fillDemo('user@demo.com')"
                            class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs hover:bg-gray-100 transition">
                            User
                        </button>
                    </div>
                </div>

                <!-- Register Link -->
                <p class="text-center text-gray-600">
                    ยังไม่มีบัญชี?
                    <a href="<?php echo BASE_URL; ?>/pages/user/register.php"
                        class="text-primary-600 hover:text-primary-700 font-medium">
                        สมัครสมาชิก
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(inputId + '-toggle-icon');

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function fillDemo(email) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = 'password123';
    }
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>