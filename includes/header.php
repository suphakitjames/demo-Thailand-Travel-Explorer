<?php

/**
 * =====================================================
 * Header Template
 * =====================================================
 */

// เริ่ม session
start_session();

// ดึงข้อมูลผู้ใช้ปัจจุบัน
$currentUser = current_user();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo h(SITE_DESCRIPTION); ?>">
    <meta name="theme-color" content="#0ea5e9">

    <title><?php echo isset($pageTitle) ? h($pageTitle) . ' | ' : ''; ?><?php echo h(SITE_NAME); ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                        accent: {
                            50: '#fdf4ff',
                            100: '#fae8ff',
                            200: '#f5d0fe',
                            300: '#f0abfc',
                            400: '#e879f9',
                            500: '#d946ef',
                            600: '#c026d3',
                            700: '#a21caf',
                            800: '#86198f',
                            900: '#701a75',
                        }
                    },
                    fontFamily: {
                        'thai': ['Noto Sans Thai', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts - Noto Sans Thai -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">

    <style>
        body {
            font-family: 'Noto Sans Thai', sans-serif;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen">

    <!-- Navigation -->
    <nav class="bg-white/80 backdrop-blur-lg shadow-lg sticky top-0 z-50 border-b border-white/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <a href="<?php echo BASE_URL; ?>" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-accent-500 rounded-xl flex items-center justify-center transform group-hover:scale-110 transition-transform shadow-lg">
                        <i class="fas fa-umbrella-beach text-white text-lg"></i>
                    </div>
                    <span class="text-xl font-bold bg-gradient-to-r from-primary-600 to-accent-600 bg-clip-text text-transparent hidden sm:block">
                        เที่ยวไทย
                    </span>
                </a>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-1">
                    <a href="<?php echo BASE_URL; ?>" class="px-4 py-2 rounded-xl text-gray-700 hover:text-primary-600 hover:bg-primary-50 transition-all duration-200 flex items-center space-x-2">
                        <i class="fas fa-home"></i>
                        <span>หน้าแรก</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/public/search.php" class="px-4 py-2 rounded-xl text-gray-700 hover:text-primary-600 hover:bg-primary-50 transition-all duration-200 flex items-center space-x-2">
                        <i class="fas fa-search"></i>
                        <span>ค้นหา</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/public/nearby.php" class="px-4 py-2 rounded-xl text-gray-700 hover:text-primary-600 hover:bg-primary-50 transition-all duration-200 flex items-center space-x-2">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>ใกล้ฉัน</span>
                    </a>

                    <?php if (is_logged_in()): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/user/trips.php" class="px-4 py-2 rounded-xl text-gray-700 hover:text-primary-600 hover:bg-primary-50 transition-all duration-200 flex items-center space-x-2">
                            <i class="fas fa-route"></i>
                            <span>ทริปของฉัน</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/user/favorites.php" class="px-4 py-2 rounded-xl text-gray-700 hover:text-primary-600 hover:bg-primary-50 transition-all duration-200 flex items-center space-x-2">
                            <i class="fas fa-heart"></i>
                            <span>รายการโปรด</span>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Right Side -->
                <div class="flex items-center space-x-3">
                    <?php if (is_logged_in()): ?>
                        <!-- User Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 px-3 py-2 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="w-8 h-8 bg-gradient-to-br from-primary-400 to-accent-400 rounded-full flex items-center justify-center">
                                    <span class="text-white font-medium text-sm">
                                        <?php echo mb_substr($currentUser['full_name'], 0, 1); ?>
                                    </span>
                                </div>
                                <span class="hidden sm:block text-gray-700 font-medium">
                                    <?php echo h($currentUser['full_name']); ?>
                                </span>
                                <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                            </button>

                            <!-- Dropdown Menu -->
                            <div x-show="open" @click.away="open = false"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="transform opacity-100 scale-100"
                                x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 z-50">

                                <div class="px-4 py-3 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-900"><?php echo h($currentUser['full_name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo h($currentUser['email']); ?></p>
                                    <span class="inline-block mt-1 px-2 py-0.5 bg-primary-100 text-primary-600 text-xs rounded-full">
                                        <?php
                                        $roleLabels = ['user' => 'นักท่องเที่ยว', 'owner' => 'ผู้ประกอบการ', 'admin' => 'ผู้ดูแล'];
                                        echo $roleLabels[$currentUser['role']] ?? $currentUser['role'];
                                        ?>
                                    </span>
                                </div>

                                <a href="<?php echo BASE_URL; ?>/pages/user/profile.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-user w-5"></i>
                                    <span class="ml-2">โปรไฟล์</span>
                                </a>

                                <?php if (has_role('owner')): ?>
                                    <a href="<?php echo BASE_URL; ?>/pages/owner/dashboard.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50">
                                        <i class="fas fa-store w-5"></i>
                                        <span class="ml-2">จัดการสถานที่</span>
                                    </a>
                                <?php endif; ?>

                                <?php if (has_role('admin')): ?>
                                    <a href="<?php echo BASE_URL; ?>/pages/admin/dashboard.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50">
                                        <i class="fas fa-cog w-5"></i>
                                        <span class="ml-2">แผงควบคุม Admin</span>
                                    </a>
                                <?php endif; ?>

                                <div class="border-t border-gray-100 my-1"></div>

                                <a href="<?php echo BASE_URL; ?>/pages/user/logout.php" class="flex items-center px-4 py-2 text-red-600 hover:bg-red-50">
                                    <i class="fas fa-sign-out-alt w-5"></i>
                                    <span class="ml-2">ออกจากระบบ</span>
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/pages/user/login.php" class="px-4 py-2 text-gray-700 hover:text-primary-600 transition-colors font-medium">
                            เข้าสู่ระบบ
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/user/register.php" class="px-5 py-2.5 bg-gradient-to-r from-primary-500 to-accent-500 text-white rounded-xl hover:shadow-lg hover:shadow-primary-500/30 transition-all duration-300 font-medium">
                            สมัครสมาชิก
                        </a>
                    <?php endif; ?>

                    <!-- Mobile Menu Button -->
                    <button id="mobile-menu-btn" class="md:hidden p-2 rounded-xl hover:bg-gray-100 transition-colors">
                        <i class="fas fa-bars text-gray-600 text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="md:hidden hidden bg-white border-t">
            <div class="px-4 py-3 space-y-1">
                <a href="<?php echo BASE_URL; ?>" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-primary-50 hover:text-primary-600">
                    <i class="fas fa-home w-6"></i>
                    <span>หน้าแรก</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/public/search.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-primary-50 hover:text-primary-600">
                    <i class="fas fa-search w-6"></i>
                    <span>ค้นหา</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/public/nearby.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-primary-50 hover:text-primary-600">
                    <i class="fas fa-map-marker-alt w-6"></i>
                    <span>ที่เที่ยวใกล้ฉัน</span>
                </a>
                <?php if (is_logged_in()): ?>
                    <a href="<?php echo BASE_URL; ?>/pages/user/trips.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-primary-50 hover:text-primary-600">
                        <i class="fas fa-route w-6"></i>
                        <span>ทริปของฉัน</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/user/favorites.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-primary-50 hover:text-primary-600">
                        <i class="fas fa-heart w-6"></i>
                        <span>รายการโปรด</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="min-h-screen">
        <!-- Flash Messages -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
            <?php echo display_flash(); ?>
        </div>

        <!-- Alpine.js for dropdown -->
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>