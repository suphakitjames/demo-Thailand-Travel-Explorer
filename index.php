<?php

/**
 * =====================================================
 * ระบบค้นหาสถานที่ท่องเที่ยว อัจฉริยะ
 * Home Page / Entry Point
 * =====================================================
 */

require_once __DIR__ . '/config/config.php';
require_once INCLUDES_PATH . '/functions.php';

start_session();

// Fetch featured places
try {
    $featuredStmt = db()->prepare("
        SELECT p.*, pr.name_th as province_name, c.name_th as category_name, c.icon as category_icon
        FROM places p
        LEFT JOIN provinces pr ON p.province_id = pr.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'approved' AND p.is_featured = 1
        ORDER BY p.popularity_score DESC
        LIMIT 8
    ");
    $featuredStmt->execute();
    $featuredPlaces = $featuredStmt->fetchAll();
} catch (PDOException $e) {
    $featuredPlaces = [];
    if (DEBUG_MODE) {
        set_flash('error', 'Database Error: ' . $e->getMessage());
    }
}

// Fetch categories
try {
    $categoriesStmt = db()->query("
        SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, id ASC
    ");
    $categories = $categoriesStmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Fetch popular provinces
try {
    $provincesStmt = db()->query("
        SELECT pr.*, COUNT(p.id) as place_count
        FROM provinces pr
        LEFT JOIN places p ON pr.id = p.province_id AND p.status = 'approved'
        GROUP BY pr.id
        HAVING place_count > 0
        ORDER BY place_count DESC
        LIMIT 6
    ");
    $popularProvinces = $provincesStmt->fetchAll();
} catch (PDOException $e) {
    $popularProvinces = [];
}

$pageTitle = null; // Use default site name
require_once INCLUDES_PATH . '/header.php';
?>

<!-- Hero Section - Enhanced -->
<section class="relative overflow-hidden min-h-[85vh] flex items-center">
    <!-- Animated Gradient Background -->
    <div class="absolute inset-0 animated-gradient"></div>
    <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1552733407-5d5c46c3bb3b?w=1920')] bg-cover bg-center opacity-15 mix-blend-overlay"></div>

    <!-- Floating Decorative Shapes -->
    <div class="absolute top-20 left-10 w-72 h-72 bg-white/10 rounded-full blur-3xl floating-shape-1"></div>
    <div class="absolute bottom-10 right-10 w-96 h-96 bg-accent-500/20 rounded-full blur-3xl floating-shape-2"></div>
    <div class="absolute top-1/2 left-1/4 w-20 h-20 bg-yellow-300/30 rounded-full blur-xl floating-shape-3"></div>
    <div class="absolute top-1/3 right-1/4 w-16 h-16 bg-white/20 rounded-lg rotate-45 floating-shape-2"></div>
    <div class="absolute bottom-1/4 left-1/3 w-12 h-12 bg-accent-300/30 rounded-full floating-shape-1"></div>

    <!-- Floating Icons -->
    <div class="absolute top-32 right-20 text-6xl opacity-20 floating-shape-1 hidden lg:block">🏝️</div>
    <div class="absolute bottom-32 left-20 text-5xl opacity-20 floating-shape-2 hidden lg:block">⛰️</div>
    <div class="absolute top-1/2 right-32 text-4xl opacity-15 floating-shape-3 hidden lg:block">🌴</div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-32 w-full">
        <div class="text-center">
            <!-- Animated Badge -->
            <div class="inline-flex items-center px-5 py-2.5 bg-white/20 backdrop-blur-md rounded-full text-white text-sm mb-8 reveal-scale shimmer">
                <i class="fas fa-sparkles mr-2 text-yellow-300 icon-float"></i>
                <span>ค้นหาสถานที่ท่องเที่ยวทั่วไทย</span>
                <i class="fas fa-sparkles ml-2 text-yellow-300 icon-float"></i>
            </div>

            <!-- Animated Title -->
            <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold text-white mb-8 leading-tight reveal">
                <span class="inline-block animate-pulse">🌏</span>
                เที่ยวไทย<br class="sm:hidden">
                <span class="bg-gradient-to-r from-yellow-300 via-orange-300 to-pink-300 bg-clip-text text-transparent inline-block">
                    ไปด้วยกัน
                </span>
            </h1>

            <p class="text-xl md:text-2xl text-white/90 mb-12 max-w-3xl mx-auto leading-relaxed reveal" style="animation-delay: 0.2s">
                ค้นหาสถานที่ท่องเที่ยว อ่านรีวิวจากผู้ใช้จริง<br class="hidden sm:block">
                วางแผนทริป และนำทางไปยังจุดหมายอย่างง่ายดาย
            </p>

            <!-- Enhanced Search Box -->
            <form action="<?php echo BASE_URL; ?>/pages/public/search.php" method="GET" class="max-w-2xl mx-auto relative reveal" id="searchForm" style="animation-delay: 0.4s">
                <div class="search-box search-box-enhanced flex items-center p-2 pl-6 glow-hover">
                    <i class="fas fa-search text-gray-400 text-xl"></i>
                    <input type="text" name="q" id="searchInput"
                        placeholder="ค้นหาสถานที่ เช่น ดอยอินทนนท์, ภูเก็ต, วัด..."
                        class="flex-1 px-4 py-3 border-none focus:outline-none text-gray-700 text-lg bg-transparent"
                        autocomplete="off">
                    <button type="submit"
                        class="px-8 py-3 bg-gradient-to-r from-primary-500 to-accent-500 text-white rounded-full font-medium hover:shadow-lg transition-all pulse-glow">
                        <i class="fas fa-search mr-2"></i>ค้นหา
                    </button>
                </div>

                <!-- Autocomplete Dropdown -->
                <div id="searchResults" class="absolute top-full left-0 right-0 mt-2 bg-white rounded-2xl shadow-2xl z-50 hidden overflow-hidden max-h-96 overflow-y-auto">
                    <!-- Results will be populated by JavaScript -->
                </div>
            </form>

            <!-- Quick Links with Animation -->
            <div class="flex flex-wrap justify-center gap-3 mt-10 reveal" style="animation-delay: 0.6s">
                <a href="<?php echo BASE_URL; ?>/pages/public/search.php?category=1"
                    class="quick-link-pill px-5 py-2.5 bg-white/20 backdrop-blur rounded-full text-white text-sm hover:bg-white/30 transition-all flex items-center gap-2">
                    <span class="text-lg">🏔️</span> ภูเขา
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/public/search.php?category=2"
                    class="quick-link-pill px-5 py-2.5 bg-white/20 backdrop-blur rounded-full text-white text-sm hover:bg-white/30 transition-all flex items-center gap-2">
                    <span class="text-lg">🏖️</span> ทะเล
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/public/search.php?category=3"
                    class="quick-link-pill px-5 py-2.5 bg-white/20 backdrop-blur rounded-full text-white text-sm hover:bg-white/30 transition-all flex items-center gap-2">
                    <span class="text-lg">🛕</span> วัด
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/public/search.php?category=5"
                    class="quick-link-pill px-5 py-2.5 bg-white/20 backdrop-blur rounded-full text-white text-sm hover:bg-white/30 transition-all flex items-center gap-2">
                    <span class="text-lg">☕</span> คาเฟ่
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/public/nearby.php"
                    class="quick-link-pill px-5 py-2.5 bg-white/25 backdrop-blur rounded-full text-white text-sm hover:bg-white/35 transition-all flex items-center gap-2 border border-white/30">
                    <i class="fas fa-location-dot text-yellow-300"></i> ใกล้ฉัน
                </a>
            </div>

            <!-- Scroll Indicator -->
            <div class="absolute bottom-8 left-1/2 -translate-x-1/2 text-white/60 animate-bounce hidden md:block">
                <i class="fas fa-chevron-down text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Wave Bottom -->
    <div class="absolute bottom-0 left-0 right-0">
        <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full">
            <path d="M0 120L60 110C120 100 240 80 360 70C480 60 600 60 720 65C840 70 960 80 1080 85C1200 90 1320 90 1380 90L1440 90V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z" class="fill-slate-50" />
        </svg>
    </div>
</section>

<!-- Categories Section - Enhanced -->
<section class="py-20 -mt-8" id="categories-section">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14 reveal">
            <span class="inline-block px-4 py-2 bg-primary-100 text-primary-600 rounded-full text-sm font-medium mb-4">
                <i class="fas fa-th-large mr-2"></i>หมวดหมู่
            </span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">หมวดหมู่ยอดนิยม</h2>
            <p class="text-gray-600 text-lg">เลือกหมวดหมู่ที่คุณสนใจเพื่อเริ่มต้นการค้นหา</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6" id="categories-grid">
            <?php foreach ($categories as $index => $category): ?>
                <a href="<?php echo BASE_URL; ?>/pages/public/search.php?category=<?php echo $category['id']; ?>"
                    class="stagger-item group category-card-animated bg-white rounded-2xl p-6 shadow-lg text-center glow-hover relative overflow-hidden"
                    style="transition-delay: <?php echo $index * 0.1; ?>s">
                    <!-- Background Gradient on Hover -->
                    <div class="absolute inset-0 bg-gradient-to-br from-primary-50 to-accent-50 opacity-0 group-hover:opacity-100 transition-opacity"></div>

                    <div class="relative z-10">
                        <div class="category-icon w-16 h-16 bg-gradient-to-br from-primary-100 to-accent-100 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:shadow-lg transition-all">
                            <i class="fas <?php echo h($category['icon'] ?: 'fa-map-pin'); ?> text-2xl text-primary-600"></i>
                        </div>
                        <h3 class="font-semibold text-gray-800 group-hover:text-primary-600 transition-colors">
                            <?php echo h($category['name_th']); ?>
                        </h3>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Places Section - Enhanced -->
<section class="py-20 bg-white" id="featured-section">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-14 reveal">
            <div>
                <span class="inline-block px-4 py-2 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium mb-4">
                    <i class="fas fa-fire mr-2"></i>แนะนำ
                </span>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-2">สถานที่แนะนำ</h2>
                <p class="text-gray-600 text-lg">สถานที่ท่องเที่ยวยอดนิยมที่คุณต้องไป</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/pages/public/search.php"
                class="hidden sm:flex items-center gap-2 px-5 py-2.5 bg-primary-50 text-primary-600 hover:bg-primary-100 rounded-full font-medium transition-all">
                ดูทั้งหมด
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <?php if (empty($featuredPlaces)): ?>
            <div class="text-center py-16 bg-gray-50 rounded-2xl">
                <i class="fas fa-map-marked-alt text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">ยังไม่มีสถานที่แนะนำในขณะนี้</p>
                <p class="text-gray-400 text-sm mt-2">กรุณาเพิ่มข้อมูลสถานที่ท่องเที่ยวในระบบ</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6" id="places-grid">
                <?php foreach ($featuredPlaces as $index => $place): ?>
                    <a href="<?php echo BASE_URL; ?>/pages/public/place.php?slug=<?php echo h($place['slug']); ?>"
                        class="place-card place-card-enhanced stagger-item group relative"
                        style="transition-delay: <?php echo $index * 0.1; ?>s">
                        <!-- Image -->
                        <div class="relative h-48 overflow-hidden">
                            <img src="<?php echo h($place['thumbnail'] ?: 'https://via.placeholder.com/400x300?text=No+Image'); ?>"
                                alt="<?php echo h($place['name_th']); ?>"
                                class="w-full h-full object-cover">

                            <!-- Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>

                            <!-- Category Badge -->
                            <div class="absolute top-3 left-3">
                                <span class="px-3 py-1 bg-white/90 backdrop-blur rounded-full text-xs font-medium text-gray-700">
                                    <i class="fas <?php echo h($place['category_icon'] ?: 'fa-map-pin'); ?> mr-1 text-primary-500"></i>
                                    <?php echo h($place['category_name'] ?? 'ไม่ระบุ'); ?>
                                </span>
                            </div>

                            <!-- Featured Badge -->
                            <?php if ($place['is_featured']): ?>
                                <div class="absolute top-3 right-3">
                                    <span class="px-2 py-1 bg-yellow-400 rounded-full text-xs font-bold text-yellow-900">
                                        <i class="fas fa-star mr-1"></i>แนะนำ
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Content -->
                        <div class="p-5">
                            <h3 class="font-bold text-gray-800 text-lg mb-1 group-hover:text-primary-600 transition-colors line-clamp-1">
                                <?php echo h($place['name_th']); ?>
                            </h3>

                            <p class="text-gray-500 text-sm mb-3 flex items-center">
                                <i class="fas fa-map-marker-alt text-primary-400 mr-1"></i>
                                <?php echo h($place['province_name'] ?? 'ไม่ระบุจังหวัด'); ?>
                            </p>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="rating-stars">
                                        <?php
                                        $rating = round($place['avg_rating'] * 2) / 2;
                                        for ($i = 1; $i <= 5; $i++):
                                            if ($i <= $rating):
                                        ?>
                                                <i class="fas fa-star text-sm"></i>
                                            <?php elseif ($i - 0.5 <= $rating): ?>
                                                <i class="fas fa-star-half-alt text-sm"></i>
                                            <?php else: ?>
                                                <i class="far fa-star text-sm empty"></i>
                                        <?php endif;
                                        endfor; ?>
                                    </div>
                                    <span class="ml-2 text-gray-600 text-sm">
                                        <?php echo number_format($place['avg_rating'], 1); ?>
                                    </span>
                                </div>

                                <span class="text-gray-400 text-xs">
                                    <i class="fas fa-eye mr-1"></i>
                                    <?php echo format_number($place['view_count']); ?>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Mobile View All -->
        <div class="text-center mt-8 sm:hidden">
            <a href="<?php echo BASE_URL; ?>/pages/public/search.php"
                class="inline-flex items-center text-primary-600 font-medium">
                ดูสถานที่ทั้งหมด
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Popular Provinces Section - Enhanced -->
<section class="py-20 bg-gradient-to-br from-gray-50 via-white to-blue-50" id="provinces-section">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14 reveal">
            <span class="inline-block px-4 py-2 bg-blue-100 text-blue-600 rounded-full text-sm font-medium mb-4">
                <i class="fas fa-map mr-2"></i>จังหวัด
            </span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">จังหวัดยอดนิยม</h2>
            <p class="text-gray-600 text-lg">สำรวจสถานที่ท่องเที่ยวตามจังหวัดที่คุณสนใจ</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 md:gap-6" id="provinces-grid">
            <?php foreach ($popularProvinces as $index => $province): ?>
                <a href="<?php echo BASE_URL; ?>/pages/public/search.php?province=<?php echo $province['id']; ?>"
                    class="stagger-item group province-card-pop relative bg-white rounded-2xl p-6 shadow-lg text-center glow-hover overflow-hidden"
                    style="transition-delay: <?php echo $index * 0.1; ?>s">
                    <div class="relative z-10">
                        <div class="province-emoji text-5xl mb-4">
                            <?php
                            // Province icons based on region
                            $regionIcons = [
                                'north' => '🏔️',
                                'northeast' => '🌾',
                                'central' => '🏛️',
                                'east' => '🏖️',
                                'west' => '🌲',
                                'south' => '🌴'
                            ];
                            echo $regionIcons[$province['region']] ?? '📍';
                            ?>
                        </div>
                        <h3 class="font-bold text-gray-800 group-hover:text-primary-600 transition-colors">
                            <?php echo h($province['name_th']); ?>
                        </h3>
                        <p class="text-sm text-gray-500 mt-2 flex items-center justify-center gap-1">
                            <i class="fas fa-location-dot text-primary-400"></i>
                            <?php echo format_number($province['place_count']); ?> สถานที่
                        </p>
                    </div>

                    <!-- Hover Effect -->
                    <div class="absolute inset-0 bg-gradient-to-br from-primary-500/10 to-accent-500/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Features Section - Enhanced -->
<section class="py-20 bg-white" id="features-section">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14 reveal">
            <span class="inline-block px-4 py-2 bg-purple-100 text-purple-600 rounded-full text-sm font-medium mb-4">
                <i class="fas fa-magic mr-2"></i>คุณสมบัติ
            </span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">ทำไมต้องใช้เรา?</h2>
            <p class="text-gray-600 text-lg max-w-2xl mx-auto">เครื่องมือที่ช่วยให้การเที่ยวไทยของคุณง่ายขึ้นอย่างที่ไม่เคยมีมาก่อน</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8" id="features-grid">
            <!-- Feature 1 -->
            <div class="feature-card-slide text-center p-8 rounded-2xl bg-gradient-to-br from-slate-50 to-blue-50 shadow-lg hover:shadow-xl transition-all group glow-hover">
                <div class="w-20 h-20 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg group-hover:scale-110 transition-transform icon-float">
                    <i class="fas fa-search text-3xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">ค้นหาอัจฉริยะ</h3>
                <p class="text-gray-600">ค้นหาสถานที่ตามอารมณ์ งบประมาณ และฤดูกาล</p>
            </div>

            <!-- Feature 2 -->
            <div class="feature-card-slide text-center p-8 rounded-2xl bg-gradient-to-br from-slate-50 to-green-50 shadow-lg hover:shadow-xl transition-all group glow-hover">
                <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-green-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg group-hover:scale-110 transition-transform icon-float">
                    <i class="fas fa-map-marker-alt text-3xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">ที่เที่ยวใกล้ฉัน</h3>
                <p class="text-gray-600">ค้นหาสถานที่ใกล้ตำแหน่งปัจจุบันของคุณ</p>
            </div>

            <!-- Feature 3 -->
            <div class="feature-card-slide text-center p-8 rounded-2xl bg-gradient-to-br from-slate-50 to-yellow-50 shadow-lg hover:shadow-xl transition-all group glow-hover">
                <div class="w-20 h-20 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg group-hover:scale-110 transition-transform icon-float">
                    <i class="fas fa-star text-3xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">รีวิวจริง</h3>
                <p class="text-gray-600">อ่านรีวิวจากนักท่องเที่ยวที่ไปมาจริง</p>
            </div>

            <!-- Feature 4 -->
            <div class="feature-card-slide text-center p-8 rounded-2xl bg-gradient-to-br from-slate-50 to-purple-50 shadow-lg hover:shadow-xl transition-all group glow-hover">
                <div class="w-20 h-20 bg-gradient-to-br from-purple-400 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg group-hover:scale-110 transition-transform icon-float">
                    <i class="fas fa-route text-3xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3">วางแผนทริป</h3>
                <p class="text-gray-600">สร้างทริปและคำนวณเส้นทางอัตโนมัติ</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-gradient-to-r from-primary-600 to-accent-600 relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1920')] bg-cover bg-center opacity-10"></div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
            พร้อมเริ่มต้นการเดินทางของคุณหรือยัง?
        </h2>
        <p class="text-xl text-white/80 mb-8">
            สมัครสมาชิกฟรี เพื่อบันทึกสถานที่โปรด วางแผนทริป และแชร์ประสบการณ์
        </p>

        <?php if (!is_logged_in()): ?>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="<?php echo BASE_URL; ?>/pages/user/register.php"
                    class="px-8 py-4 bg-white text-primary-600 rounded-xl font-bold text-lg hover:shadow-lg hover:shadow-white/30 transition-all">
                    <i class="fas fa-user-plus mr-2"></i>
                    สมัครสมาชิกฟรี
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/public/search.php"
                    class="px-8 py-4 bg-white/20 backdrop-blur text-white rounded-xl font-bold text-lg hover:bg-white/30 transition-all">
                    <i class="fas fa-search mr-2"></i>
                    เริ่มค้นหาเลย
                </a>
            </div>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>/pages/user/trips.php"
                class="inline-flex items-center px-8 py-4 bg-white text-primary-600 rounded-xl font-bold text-lg hover:shadow-lg transition-all">
                <i class="fas fa-route mr-2"></i>
                วางแผนทริปใหม่
            </a>
        <?php endif; ?>
    </div>
</section>

<!-- Real-time Search Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        let debounceTimer;

        if (!searchInput || !searchResults) return;

        searchInput.addEventListener('input', function() {
            const query = this.value.trim();

            clearTimeout(debounceTimer);

            if (query.length < 1) {
                searchResults.classList.add('hidden');
                return;
            }

            debounceTimer = setTimeout(() => {
                fetchResults(query);
            }, 300);
        });

        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 1) {
                searchResults.classList.remove('hidden');
            }
        });

        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.add('hidden');
            }
        });

        async function fetchResults(query) {
            try {
                const response = await fetch(`<?php echo BASE_URL; ?>/api/search.php?q=${encodeURIComponent(query)}&limit=8`);
                const data = await response.json();

                if (data.success && data.results.length > 0) {
                    renderResults(data.results);
                    searchResults.classList.remove('hidden');
                } else {
                    searchResults.innerHTML = `
                    <div class="p-6 text-center text-gray-500">
                        <i class="fas fa-search text-gray-300 text-3xl mb-2"></i>
                        <p>ไม่พบผลลัพธ์ที่ตรงกับ "${query}"</p>
                    </div>
                `;
                    searchResults.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Search error:', error);
            }
        }

        function renderResults(results) {
            let html = '';

            results.forEach(item => {
                if (item.type === 'place') {
                    html += `
                    <a href="${item.url}" class="flex items-center gap-4 p-4 hover:bg-gray-50 transition-colors border-b border-gray-100 last:border-0">
                        <img src="${item.thumbnail}" alt="" class="w-14 h-14 rounded-lg object-cover flex-shrink-0">
                        <div class="flex-1 min-w-0">
                            <h4 class="font-medium text-gray-800 truncate">${item.name}</h4>
                            <p class="text-sm text-gray-500 flex items-center">
                                <i class="fas fa-map-marker-alt text-primary-400 mr-1"></i>
                                ${item.province}
                            </p>
                        </div>
                        <span class="px-3 py-1 bg-gray-100 rounded-full text-xs text-gray-600">
                            <i class="fas ${item.icon} mr-1"></i>${item.category}
                        </span>
                    </a>
                `;
                } else {
                    html += `
                    <a href="${item.url}" class="flex items-center gap-4 p-4 hover:bg-gray-50 transition-colors border-b border-gray-100 last:border-0">
                        <div class="w-14 h-14 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-map text-2xl text-blue-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-medium text-gray-800 truncate">${item.name}</h4>
                            <p class="text-sm text-gray-500">ดูสถานที่ทั้งหมดในจังหวัดนี้</p>
                        </div>
                        <span class="px-3 py-1 bg-blue-100 rounded-full text-xs text-blue-600">
                            จังหวัด
                        </span>
                    </a>
                `;
                }
            });

            html += `
            <div class="p-3 bg-gray-50 text-center">
                <button type="submit" class="text-primary-600 hover:text-primary-700 font-medium text-sm">
                    <i class="fas fa-search mr-1"></i> ดูผลลัพธ์ทั้งหมด
                </button>
            </div>
        `;

            searchResults.innerHTML = html;
        }
    });
</script>

<!-- Scroll Progress & Back to Top -->
<div class="scroll-progress" id="scrollProgress"></div>
<button class="back-to-top" id="backToTop">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Enhanced Animation Scripts -->
<script>
    (function() {
        'use strict';

        // Scroll Progress Indicator
        const scrollProgress = document.getElementById('scrollProgress');

        // Back to Top Button
        const backToTop = document.getElementById('backToTop');

        // Intersection Observer for Reveal Animations
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };

        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, observerOptions);

        // Observe all reveal elements
        document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale, .stagger-item, .feature-card-slide').forEach(el => {
            revealObserver.observe(el);
        });

        // Scroll Event Handler (debounced)
        let ticking = false;

        function onScroll() {
            const scrollTop = window.scrollY;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const scrollPercent = (scrollTop / docHeight) * 100;

            // Update scroll progress bar
            if (scrollProgress) {
                scrollProgress.style.width = scrollPercent + '%';
            }

            // Show/hide back to top button
            if (backToTop) {
                if (scrollTop > 500) {
                    backToTop.classList.add('visible');
                } else {
                    backToTop.classList.remove('visible');
                }
            }
        }

        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    onScroll();
                    ticking = false;
                });
                ticking = true;
            }
        });

        // Back to Top Click Handler
        if (backToTop) {
            backToTop.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }

        // 3D Tilt Effect for Cards (optional - performance optimized)
        const tiltCards = document.querySelectorAll('.place-card-enhanced');

        tiltCards.forEach(card => {
            card.addEventListener('mousemove', function(e) {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                const centerX = rect.width / 2;
                const centerY = rect.height / 2;

                const rotateX = (y - centerY) / 20;
                const rotateY = (centerX - x) / 20;

                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-15px) scale(1.02)`;
            });

            card.addEventListener('mouseleave', function() {
                card.style.transform = '';
            });
        });

        // Initial activation of elements in viewport
        setTimeout(() => {
            document.querySelectorAll('.reveal, .reveal-scale').forEach(el => {
                const rect = el.getBoundingClientRect();
                if (rect.top < window.innerHeight) {
                    el.classList.add('active');
                }
            });
        }, 100);

        console.log('🏖️ เที่ยวไทย ไปด้วยกัน - UI Enhanced!');
    })();
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>