<?php

/**
 * =====================================================
 * Admin Dashboard - แผงควบคุม Admin
 * =====================================================
 */

require_once __DIR__ . '/../../config/config.php';
require_once INCLUDES_PATH . '/functions.php';

start_session();
require_login();
require_role('admin');

// Get stats from database
$db = db();

// Users stats
$usersStats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'banned' THEN 1 ELSE 0 END) as banned,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
        SUM(CASE WHEN role = 'owner' THEN 1 ELSE 0 END) as owners,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today
    FROM users
")->fetch();

// Places stats
$placesStats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM places
")->fetch();

// Reviews stats
$reviewsStats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'spam' THEN 1 ELSE 0 END) as spam,
        AVG(rating_overall) as avg_rating
    FROM reviews
")->fetch();

// Trips stats
$tripsStats = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_public = 1 THEN 1 ELSE 0 END) as public_trips
    FROM trips
")->fetch();

// Recent activities
$recentActivities = $db->query("
    SELECT al.*, u.full_name 
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 10
")->fetchAll();

// Recent users
$recentUsers = $db->query("
    SELECT * FROM users ORDER BY created_at DESC LIMIT 5
")->fetchAll();

// Recent reviews
$recentReviews = $db->query("
    SELECT r.*, u.full_name, p.name_th as place_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN places p ON r.place_id = p.id
    ORDER BY r.created_at DESC
    LIMIT 5
")->fetchAll();

$pageTitle = 'แผงควบคุม Admin';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="bg-gray-100 min-h-screen">
    <!-- Admin Header -->
    <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">
                        <i class="fas fa-tachometer-alt mr-3"></i>แผงควบคุม Admin
                    </h1>
                    <p class="text-gray-300 mt-1">ยินดีต้อนรับ, <?php echo h(current_user()['full_name']); ?></p>
                </div>
                <div class="text-right text-sm text-gray-400">
                    <p><?php echo date('l, j F Y'); ?></p>
                    <p><?php echo date('H:i'); ?> น.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Users -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 uppercase tracking-wider">ผู้ใช้ทั้งหมด</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($usersStats['total']); ?></p>
                        <p class="text-sm text-green-600 mt-1">
                            <i class="fas fa-arrow-up mr-1"></i><?php echo $usersStats['today']; ?> วันนี้
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-users text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>

            <!-- Places -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 uppercase tracking-wider">สถานที่</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($placesStats['total']); ?></p>
                        <?php if ($placesStats['pending'] > 0): ?>
                            <p class="text-sm text-yellow-600 mt-1">
                                <i class="fas fa-clock mr-1"></i><?php echo $placesStats['pending']; ?> รอตรวจสอบ
                            </p>
                        <?php else: ?>
                            <p class="text-sm text-green-600 mt-1">
                                <i class="fas fa-check mr-1"></i>อนุมัติแล้วทั้งหมด
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-map-marker-alt text-2xl text-green-600"></i>
                    </div>
                </div>
            </div>

            <!-- Reviews -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 uppercase tracking-wider">รีวิว</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($reviewsStats['total']); ?></p>
                        <p class="text-sm text-gray-500 mt-1">
                            <i class="fas fa-star text-yellow-400 mr-1"></i>
                            <?php echo number_format($reviewsStats['avg_rating'] ?? 0, 1); ?> คะแนนเฉลี่ย
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-yellow-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-comments text-2xl text-yellow-600"></i>
                    </div>
                </div>
            </div>

            <!-- Trips -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 uppercase tracking-wider">ทริป</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($tripsStats['total']); ?></p>
                        <p class="text-sm text-purple-600 mt-1">
                            <i class="fas fa-globe mr-1"></i><?php echo $tripsStats['public_trips']; ?> สาธารณะ
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-route text-2xl text-purple-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Pending -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-bolt text-yellow-500 mr-2"></i>การดำเนินการด่วน
                </h2>
                <div class="space-y-3">
                    <a href="<?php echo BASE_URL; ?>/pages/admin/users.php"
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                        <span class="flex items-center">
                            <i class="fas fa-users w-8 text-blue-500"></i>
                            <span>จัดการผู้ใช้</span>
                        </span>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/admin/places.php"
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                        <span class="flex items-center">
                            <i class="fas fa-map-marker-alt w-8 text-green-500"></i>
                            <span>จัดการสถานที่</span>
                        </span>
                        <?php if ($placesStats['pending'] > 0): ?>
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">
                                <?php echo $placesStats['pending']; ?>
                            </span>
                        <?php else: ?>
                            <i class="fas fa-chevron-right text-gray-400"></i>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/admin/reviews.php"
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                        <span class="flex items-center">
                            <i class="fas fa-comments w-8 text-yellow-500"></i>
                            <span>จัดการรีวิว</span>
                        </span>
                        <?php if ($reviewsStats['pending'] > 0): ?>
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">
                                <?php echo $reviewsStats['pending']; ?>
                            </span>
                        <?php else: ?>
                            <i class="fas fa-chevron-right text-gray-400"></i>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- User Stats -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-user-tag text-blue-500 mr-2"></i>สถิติผู้ใช้
                </h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">นักท่องเที่ยว</span>
                        <span class="font-bold"><?php echo number_format($usersStats['total'] - $usersStats['admins'] - $usersStats['owners']); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">ผู้ประกอบการ</span>
                        <span class="font-bold"><?php echo number_format($usersStats['owners']); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Admin</span>
                        <span class="font-bold"><?php echo number_format($usersStats['admins']); ?></span>
                    </div>
                    <hr>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Active</span>
                        <span class="font-bold text-green-600"><?php echo number_format($usersStats['active']); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Banned</span>
                        <span class="font-bold text-red-600"><?php echo number_format($usersStats['banned']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Content Stats -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-chart-pie text-green-500 mr-2"></i>สถิติเนื้อหา
                </h2>
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-600">สถานที่อนุมัติ</span>
                            <span class="text-sm font-bold"><?php echo $placesStats['approved']; ?>/<?php echo $placesStats['total']; ?></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full"
                                style="width: <?php echo $placesStats['total'] > 0 ? ($placesStats['approved'] / $placesStats['total'] * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-600">รีวิวอนุมัติ</span>
                            <span class="text-sm font-bold"><?php echo $reviewsStats['approved']; ?>/<?php echo $reviewsStats['total']; ?></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full"
                                style="width: <?php echo $reviewsStats['total'] > 0 ? ($reviewsStats['approved'] / $reviewsStats['total'] * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Data -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Users -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-800">
                        <i class="fas fa-user-plus text-blue-500 mr-2"></i>ผู้ใช้ใหม่
                    </h2>
                    <a href="<?php echo BASE_URL; ?>/pages/admin/users.php" class="text-sm text-primary-600 hover:underline">
                        ดูทั้งหมด
                    </a>
                </div>
                <div class="space-y-3">
                    <?php foreach ($recentUsers as $user): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-primary-400 to-accent-400 rounded-full flex items-center justify-center text-white font-bold">
                                    <?php echo mb_substr($user['full_name'], 0, 1); ?>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800"><?php echo h($user['full_name']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo h($user['email']); ?></p>
                                </div>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full 
                                <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-700' : ($user['role'] === 'owner' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700'); ?>">
                                <?php echo $user['role']; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Reviews -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-800">
                        <i class="fas fa-comment-dots text-yellow-500 mr-2"></i>รีวิวล่าสุด
                    </h2>
                    <a href="<?php echo BASE_URL; ?>/pages/admin/reviews.php" class="text-sm text-primary-600 hover:underline">
                        ดูทั้งหมด
                    </a>
                </div>
                <div class="space-y-3">
                    <?php foreach ($recentReviews as $review): ?>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium text-gray-800"><?php echo h($review['full_name']); ?></span>
                                <div class="rating-stars text-xs">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $review['rating_overall']): ?>
                                            <i class="fas fa-star text-yellow-400"></i>
                                        <?php else: ?>
                                            <i class="far fa-star text-gray-300"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 line-clamp-2"><?php echo h($review['content']); ?></p>
                            <p class="text-xs text-gray-400 mt-1">
                                <i class="fas fa-map-marker-alt mr-1"></i><?php echo h($review['place_name']); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>