<?php

/**
 * =====================================================
 * Register Page
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
$success = false;
$formData = [
    'full_name' => '',
    'username' => '',
    'email' => '',
    'phone' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf(post(CSRF_TOKEN_NAME))) {
        $errors[] = 'การยืนยันตัวตนล้มเหลว กรุณาลองใหม่อีกครั้ง';
    } else {
        // Get form data
        $formData['full_name'] = trim(post('full_name', ''));
        $formData['username'] = trim(post('username', ''));
        $formData['email'] = trim(post('email', ''));
        $formData['phone'] = trim(post('phone', ''));
        $password = post('password', '');
        $passwordConfirm = post('password_confirm', '');

        // Validation
        if (empty($formData['full_name'])) {
            $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
        } elseif (mb_strlen($formData['full_name']) < 3) {
            $errors[] = 'ชื่อ-นามสกุลต้องมีอย่างน้อย 3 ตัวอักษร';
        }

        if (empty($formData['username'])) {
            $errors[] = 'กรุณากรอกชื่อผู้ใช้';
        } elseif (strlen($formData['username']) < 3) {
            $errors[] = 'ชื่อผู้ใช้ต้องมีอย่างน้อย 3 ตัวอักษร';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $formData['username'])) {
            $errors[] = 'ชื่อผู้ใช้ต้องเป็นภาษาอังกฤษ ตัวเลข หรือ _ เท่านั้น';
        }

        if (empty($formData['email'])) {
            $errors[] = 'กรุณากรอกอีเมล';
        } elseif (!is_valid_email($formData['email'])) {
            $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
        }

        if (!empty($formData['phone']) && !preg_match('/^[0-9]{9,10}$/', $formData['phone'])) {
            $errors[] = 'เบอร์โทรศัพท์ไม่ถูกต้อง';
        }

        if (empty($password)) {
            $errors[] = 'กรุณากรอกรหัสผ่าน';
        } elseif (strlen($password) < 8) {
            $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'รหัสผ่านไม่ตรงกัน';
        }

        // Check if username or email already exists
        if (empty($errors)) {
            try {
                $checkStmt = db()->prepare("
                    SELECT id FROM users 
                    WHERE username = ? OR email = ?
                ");
                $checkStmt->execute([$formData['username'], $formData['email']]);

                if ($checkStmt->fetch()) {
                    // Check which one exists
                    $checkUsername = db()->prepare("SELECT id FROM users WHERE username = ?");
                    $checkUsername->execute([$formData['username']]);
                    if ($checkUsername->fetch()) {
                        $errors[] = 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว';
                    }

                    $checkEmail = db()->prepare("SELECT id FROM users WHERE email = ?");
                    $checkEmail->execute([$formData['email']]);
                    if ($checkEmail->fetch()) {
                        $errors[] = 'อีเมลนี้ถูกใช้งานแล้ว';
                    }
                }
            } catch (PDOException $e) {
                $errors[] = 'เกิดข้อผิดพลาดในการตรวจสอบข้อมูล';
                if (DEBUG_MODE) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        // Create user if no errors
        if (empty($errors)) {
            try {
                $hashedPassword = password_hash($password, HASH_ALGO, ['cost' => HASH_COST]);

                $insertStmt = db()->prepare("
                    INSERT INTO users (username, email, password, full_name, phone, role, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'user', 'active', NOW())
                ");

                $insertStmt->execute([
                    $formData['username'],
                    $formData['email'],
                    $hashedPassword,
                    $formData['full_name'],
                    $formData['phone'] ?: null
                ]);

                $userId = db()->lastInsertId();

                // Log activity
                $logStmt = db()->prepare("
                    INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent)
                    VALUES (?, 'register', 'สมัครสมาชิกใหม่', ?, ?)
                ");
                $logStmt->execute([$userId, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);

                // Auto login after registration
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_role'] = 'user';
                $_SESSION['user_name'] = $formData['full_name'];

                session_regenerate_id(true);

                set_flash('success', 'สมัครสมาชิกสำเร็จ! ยินดีต้อนรับสู่ระบบ');
                redirect(BASE_URL);
            } catch (PDOException $e) {
                $errors[] = 'เกิดข้อผิดพลาดในการสมัครสมาชิก กรุณาลองใหม่อีกครั้ง';
                if (DEBUG_MODE) {
                    $errors[] = $e->getMessage();
                }
            }
        }
    }

    // Regenerate CSRF token after failed attempt
    regenerate_csrf();
}

$pageTitle = 'สมัครสมาชิก';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 -mt-16">
    <div class="max-w-md w-full">
        <!-- Register Card -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-accent-500 to-primary-500 px-8 py-10 text-center">
                <div class="w-20 h-20 bg-white/20 backdrop-blur rounded-2xl mx-auto flex items-center justify-center mb-4">
                    <i class="fas fa-user-plus text-white text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-white">สมัครสมาชิก</h1>
                <p class="text-white/80 mt-2">สร้างบัญชีใหม่เพื่อเริ่มต้นการเดินทาง</p>
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

                    <!-- Full Name -->
                    <div class="mb-4">
                        <label for="full_name" class="form-label">
                            <i class="fas fa-user text-gray-400 mr-2"></i>ชื่อ-นามสกุล <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="full_name" name="full_name"
                            class="form-input"
                            placeholder="สมชาย ใจดี"
                            value="<?php echo h($formData['full_name']); ?>"
                            required minlength="3">
                    </div>

                    <!-- Username -->
                    <div class="mb-4">
                        <label for="username" class="form-label">
                            <i class="fas fa-at text-gray-400 mr-2"></i>ชื่อผู้ใช้ <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="username" name="username"
                            class="form-input"
                            placeholder="somchai123"
                            value="<?php echo h($formData['username']); ?>"
                            pattern="[a-zA-Z0-9_]+"
                            required minlength="3">
                        <p class="text-xs text-gray-500 mt-1">ภาษาอังกฤษ ตัวเลข หรือ _ เท่านั้น</p>
                    </div>

                    <!-- Email -->
                    <div class="mb-4">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope text-gray-400 mr-2"></i>อีเมล <span class="text-red-500">*</span>
                        </label>
                        <input type="email" id="email" name="email"
                            class="form-input"
                            placeholder="your@email.com"
                            value="<?php echo h($formData['email']); ?>"
                            required>
                    </div>

                    <!-- Phone (Optional) -->
                    <div class="mb-4">
                        <label for="phone" class="form-label">
                            <i class="fas fa-phone text-gray-400 mr-2"></i>เบอร์โทรศัพท์
                        </label>
                        <input type="tel" id="phone" name="phone"
                            class="form-input"
                            placeholder="0812345678"
                            value="<?php echo h($formData['phone']); ?>"
                            pattern="[0-9]{9,10}">
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock text-gray-400 mr-2"></i>รหัสผ่าน <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" id="password" name="password"
                                class="form-input pr-12"
                                placeholder="••••••••"
                                required minlength="8">
                            <button type="button" onclick="togglePassword('password')"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye" id="password-toggle-icon"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">อย่างน้อย 8 ตัวอักษร</p>
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-6">
                        <label for="password_confirm" class="form-label">
                            <i class="fas fa-lock text-gray-400 mr-2"></i>ยืนยันรหัสผ่าน <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" id="password_confirm" name="password_confirm"
                                class="form-input pr-12"
                                placeholder="••••••••"
                                required>
                            <button type="button" onclick="togglePassword('password_confirm')"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye" id="password_confirm-toggle-icon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Terms -->
                    <div class="mb-6">
                        <label class="flex items-start cursor-pointer">
                            <input type="checkbox" name="terms" required
                                class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500 mt-0.5">
                            <span class="ml-2 text-sm text-gray-600">
                                ฉันยอมรับ
                                <a href="#" class="text-primary-600 hover:underline">เงื่อนไขการใช้งาน</a>
                                และ
                                <a href="#" class="text-primary-600 hover:underline">นโยบายความเป็นส่วนตัว</a>
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full btn btn-gradient py-3.5 text-lg">
                        <i class="fas fa-user-plus mr-2"></i>
                        สมัครสมาชิก
                    </button>
                </form>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white text-gray-500">มีบัญชีแล้ว?</span>
                    </div>
                </div>

                <!-- Login Link -->
                <a href="<?php echo BASE_URL; ?>/pages/user/login.php"
                    class="w-full btn btn-outline py-3 flex items-center justify-center">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    เข้าสู่ระบบ
                </a>
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
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>