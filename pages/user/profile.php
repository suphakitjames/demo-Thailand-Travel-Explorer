<?php

/**
 * =====================================================
 * User Profile - โปรไฟล์ผู้ใช้
 * =====================================================
 */

require_once __DIR__ . '/../../config/config.php';
require_once INCLUDES_PATH . '/functions.php';

start_session();
require_login();

$db = db();
$userId = get_user_id();

// Get user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('error', 'ไม่พบข้อมูลผู้ใช้');
    redirect(BASE_URL);
}

// Handle form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(post(CSRF_TOKEN_NAME))) {
        $errors[] = 'การยืนยันตัวตนล้มเหลว';
    } else {
        $action = post('action', '');

        // Update Profile
        if ($action === 'update_profile') {
            $fullName = trim(post('full_name', ''));
            $phone = trim(post('phone', ''));

            if (empty($fullName)) {
                $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
            }

            if (empty($errors)) {
                $stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ?, updated_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$fullName, $phone, $userId]);

                if ($result) {
                    $success = 'อัปเดตโปรไฟล์สำเร็จ';
                    // Refresh user data
                    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                } else {
                    $errors[] = 'ไม่สามารถอัปเดตได้';
                }
            }
        }

        // Change Password
        if ($action === 'change_password') {
            $currentPassword = post('current_password', '');
            $newPassword = post('new_password', '');
            $confirmPassword = post('confirm_password', '');

            if (empty($currentPassword)) {
                $errors[] = 'กรุณากรอกรหัสผ่านปัจจุบัน';
            } elseif (!password_verify($currentPassword, $user['password'])) {
                $errors[] = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
            }

            if (empty($newPassword)) {
                $errors[] = 'กรุณากรอกรหัสผ่านใหม่';
            } elseif (strlen($newPassword) < 6) {
                $errors[] = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร';
            }

            if ($newPassword !== $confirmPassword) {
                $errors[] = 'รหัสผ่านใหม่ไม่ตรงกัน';
            }

            if (empty($errors)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                $result = $stmt->execute([$hashedPassword, $userId]);

                if ($result) {
                    $success = 'เปลี่ยนรหัสผ่านสำเร็จ';
                } else {
                    $errors[] = 'ไม่สามารถเปลี่ยนรหัสผ่านได้';
                }
            }
        }
    }
    regenerate_csrf();
}

// Get user stats
$reviewCount = $db->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
$reviewCount->execute([$userId]);
$reviewCount = $reviewCount->fetchColumn();

$tripCount = $db->prepare("SELECT COUNT(*) FROM trips WHERE user_id = ?");
$tripCount->execute([$userId]);
$tripCount = $tripCount->fetchColumn();

$placeCount = 0;
if ($user['role'] === 'owner') {
    $placeCount = $db->prepare("SELECT COUNT(*) FROM places WHERE owner_id = ?");
    $placeCount->execute([$userId]);
    $placeCount = $placeCount->fetchColumn();
}

$pageTitle = 'โปรไฟล์';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Profile Header -->
        <div class="bg-gradient-to-r from-primary-500 to-accent-500 rounded-3xl p-8 text-white mb-8 shadow-xl">
            <div class="flex flex-col md:flex-row items-center gap-6">
                <div class="w-24 h-24 bg-white/20 backdrop-blur rounded-full flex items-center justify-center text-4xl font-bold">
                    <?php echo mb_substr($user['full_name'], 0, 1); ?>
                </div>
                <div class="text-center md:text-left">
                    <h1 class="text-3xl font-bold"><?php echo h($user['full_name']); ?></h1>
                    <p class="text-white/80 mt-1"><?php echo h($user['email']); ?></p>
                    <div class="flex flex-wrap justify-center md:justify-start gap-2 mt-3">
                        <?php
                        $roleLabels = ['user' => 'นักท่องเที่ยว', 'owner' => 'ผู้ประกอบการ', 'admin' => 'ผู้ดูแลระบบ'];
                        $roleColors = ['user' => 'bg-blue-500', 'owner' => 'bg-green-500', 'admin' => 'bg-purple-500'];
                        ?>
                        <span class="px-3 py-1 <?php echo $roleColors[$user['role']] ?? 'bg-gray-500'; ?> rounded-full text-sm">
                            <?php echo $roleLabels[$user['role']] ?? $user['role']; ?>
                        </span>
                        <span class="px-3 py-1 bg-white/20 rounded-full text-sm">
                            สมาชิกตั้งแต่ <?php echo format_date_thai($user['created_at'], 'd M Y'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-2xl p-5 shadow-lg text-center">
                <i class="fas fa-comments text-2xl text-yellow-500 mb-2"></i>
                <p class="text-2xl font-bold text-gray-800"><?php echo $reviewCount; ?></p>
                <p class="text-sm text-gray-500">รีวิว</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-lg text-center">
                <i class="fas fa-route text-2xl text-purple-500 mb-2"></i>
                <p class="text-2xl font-bold text-gray-800"><?php echo $tripCount; ?></p>
                <p class="text-sm text-gray-500">ทริป</p>
            </div>
            <?php if ($user['role'] === 'owner'): ?>
                <div class="bg-white rounded-2xl p-5 shadow-lg text-center">
                    <i class="fas fa-map-marker-alt text-2xl text-green-500 mb-2"></i>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $placeCount; ?></p>
                    <p class="text-sm text-gray-500">สถานที่</p>
                </div>
            <?php endif; ?>
            <div class="bg-white rounded-2xl p-5 shadow-lg text-center">
                <i class="fas fa-heart text-2xl text-red-500 mb-2"></i>
                <p class="text-2xl font-bold text-gray-800">0</p>
                <p class="text-sm text-gray-500">รายการโปรด</p>
            </div>
        </div>

        <!-- Messages -->
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

        <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-r-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500"></i>
                    <p class="ml-3 text-green-700"><?php echo h($success); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Update Profile -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-user-edit text-primary-500 mr-2"></i>แก้ไขโปรไฟล์
                </h2>

                <form method="POST" action="">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="update_profile">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล</label>
                        <input type="text" name="full_name" value="<?php echo h($user['full_name']); ?>" required
                            class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
                        <input type="email" value="<?php echo h($user['email']); ?>" disabled
                            class="w-full px-4 py-3 border rounded-xl bg-gray-50 text-gray-500">
                        <p class="text-xs text-gray-400 mt-1">ไม่สามารถเปลี่ยนอีเมลได้</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์</label>
                        <input type="tel" name="phone" value="<?php echo h($user['phone'] ?? ''); ?>"
                            class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <button type="submit" class="w-full btn btn-gradient py-3">
                        <i class="fas fa-save mr-2"></i>บันทึกการเปลี่ยนแปลง
                    </button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-lock text-primary-500 mr-2"></i>เปลี่ยนรหัสผ่าน
                </h2>

                <form method="POST" action="">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่านปัจจุบัน</label>
                        <input type="password" name="current_password" required
                            class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่านใหม่</label>
                        <input type="password" name="new_password" required minlength="6"
                            class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">ยืนยันรหัสผ่านใหม่</label>
                        <input type="password" name="confirm_password" required
                            class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <button type="submit" class="w-full bg-gray-800 hover:bg-gray-900 text-white py-3 rounded-xl transition-colors">
                        <i class="fas fa-key mr-2"></i>เปลี่ยนรหัสผ่าน
                    </button>
                </form>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="mt-8 bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-link text-primary-500 mr-2"></i>ลิงก์ด่วน
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="<?php echo BASE_URL; ?>/pages/user/trips.php" class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                    <i class="fas fa-route text-purple-500 text-xl"></i>
                    <span>ทริปของฉัน</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/user/favorites.php" class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                    <i class="fas fa-heart text-red-500 text-xl"></i>
                    <span>รายการโปรด</span>
                </a>
                <?php if ($user['role'] === 'owner'): ?>
                    <a href="<?php echo BASE_URL; ?>/pages/owner/dashboard.php" class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                        <i class="fas fa-store text-green-500 text-xl"></i>
                        <span>จัดการสถานที่</span>
                    </a>
                <?php endif; ?>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="<?php echo BASE_URL; ?>/pages/admin/dashboard.php" class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                        <i class="fas fa-cog text-purple-500 text-xl"></i>
                        <span>แผงควบคุม Admin</span>
                    </a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>/pages/user/logout.php" class="flex items-center gap-3 p-4 bg-red-50 rounded-xl hover:bg-red-100 transition-colors text-red-600">
                    <i class="fas fa-sign-out-alt text-xl"></i>
                    <span>ออกจากระบบ</span>
                </a>
            </div>
        </div>

    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>