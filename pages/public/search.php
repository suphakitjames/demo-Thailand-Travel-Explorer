<?php

/**
 * =====================================================
 * Search Page - ค้นหาสถานที่ท่องเที่ยว
 * =====================================================
 */

require_once __DIR__ . '/../../config/config.php';
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/PlaceModel.php';

start_session();

$placeModel = new PlaceModel();

// Get filters data
$filtersData = $placeModel->getFilters();

// Get search parameters
$keyword = trim(get('q', ''));
$categoryId = (int) get('category', 0);
$provinceId = (int) get('province', 0);
$moodId = (int) get('mood', 0);
$budgetId = (int) get('budget', 0);
$seasonId = (int) get('season', 0);
$rating = (float) get('rating', 0);
$orderBy = get('sort', 'popular');
$page = max(1, (int) get('page', 1));
$perPage = 12;

// Build filters
$filters = [];
if (!empty($keyword)) $filters['keyword'] = $keyword;
if ($categoryId > 0) $filters['category'] = $categoryId;
if ($provinceId > 0) $filters['province'] = $provinceId;
if ($moodId > 0) $filters['mood'] = $moodId;
if ($budgetId > 0) $filters['budget'] = $budgetId;
if ($seasonId > 0) $filters['season'] = $seasonId;
if ($rating > 0) $filters['rating'] = $rating;

// Get places
$offset = ($page - 1) * $perPage;
$places = $placeModel->search($filters, $perPage, $offset, $orderBy);
$totalPlaces = $placeModel->countAll($filters);
$totalPages = ceil($totalPlaces / $perPage);

// Build query string for pagination
$queryParams = $_GET;
unset($queryParams['page']);
$queryString = http_build_query($queryParams);

$pageTitle = $keyword ? 'ค้นหา: ' . $keyword : 'ค้นหาสถานที่ท่องเที่ยว';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <?php if (!empty($keyword)): ?>
                    ผลการค้นหา "<?php echo h($keyword); ?>"
                <?php else: ?>
                    ค้นหาสถานที่ท่องเที่ยว
                <?php endif; ?>
            </h1>
            <p class="text-gray-600">
                พบ <?php echo format_number($totalPlaces); ?> สถานที่
            </p>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filters Sidebar -->
            <aside class="lg:w-72 flex-shrink-0">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-24">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold text-gray-800">
                            <i class="fas fa-filter text-primary-500 mr-2"></i>ตัวกรอง
                        </h2>
                        <?php if (!empty($filters)): ?>
                            <a href="<?php echo BASE_URL; ?>/pages/public/search.php"
                                class="text-sm text-red-500 hover:text-red-600">
                                ล้างทั้งหมด
                            </a>
                        <?php endif; ?>
                    </div>

                    <form method="GET" action="" id="filterForm">
                        <!-- Keyword -->
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2">คำค้นหา</label>
                            <div class="relative">
                                <input type="text" name="q" value="<?php echo h($keyword); ?>"
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    placeholder="ค้นหาสถานที่...">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            </div>
                        </div>

                        <!-- Category -->
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2">หมวดหมู่</label>
                            <select name="category" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500">
                                <option value="">ทั้งหมด</option>
                                <?php foreach ($filtersData['categories'] as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $categoryId == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo h($cat['name_th']); ?> (<?php echo $cat['place_count']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Province -->
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2">จังหวัด</label>
                            <select name="province" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500">
                                <option value="">ทั้งหมด</option>
                                <?php foreach ($filtersData['provinces'] as $prov): ?>
                                    <option value="<?php echo $prov['id']; ?>" <?php echo $provinceId == $prov['id'] ? 'selected' : ''; ?>>
                                        <?php echo h($prov['name_th']); ?> (<?php echo $prov['place_count']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Mood -->
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2">อารมณ์การเที่ยว</label>
                            <select name="mood" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500">
                                <option value="">ทั้งหมด</option>
                                <?php foreach ($filtersData['moods'] as $mood): ?>
                                    <option value="<?php echo $mood['id']; ?>" <?php echo $moodId == $mood['id'] ? 'selected' : ''; ?>>
                                        <?php echo h($mood['icon'] . ' ' . $mood['name_th']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Budget -->
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2">งบประมาณ</label>
                            <select name="budget" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500">
                                <option value="">ทั้งหมด</option>
                                <?php foreach ($filtersData['budgets'] as $budget): ?>
                                    <option value="<?php echo $budget['id']; ?>" <?php echo $budgetId == $budget['id'] ? 'selected' : ''; ?>>
                                        <?php echo h($budget['name_th']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Season -->
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2">ฤดูกาล</label>
                            <select name="season" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500">
                                <option value="">ทั้งหมด</option>
                                <?php foreach ($filtersData['seasons'] as $season): ?>
                                    <option value="<?php echo $season['id']; ?>" <?php echo $seasonId == $season['id'] ? 'selected' : ''; ?>>
                                        <?php echo h($season['icon'] . ' ' . $season['name_th']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="w-full btn btn-gradient py-3">
                            <i class="fas fa-search mr-2"></i>ค้นหา
                        </button>
                    </form>
                </div>
            </aside>

            <!-- Results -->
            <main class="flex-1">
                <!-- Sort & View Options -->
                <div class="bg-white rounded-xl shadow p-4 mb-6 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600">เรียงตาม:</span>
                        <select name="sort" id="sortSelect"
                            class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 text-sm"
                            onchange="updateSort(this.value)">
                            <option value="popular" <?php echo $orderBy === 'popular' ? 'selected' : ''; ?>>ยอดนิยม</option>
                            <option value="rating" <?php echo $orderBy === 'rating' ? 'selected' : ''; ?>>คะแนนสูงสุด</option>
                            <option value="views" <?php echo $orderBy === 'views' ? 'selected' : ''; ?>>ดูมากสุด</option>
                            <option value="newest" <?php echo $orderBy === 'newest' ? 'selected' : ''; ?>>ใหม่ล่าสุด</option>
                            <option value="name" <?php echo $orderBy === 'name' ? 'selected' : ''; ?>>ชื่อ ก-ฮ</option>
                        </select>
                    </div>

                    <div class="text-sm text-gray-600">
                        แสดง <?php echo count($places); ?> จาก <?php echo format_number($totalPlaces); ?> รายการ
                    </div>
                </div>

                <!-- Places Grid -->
                <?php if (empty($places)): ?>
                    <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                        <i class="fas fa-map-marked-alt text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-bold text-gray-700 mb-2">ไม่พบสถานที่</h3>
                        <p class="text-gray-500 mb-4">ลองเปลี่ยนคำค้นหาหรือตัวกรอง</p>
                        <a href="<?php echo BASE_URL; ?>/pages/public/search.php" class="btn btn-primary">
                            ล้างตัวกรอง
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($places as $place): ?>
                            <a href="<?php echo BASE_URL; ?>/pages/public/place.php?slug=<?php echo h($place['slug']); ?>"
                                class="place-card group">
                                <!-- Image -->
                                <div class="relative h-48 overflow-hidden">
                                    <img src="<?php echo h($place['thumbnail'] ?: 'https://via.placeholder.com/400x300?text=No+Image'); ?>"
                                        alt="<?php echo h($place['name_th']); ?>"
                                        class="w-full h-full object-cover"
                                        loading="lazy">

                                    <!-- Category Badge -->
                                    <div class="absolute top-3 left-3">
                                        <span class="px-3 py-1 bg-white/90 backdrop-blur rounded-full text-xs font-medium text-gray-700">
                                            <i class="fas <?php echo h($place['category_icon'] ?: 'fa-map-pin'); ?> mr-1 text-primary-500"></i>
                                            <?php echo h($place['category_name'] ?? 'ไม่ระบุ'); ?>
                                        </span>
                                    </div>

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

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav class="mt-8 flex justify-center">
                            <ul class="flex items-center gap-2">
                                <?php if ($page > 1): ?>
                                    <li>
                                        <a href="?<?php echo $queryString; ?>&page=<?php echo $page - 1; ?>"
                                            class="px-4 py-2 bg-white rounded-lg shadow hover:bg-gray-50 text-gray-600">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);

                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <li>
                                        <a href="?<?php echo $queryString; ?>&page=<?php echo $i; ?>"
                                            class="px-4 py-2 rounded-lg shadow <?php echo $i === $page ? 'bg-primary-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <li>
                                        <a href="?<?php echo $queryString; ?>&page=<?php echo $page + 1; ?>"
                                            class="px-4 py-2 bg-white rounded-lg shadow hover:bg-gray-50 text-gray-600">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>

<script>
    function updateSort(value) {
        const url = new URL(window.location);
        url.searchParams.set('sort', value);
        url.searchParams.delete('page');
        window.location = url;
    }
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>