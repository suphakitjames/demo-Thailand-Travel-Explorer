<?php

/**
 * =====================================================
 * Place Detail Page - หน้ารายละเอียดสถานที่
 * =====================================================
 */

require_once __DIR__ . '/../../config/config.php';
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/PlaceModel.php';
require_once INCLUDES_PATH . '/ReviewModel.php';

start_session();

$placeModel = new PlaceModel();

// Get place by slug
$slug = get('slug', '');
if (empty($slug)) {
    set_flash('error', 'ไม่พบสถานที่ที่ต้องการ');
    redirect(BASE_URL . '/pages/public/search.php');
}

$place = $placeModel->getBySlug($slug);

if (!$place) {
    set_flash('error', 'ไม่พบสถานที่ที่ต้องการ');
    redirect(BASE_URL . '/pages/public/search.php');
}

// Get reviews using ReviewModel (includes images)
$reviewModel = new ReviewModel();
$reviews = $reviewModel->getByPlaceId($place['id'], 5);
$reviewCount = $reviewModel->countByPlaceId($place['id']);

// Get related places
$relatedPlaces = $placeModel->getRelated($place['id'], $place['category_id'], $place['province_id'], 4);

$pageTitle = $place['name_th'];
require_once INCLUDES_PATH . '/header.php';
?>

<!-- Hero Section -->
<section class="relative h-[50vh] min-h-[400px] overflow-hidden">
    <img src="<?php echo h($place['thumbnail'] ?: 'https://via.placeholder.com/1920x600?text=No+Image'); ?>"
        alt="<?php echo h($place['name_th']); ?>"
        class="absolute inset-0 w-full h-full object-cover">
    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"></div>

    <!-- Content -->
    <div class="absolute bottom-0 left-0 right-0 p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Breadcrumb -->
            <nav class="mb-4">
                <ol class="flex items-center text-white/80 text-sm">
                    <li><a href="<?php echo BASE_URL; ?>" class="hover:text-white">หน้าแรก</a></li>
                    <li class="mx-2">/</li>
                    <li><a href="<?php echo BASE_URL; ?>/pages/public/search.php" class="hover:text-white">ค้นหา</a></li>
                    <li class="mx-2">/</li>
                    <li class="text-white"><?php echo h($place['name_th']); ?></li>
                </ol>
            </nav>

            <!-- Category -->
            <span class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur rounded-full text-white text-sm mb-4">
                <i class="fas <?php echo h($place['category_icon'] ?: 'fa-map-pin'); ?> mr-2"></i>
                <?php echo h($place['category_name']); ?>
            </span>

            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                <?php echo h($place['name_th']); ?>
            </h1>

            <div class="flex flex-wrap items-center gap-4 text-white/90">
                <span class="flex items-center">
                    <i class="fas fa-map-marker-alt mr-2"></i>
                    <?php echo h($place['province_name']); ?>
                </span>

                <span class="flex items-center">
                    <div class="rating-stars mr-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= round($place['avg_rating'])): ?>
                                <i class="fas fa-star text-yellow-400"></i>
                            <?php else: ?>
                                <i class="far fa-star text-white/50"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <?php echo number_format($place['avg_rating'], 1); ?>
                    (<?php echo format_number($place['review_count']); ?> รีวิว)
                </span>

                <span class="flex items-center">
                    <i class="fas fa-eye mr-2"></i>
                    <?php echo format_number($place['view_count']); ?> ดู
                </span>
            </div>
        </div>
    </div>
</section>

<div class="bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Main Content -->
            <main class="flex-1">
                <!-- Quick Info -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <?php if (!empty($place['budget_name'])): ?>
                            <div class="text-center p-4 bg-green-50 rounded-xl">
                                <i class="fas fa-wallet text-2xl text-green-600 mb-2"></i>
                                <p class="text-sm text-gray-600">งบประมาณ</p>
                                <p class="font-bold text-gray-800"><?php echo h($place['budget_name']); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($place['opening_hours'])): ?>
                            <div class="text-center p-4 bg-blue-50 rounded-xl">
                                <i class="fas fa-clock text-2xl text-blue-600 mb-2"></i>
                                <p class="text-sm text-gray-600">เวลาเปิด</p>
                                <p class="font-bold text-gray-800">
                                    <?php
                                    // Parse opening hours JSON
                                    $hours = $place['opening_hours'];
                                    if (is_string($hours)) {
                                        $hoursData = json_decode($hours, true);
                                        if (is_array($hoursData) && isset($hoursData['mon'])) {
                                            echo h($hoursData['mon']);
                                        } else {
                                            echo h($hours);
                                        }
                                    } else {
                                        echo 'ไม่ระบุ';
                                    }
                                    ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($place['entrance_fee_thai']) || !empty($place['is_free'])): ?>
                            <div class="text-center p-4 bg-yellow-50 rounded-xl">
                                <i class="fas fa-ticket-alt text-2xl text-yellow-600 mb-2"></i>
                                <p class="text-sm text-gray-600">ค่าเข้า</p>
                                <p class="font-bold text-gray-800">
                                    <?php
                                    if (!empty($place['is_free'])) {
                                        echo 'ฟรี';
                                    } elseif (!empty($place['entrance_fee_thai'])) {
                                        echo format_price($place['entrance_fee_thai']);
                                    } else {
                                        echo 'ไม่ระบุ';
                                    }
                                    ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($place['moods']) && count($place['moods']) > 0): ?>
                            <div class="text-center p-4 bg-purple-50 rounded-xl">
                                <?php
                                $moodIcon = $place['moods'][0]['icon'] ?? 'fa-heart';
                                $moodName = $place['moods'][0]['name_th'] ?? 'ไม่ระบุ';
                                // Check if icon is FA class or emoji
                                if (strpos($moodIcon, 'fa-') !== false):
                                ?>
                                    <i class="fas <?php echo h($moodIcon); ?> text-2xl text-purple-600 mb-2"></i>
                                <?php else: ?>
                                    <span class="text-2xl mb-2 block"><?php echo h($moodIcon); ?></span>
                                <?php endif; ?>
                                <p class="text-sm text-gray-600">อารมณ์</p>
                                <p class="font-bold text-gray-800"><?php echo h($moodName); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Description -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-info-circle text-primary-500 mr-2"></i>รายละเอียด
                    </h2>
                    <div class="prose max-w-none text-gray-600">
                        <?php echo nl2br(h($place['description_th'] ?? 'ไม่มีรายละเอียด')); ?>
                    </div>
                </div>

                <!-- Tags -->
                <?php if (!empty($place['moods']) || !empty($place['seasons'])): ?>
                    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-tags text-primary-500 mr-2"></i>แท็ก
                        </h2>
                        <div class="flex flex-wrap gap-2">
                            <?php if (!empty($place['moods'])): ?>
                                <?php foreach ($place['moods'] as $mood): ?>
                                    <span class="px-4 py-2 bg-purple-100 text-purple-700 rounded-full text-sm flex items-center gap-2">
                                        <?php
                                        $icon = $mood['icon'] ?? '';
                                        if (strpos($icon, 'fa-') !== false):
                                        ?>
                                            <i class="fas <?php echo h($icon); ?>"></i>
                                        <?php else: ?>
                                            <span><?php echo h($icon); ?></span>
                                        <?php endif; ?>
                                        <?php echo h($mood['name_th'] ?? ''); ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if (!empty($place['seasons'])): ?>
                                <?php foreach ($place['seasons'] as $season): ?>
                                    <span class="px-4 py-2 bg-green-100 text-green-700 rounded-full text-sm flex items-center gap-2">
                                        <?php
                                        $icon = $season['icon'] ?? '';
                                        if (strpos($icon, 'fa-') !== false):
                                        ?>
                                            <i class="fas <?php echo h($icon); ?>"></i>
                                        <?php else: ?>
                                            <span><?php echo h($icon); ?></span>
                                        <?php endif; ?>
                                        <?php echo h($season['name_th'] ?? ''); ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Map -->
                <?php if (!empty($place['latitude']) && !empty($place['longitude'])): ?>
                    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-map-marked-alt text-primary-500 mr-2"></i>แผนที่
                        </h2>
                        <div id="map" class="h-80 rounded-xl"></div>

                        <div class="mt-4 flex flex-wrap gap-3">
                            <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $place['latitude']; ?>,<?php echo $place['longitude']; ?>"
                                target="_blank"
                                class="btn btn-primary">
                                <i class="fas fa-directions mr-2"></i>นำทางด้วย Google Maps
                            </a>

                            <?php if (!empty($place['google_map_url'])): ?>
                                <a href="<?php echo h($place['google_map_url']); ?>"
                                    target="_blank"
                                    class="btn btn-outline">
                                    <i class="fas fa-external-link-alt mr-2"></i>ดูบน Google Maps
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Reviews -->
                <div class="bg-white rounded-2xl shadow-lg p-6" id="reviews-section">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-comments text-primary-500 mr-2"></i>รีวิว
                            <span class="text-gray-500 text-base font-normal" id="review-count">(<?php echo format_number($reviewCount); ?>)</span>
                        </h2>

                        <?php if (is_logged_in()): ?>
                            <button onclick="openReviewModal()" class="btn btn-primary">
                                <i class="fas fa-plus mr-2"></i>เขียนรีวิว
                            </button>
                        <?php else: ?>
                            <a href="<?php echo BASE_URL; ?>/pages/user/login.php" class="btn btn-outline">
                                เข้าสู่ระบบเพื่อรีวิว
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Rating Breakdown -->
                    <?php if ($reviewCount > 0): ?>
                        <div class="bg-gradient-to-r from-primary-50 to-accent-50 rounded-xl p-6 mb-6">
                            <div class="flex flex-col md:flex-row items-center gap-6">
                                <!-- Overall Rating -->
                                <div class="text-center md:w-1/3">
                                    <div class="text-5xl font-bold text-primary-600 mb-2">
                                        <?php echo number_format($place['avg_rating'], 1); ?>
                                    </div>
                                    <div class="rating-stars mb-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= round($place['avg_rating'])): ?>
                                                <i class="fas fa-star"></i>
                                            <?php else: ?>
                                                <i class="far fa-star empty"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="text-gray-600 text-sm"><?php echo format_number($reviewCount); ?> รีวิว</p>
                                </div>

                                <!-- Category Ratings -->
                                <div class="flex-1 grid grid-cols-3 gap-4 text-center">
                                    <div>
                                        <div class="text-2xl font-bold text-gray-800">
                                            <?php echo number_format($place['avg_rating_cleanliness'] ?? 0, 1); ?>
                                        </div>
                                        <p class="text-gray-500 text-sm">ความสะอาด</p>
                                    </div>
                                    <div>
                                        <div class="text-2xl font-bold text-gray-800">
                                            <?php echo number_format($place['avg_rating_service'] ?? 0, 1); ?>
                                        </div>
                                        <p class="text-gray-500 text-sm">การบริการ</p>
                                    </div>
                                    <div>
                                        <div class="text-2xl font-bold text-gray-800">
                                            <?php echo number_format($place['avg_rating_value'] ?? 0, 1); ?>
                                        </div>
                                        <p class="text-gray-500 text-sm">ความคุ้มค่า</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Reviews List -->
                    <div id="reviews-container">
                        <?php if (empty($reviews)): ?>
                            <div class="text-center py-12" id="no-reviews">
                                <i class="fas fa-comment-slash text-5xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500">ยังไม่มีรีวิว เป็นคนแรกที่รีวิวสถานที่นี้!</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-6" id="reviews-list">
                                <?php foreach ($reviews as $review): ?>
                                    <div class="review-card flex gap-4 p-5 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors" data-review-id="<?php echo $review['id']; ?>">
                                        <div class="flex-shrink-0">
                                            <div class="w-12 h-12 bg-gradient-to-br from-primary-100 to-accent-100 rounded-full flex items-center justify-center overflow-hidden">
                                                <?php if (!empty($review['avatar'])): ?>
                                                    <img src="<?php echo h($review['avatar']); ?>" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <i class="fas fa-user text-primary-600"></i>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between mb-2">
                                                <h4 class="font-bold text-gray-800"><?php echo h($review['full_name']); ?></h4>
                                                <span class="text-sm text-gray-500"><?php echo format_date_thai($review['created_at']); ?></span>
                                            </div>

                                            <!-- Star Rating -->
                                            <div class="flex items-center gap-2 mb-3">
                                                <div class="rating-stars">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <?php if ($i <= ($review['rating_overall'] ?? 0)): ?>
                                                            <i class="fas fa-star text-sm"></i>
                                                        <?php else: ?>
                                                            <i class="far fa-star text-sm empty"></i>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                </div>
                                                <?php if (!empty($review['visit_date'])): ?>
                                                    <span class="text-xs text-gray-400">| ไป <?php echo date('M Y', strtotime($review['visit_date'])); ?></span>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Review Content -->
                                            <?php if (!empty($review['title'])): ?>
                                                <h5 class="font-semibold text-gray-800 mb-1"><?php echo h($review['title']); ?></h5>
                                            <?php endif; ?>
                                            <p class="text-gray-600 mb-3"><?php echo nl2br(h($review['content'] ?? '')); ?></p>

                                            <!-- Review Images -->
                                            <?php if (!empty($review['images'])): ?>
                                                <div class="flex flex-wrap gap-2 mb-3">
                                                    <?php foreach ($review['images'] as $image): ?>
                                                        <a href="<?php echo h($image['image_url']); ?>" target="_blank" class="block">
                                                            <img src="<?php echo h($image['image_url']); ?>"
                                                                alt="Review Image"
                                                                class="w-20 h-20 object-cover rounded-lg hover:opacity-80 transition-opacity cursor-pointer">
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>


                                            <!-- Like Button -->
                                            <div class="flex items-center gap-4 pt-3 border-t border-gray-200">
                                                <button onclick="toggleLike(<?php echo $review['id']; ?>, this)"
                                                    class="like-btn flex items-center gap-2 text-gray-500 hover:text-primary-600 transition-colors">
                                                    <i class="far fa-thumbs-up"></i>
                                                    <span class="like-count"><?php echo $review['helpful_count'] ?? 0; ?></span>
                                                    <span class="text-sm">มีประโยชน์</span>
                                                </button>
                                            </div>

                                            <!-- Owner Reply -->
                                            <?php if (!empty($review['owner_reply'])): ?>
                                                <div class="mt-4 p-4 bg-blue-50 rounded-lg border-l-4 border-blue-400">
                                                    <p class="text-sm font-semibold text-blue-700 mb-1">
                                                        <i class="fas fa-reply mr-1"></i>ตอบกลับจากเจ้าของสถานที่
                                                    </p>
                                                    <p class="text-gray-600 text-sm"><?php echo nl2br(h($review['owner_reply'])); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($reviewCount > 5): ?>
                                <div class="text-center mt-6">
                                    <button onclick="loadMoreReviews()" class="btn btn-outline" id="load-more-btn">
                                        <i class="fas fa-arrow-down mr-2"></i>ดูรีวิวเพิ่มเติม
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>

            <!-- Sidebar -->
            <aside class="lg:w-80 flex-shrink-0 space-y-6">
                <!-- Contact Info -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="font-bold text-gray-800 mb-4">
                        <i class="fas fa-address-card text-primary-500 mr-2"></i>ข้อมูลติดต่อ
                    </h3>

                    <?php if (!empty($place['address'])): ?>
                        <div class="flex items-start gap-3 mb-4">
                            <i class="fas fa-map-marker-alt text-gray-400 mt-1"></i>
                            <span class="text-gray-600 text-sm"><?php echo h($place['address']); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($place['phone'])): ?>
                        <div class="flex items-center gap-3 mb-4">
                            <i class="fas fa-phone text-gray-400"></i>
                            <a href="tel:<?php echo h($place['phone']); ?>" class="text-primary-600 hover:underline">
                                <?php echo h($place['phone']); ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($place['website'])): ?>
                        <div class="flex items-center gap-3 mb-4">
                            <i class="fas fa-globe text-gray-400"></i>
                            <a href="<?php echo h($place['website']); ?>" target="_blank" class="text-primary-600 hover:underline truncate">
                                <?php echo h(parse_url($place['website'], PHP_URL_HOST)); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="font-bold text-gray-800 mb-4">
                        <i class="fas fa-heart text-primary-500 mr-2"></i>การดำเนินการ
                    </h3>

                    <div class="space-y-3">
                        <button onclick="sharePlace()" class="w-full btn btn-outline flex items-center justify-center">
                            <i class="fas fa-share-alt mr-2"></i>แชร์
                        </button>

                        <?php if (is_logged_in()): ?>
                            <button onclick="addToFavorite(<?php echo $place['id']; ?>)" class="w-full btn btn-outline flex items-center justify-center">
                                <i class="far fa-heart mr-2"></i>เพิ่มในรายการโปรด
                            </button>

                            <button onclick="addToTrip(<?php echo $place['id']; ?>)" class="w-full btn btn-primary flex items-center justify-center">
                                <i class="fas fa-route mr-2"></i>เพิ่มในทริป
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Related Places -->
                <?php if (!empty($relatedPlaces)): ?>
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h3 class="font-bold text-gray-800 mb-4">
                            <i class="fas fa-map-marker-alt text-primary-500 mr-2"></i>สถานที่ใกล้เคียง
                        </h3>

                        <div class="space-y-4">
                            <?php foreach ($relatedPlaces as $related): ?>
                                <a href="<?php echo BASE_URL; ?>/pages/public/place.php?slug=<?php echo h($related['slug']); ?>"
                                    class="flex gap-3 group">
                                    <img src="<?php echo h($related['thumbnail'] ?: 'https://via.placeholder.com/100'); ?>"
                                        class="w-16 h-16 rounded-lg object-cover flex-shrink-0">
                                    <div>
                                        <h4 class="font-medium text-gray-800 group-hover:text-primary-600 transition-colors line-clamp-1">
                                            <?php echo h($related['name_th']); ?>
                                        </h4>
                                        <p class="text-sm text-gray-500"><?php echo h($related['province_name']); ?></p>
                                        <div class="rating-stars text-xs">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= round($related['avg_rating'])): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star empty"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</div>

<!-- Leaflet Map -->
<?php if (!empty($place['latitude']) && !empty($place['longitude'])): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const lat = <?php echo $place['latitude']; ?>;
            const lng = <?php echo $place['longitude']; ?>;

            const map = L.map('map').setView([lat, lng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);

            const marker = L.marker([lat, lng]).addTo(map);
            marker.bindPopup('<b><?php echo addslashes($place['name_th']); ?></b>').openPopup();
        });
    </script>
<?php endif; ?>


<!-- Review Modal -->
<?php if (is_logged_in()): ?>
    <style>
        /* Fix modal z-index above Leaflet map */
        #review-modal {
            z-index: 9999 !important;
        }

        #review-modal>div {
            z-index: 9999 !important;
        }

        .leaflet-pane,
        .leaflet-control {
            z-index: 400 !important;
        }
    </style>
    <div id="review-modal" class="fixed inset-0 hidden overflow-y-auto" style="z-index: 9999;">
        <div class="min-h-screen px-4 py-8 flex items-center justify-center">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeReviewModal()" style="z-index: 9998;"></div>

            <!-- Modal Content -->
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto" style="z-index: 10000;">
                <!-- Header -->
                <div class="sticky top-0 bg-white border-b px-6 py-4 rounded-t-2xl">
                    <h3 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-star text-yellow-400 mr-2"></i>เขียนรีวิว
                    </h3>
                    <button onclick="closeReviewModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Form -->
                <form id="review-form" enctype="multipart/form-data" class="p-6">
                    <input type="hidden" name="place_id" value="<?php echo $place['id']; ?>">

                    <!-- Overall Rating -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">คะแนนรวม <span class="text-red-500">*</span></label>
                        <div class="star-rating-input" data-name="rating_overall">
                            <i class="far fa-star text-3xl cursor-pointer hover:text-yellow-400" data-value="1"></i>
                            <i class="far fa-star text-3xl cursor-pointer hover:text-yellow-400" data-value="2"></i>
                            <i class="far fa-star text-3xl cursor-pointer hover:text-yellow-400" data-value="3"></i>
                            <i class="far fa-star text-3xl cursor-pointer hover:text-yellow-400" data-value="4"></i>
                            <i class="far fa-star text-3xl cursor-pointer hover:text-yellow-400" data-value="5"></i>
                        </div>
                        <input type="hidden" name="rating_overall" id="rating_overall" required>
                    </div>

                    <!-- Category Ratings -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">ความสะอาด</label>
                            <div class="star-rating-input small" data-name="rating_cleanliness">
                                <i class="far fa-star cursor-pointer hover:text-yellow-400" data-value="1"></i>
                                <i class="far fa-star cursor-pointer hover:text-yellow-400" data-value="2"></i>
                                <i class="far fa-star cursor-pointer hover:text-yellow-400" data-value="3"></i>
                                <i class="far fa-star cursor-pointer hover:text-yellow-400" data-value="4"></i>
                                <i class="far fa-star cursor-pointer hover:text-yellow-400" data-value="5"></i>
                            </div>
                            <input type="hidden" name="rating_cleanliness" id="rating_cleanliness">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">การบริการ</label>
                            <div class="star-rating-input small" data-name="rating_service">
                                <i class="far fa-star cursor-pointer hover:text-yellow-400" data-value="1"></i>
                                <i class="far fa-star cursor-pointer hover:text-yellow-400" data-value="2"></i>
                                <i class="far fa-star cursor-pointer hover:text-yellow-400" data-value="3"></i>
                                <i class="far fa-star cursor-pointer hover:text-yellow-400" data-value="4"></i>
                                <i class="far fa-star cursor-pointer hover:text-yellow-400" data-value="5"></i>
                            </div>
                            <input type="hidden" name="rating_service" id="rating_service">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">ความคุ้มค่า</label>
                            <div class="star-rating-input small" data-name="rating_value">
                                <i class="far fa-star cursor-pointer hover:text-yellow-400" data-value="1"></i>
                                <i class="far fa-star cursor-pointer hover:text-yellow-400" data-value="2"></i>
                                <i class="far fa-star cursor-pointer hover:text-yellow-400" data-value="3"></i>
                                <i class="far fa-star cursor-pointer hover:text-yellow-400" data-value="4"></i>
                                <i class="far fa-star cursor-pointer hover:text-yellow-400" data-value="5"></i>
                            </div>
                            <input type="hidden" name="rating_value" id="rating_value">
                        </div>
                    </div>

                    <!-- Visit Date -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">วันที่ไปเที่ยว</label>
                        <input type="date" name="visit_date" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>

                    <!-- Title -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">หัวข้อ (ไม่บังคับ)</label>
                        <input type="text" name="title" placeholder="สรุปสั้นๆ เกี่ยวกับประสบการณ์ของคุณ"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>

                    <!-- Content -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">เนื้อหารีวิว <span class="text-red-500">*</span></label>
                        <textarea name="content" rows="4" required minlength="10"
                            placeholder="แชร์ประสบการณ์ของคุณ... (อย่างน้อย 10 ตัวอักษร)"
                            class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"></textarea>
                    </div>

                    <!-- Image Upload -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-camera mr-1"></i>อัปโหลดรูป (สูงสุด 5 รูป)
                        </label>
                        <input type="file" name="images[]" multiple accept="image/*" id="review-images" class="hidden">
                        <label for="review-images"
                            class="block w-full p-4 border-2 border-dashed rounded-lg text-center cursor-pointer hover:border-primary-400 hover:bg-primary-50 transition-colors">
                            <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-2"></i>
                            <p class="text-gray-500">คลิกเพื่อเลือกรูป หรือลากไฟล์มาวาง</p>
                        </label>
                        <div id="image-preview" class="flex flex-wrap gap-2 mt-3"></div>
                    </div>

                    <!-- Submit -->
                    <div class="flex gap-3">
                        <button type="button" onclick="closeReviewModal()" class="flex-1 btn btn-outline">
                            ยกเลิก
                        </button>
                        <button type="submit" class="flex-1 btn btn-primary" id="submit-review-btn">
                            <i class="fas fa-paper-plane mr-2"></i>ส่งรีวิว
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Review System JavaScript -->
<script>
    const placeId = <?php echo $place['id']; ?>;
    const baseUrl = '<?php echo BASE_URL; ?>';
    let reviewOffset = 5;

    // Share Place
    function sharePlace() {
        const url = window.location.href;
        const title = '<?php echo addslashes($place['name_th']); ?>';

        if (navigator.share) {
            navigator.share({
                title,
                url
            });
        } else {
            navigator.clipboard.writeText(url).then(() => {
                showToast('success', 'คัดลอกลิงก์แล้ว');
            });
        }
    }

    function addToFavorite(placeId) {
        showToast('info', 'ฟีเจอร์นี้จะเปิดใช้งานเร็วๆ นี้');
    }

    function addToTrip(placeId) {
        // Fetch user's trips and show modal
        fetch(`${baseUrl}/api/trips.php?action=list_for_select`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    showToast('error', 'ไม่สามารถโหลดรายการทริปได้');
                    return;
                }
                showTripSelectModal(placeId, data.data);
            })
            .catch(() => {
                showToast('error', 'เกิดข้อผิดพลาด');
            });
    }

    function showTripSelectModal(placeId, trips) {
        let tripOptions = '';
        if (trips.length === 0) {
            tripOptions = '<p class="text-gray-500 text-center py-4">ยังไม่มีทริป</p>';
        } else {
            tripOptions = trips.map(t => `
                <button onclick="addPlaceToTrip(${placeId}, ${t.id})" 
                        class="w-full text-left p-4 rounded-lg border border-gray-200 hover:border-primary-400 hover:bg-primary-50 transition-colors mb-2">
                    <div class="font-semibold text-gray-800">${t.name}</div>
                    <div class="text-sm text-gray-500">${t.item_count || 0} สถานที่</div>
                </button>
            `).join('');
        }

        Swal.fire({
            title: '<i class="fas fa-route text-primary-500 mr-2"></i>เพิ่มในทริป',
            html: `
                <div class="text-left max-h-60 overflow-y-auto mb-4">
                    ${tripOptions}
                </div>
                <button onclick="createNewTripAndAdd(${placeId})" 
                        class="w-full p-4 rounded-lg border-2 border-dashed border-primary-300 text-primary-600 hover:bg-primary-50 transition-colors">
                    <i class="fas fa-plus mr-2"></i>สร้างทริปใหม่
                </button>
            `,
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: 'ปิด'
        });
    }

    async function addPlaceToTrip(placeId, tripId) {
        Swal.close();

        const formData = new FormData();
        formData.append('trip_id', tripId);
        formData.append('place_id', placeId);

        try {
            const response = await fetch(`${baseUrl}/api/trips.php?action=add_item`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'เพิ่มสำเร็จ!',
                    text: 'สถานที่ถูกเพิ่มในทริปแล้ว',
                    showCancelButton: true,
                    confirmButtonText: 'ดูทริป',
                    cancelButtonText: 'ปิด'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `${baseUrl}/pages/user/trip-detail.php?id=${tripId}`;
                    }
                });
            } else {
                Swal.fire('เกิดข้อผิดพลาด', result.message, 'error');
            }
        } catch (error) {
            Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
        }
    }

    async function createNewTripAndAdd(placeId) {
        Swal.close();

        const {
            value: tripName
        } = await Swal.fire({
            title: 'สร้างทริปใหม่',
            input: 'text',
            inputLabel: 'ชื่อทริป',
            inputPlaceholder: 'เช่น ทริปเชียงใหม่ 3 วัน',
            showCancelButton: true,
            confirmButtonText: 'สร้างและเพิ่ม',
            cancelButtonText: 'ยกเลิก',
            inputValidator: (value) => {
                if (!value) return 'กรุณาใส่ชื่อทริป';
            }
        });

        if (tripName) {
            // Create trip first
            const createForm = new FormData();
            createForm.append('name', tripName);

            try {
                const createRes = await fetch(`${baseUrl}/api/trips.php?action=create`, {
                    method: 'POST',
                    body: createForm
                });
                const createResult = await createRes.json();

                if (createResult.success && createResult.data?.id) {
                    // Add place to newly created trip
                    await addPlaceToTrip(placeId, createResult.data.id);
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', createResult.message, 'error');
                }
            } catch (error) {
                Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        }
    }

    // Review Modal
    function openReviewModal() {
        const modal = document.getElementById('review-modal');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeReviewModal() {
        const modal = document.getElementById('review-modal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    // Star Rating Input
    document.querySelectorAll('.star-rating-input').forEach(container => {
        const stars = container.querySelectorAll('i');
        const inputName = container.dataset.name;
        const input = document.getElementById(inputName);

        stars.forEach(star => {
            star.addEventListener('click', () => {
                const value = parseInt(star.dataset.value);
                if (input) input.value = value;

                stars.forEach((s, i) => {
                    if (i < value) {
                        s.classList.remove('far');
                        s.classList.add('fas', 'text-yellow-400');
                    } else {
                        s.classList.remove('fas', 'text-yellow-400');
                        s.classList.add('far');
                    }
                });
            });

            star.addEventListener('mouseenter', () => {
                const value = parseInt(star.dataset.value);
                stars.forEach((s, i) => {
                    if (i < value) {
                        s.classList.add('text-yellow-400');
                    }
                });
            });

            star.addEventListener('mouseleave', () => {
                const currentValue = input ? parseInt(input.value) || 0 : 0;
                stars.forEach((s, i) => {
                    if (i >= currentValue) {
                        s.classList.remove('text-yellow-400');
                    }
                });
            });
        });
    });

    // Image Preview
    const imageInput = document.getElementById('review-images');
    const imagePreview = document.getElementById('image-preview');

    if (imageInput) {
        imageInput.addEventListener('change', () => {
            imagePreview.innerHTML = '';
            const files = Array.from(imageInput.files).slice(0, 5);

            files.forEach((file, i) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const div = document.createElement('div');
                    div.className = 'relative w-20 h-20';
                    div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover rounded-lg">
                    <span class="absolute -top-1 -right-1 w-5 h-5 bg-primary-500 text-white rounded-full text-xs flex items-center justify-center">${i+1}</span>
                `;
                    imagePreview.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        });
    }

    // Submit Review
    const reviewForm = document.getElementById('review-form');
    if (reviewForm) {
        reviewForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = document.getElementById('submit-review-btn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังส่ง...';
            submitBtn.disabled = true;

            try {
                const formData = new FormData(reviewForm);

                const response = await fetch(baseUrl + '/api/reviews.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showToast('success', 'สร้างรีวิวเรียบร้อยแล้ว!');
                    closeReviewModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('error', result.error || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                showToast('error', 'เกิดข้อผิดพลาดในการส่งข้อมูล');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    }

    // Toggle Like
    async function toggleLike(reviewId, btn) {
        try {
            const response = await fetch(baseUrl + '/api/review-likes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    review_id: reviewId
                })
            });

            const result = await response.json();

            if (result.success) {
                const icon = btn.querySelector('i');
                const count = btn.querySelector('.like-count');

                if (result.liked) {
                    icon.classList.remove('far');
                    icon.classList.add('fas', 'text-primary-600');
                } else {
                    icon.classList.remove('fas', 'text-primary-600');
                    icon.classList.add('far');
                }

                count.textContent = result.count;
            } else {
                if (response.status === 401) {
                    showToast('warning', 'กรุณาเข้าสู่ระบบก่อนกด Like');
                } else {
                    showToast('error', result.error);
                }
            }
        } catch (error) {
            showToast('error', 'เกิดข้อผิดพลาด');
        }
    }

    // Load More Reviews
    async function loadMoreReviews() {
        const btn = document.getElementById('load-more-btn');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังโหลด...';

        try {
            const response = await fetch(`${baseUrl}/api/reviews.php?place_id=${placeId}&limit=5&offset=${reviewOffset}`);
            const result = await response.json();

            if (result.success && result.reviews.length > 0) {
                const container = document.getElementById('reviews-list');

                result.reviews.forEach(review => {
                    container.innerHTML += createReviewCard(review);
                });

                reviewOffset += result.reviews.length;

                if (!result.has_more) {
                    btn.remove();
                } else {
                    btn.innerHTML = '<i class="fas fa-arrow-down mr-2"></i>ดูรีวิวเพิ่มเติม';
                }
            } else {
                btn.remove();
            }
        } catch (error) {
            btn.innerHTML = '<i class="fas fa-arrow-down mr-2"></i>ดูรีวิวเพิ่มเติม';
            showToast('error', 'ไม่สามารถโหลดรีวิวได้');
        }
    }

    function createReviewCard(review) {
        const stars = Array(5).fill(0).map((_, i) =>
            i < review.rating_overall ? '<i class="fas fa-star text-sm"></i>' : '<i class="far fa-star text-sm empty"></i>'
        ).join('');

        return `
        <div class="review-card flex gap-4 p-5 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-gradient-to-br from-primary-100 to-accent-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-primary-600"></i>
                </div>
            </div>
            <div class="flex-1">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-bold text-gray-800">${review.full_name}</h4>
                    <span class="text-sm text-gray-500">${new Date(review.created_at).toLocaleDateString('th-TH')}</span>
                </div>
                <div class="rating-stars mb-3">${stars}</div>
                <p class="text-gray-600 mb-3">${review.content}</p>
                <div class="flex items-center gap-4 pt-3 border-t border-gray-200">
                    <button onclick="toggleLike(${review.id}, this)" class="like-btn flex items-center gap-2 text-gray-500 hover:text-primary-600">
                        <i class="far fa-thumbs-up"></i>
                        <span class="like-count">${review.helpful_count || 0}</span>
                        <span class="text-sm">มีประโยชน์</span>
                    </button>
                </div>
            </div>
        </div>
    `;
    }

    // Close modal on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeReviewModal();
    });
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>