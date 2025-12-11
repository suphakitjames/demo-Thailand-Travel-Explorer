<?php

/**
 * =====================================================
 * Nearby Places Page - สถานที่ใกล้ฉัน
 * =====================================================
 */

require_once __DIR__ . '/../../config/config.php';
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/PlaceModel.php';

start_session();

$placeModel = new PlaceModel();

// Get user coordinates from query string (set by JavaScript)
$userLat = (float) get('lat', 0);
$userLng = (float) get('lng', 0);
$radius = (int) get('radius', 50);

$nearbyPlaces = [];
$hasLocation = false;

if ($userLat != 0 && $userLng != 0) {
    $hasLocation = true;
    $nearbyPlaces = $placeModel->getNearby($userLat, $userLng, $radius, 20);
}

$pageTitle = 'สถานที่ใกล้ฉัน';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-map-marker-alt text-primary-500 mr-2"></i>สถานที่ใกล้ฉัน
            </h1>
            <p class="text-gray-600">ค้นหาสถานที่ท่องเที่ยวใกล้ตำแหน่งของคุณ</p>
        </div>

        <!-- Location Status -->
        <div id="locationStatus" class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <?php if (!$hasLocation): ?>
                <!-- Request Location -->
                <div id="requestLocation" class="text-center py-8">
                    <div class="w-24 h-24 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-location-arrow text-4xl text-primary-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">ต้องการเข้าถึงตำแหน่งของคุณ</h3>
                    <p class="text-gray-600 mb-6">เพื่อแสดงสถานที่ท่องเที่ยวใกล้เคียง</p>
                    <button onclick="requestLocation()" class="btn btn-gradient px-8 py-3">
                        <i class="fas fa-compass mr-2"></i>อนุญาตตำแหน่ง
                    </button>
                </div>

                <!-- Loading -->
                <div id="loadingLocation" class="text-center py-8 hidden">
                    <div class="w-16 h-16 border-4 border-primary-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
                    <p class="text-gray-600">กำลังค้นหาตำแหน่งของคุณ...</p>
                </div>

                <!-- Error -->
                <div id="locationError" class="text-center py-8 hidden">
                    <div class="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-4xl text-red-500"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">ไม่สามารถเข้าถึงตำแหน่งได้</h3>
                    <p class="text-gray-600 mb-4" id="errorMessage">กรุณาอนุญาตการเข้าถึงตำแหน่งในเบราว์เซอร์</p>
                    <button onclick="requestLocation()" class="btn btn-outline">
                        ลองอีกครั้ง
                    </button>
                </div>
            <?php else: ?>
                <!-- Location Found -->
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-2xl text-green-600"></i>
                        </div>
                        <div>
                            <p class="font-bold text-gray-800">พบตำแหน่งของคุณ</p>
                            <p class="text-sm text-gray-500">
                                พิกัด: <?php echo number_format($userLat, 4); ?>, <?php echo number_format($userLng, 4); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Radius Filter -->
                    <div class="flex items-center gap-4">
                        <label class="text-sm text-gray-600">รัศมี:</label>
                        <select id="radiusSelect" onchange="updateRadius()" class="px-4 py-2 border rounded-lg">
                            <option value="10" <?php echo $radius == 10 ? 'selected' : ''; ?>>10 กม.</option>
                            <option value="25" <?php echo $radius == 25 ? 'selected' : ''; ?>>25 กม.</option>
                            <option value="50" <?php echo $radius == 50 ? 'selected' : ''; ?>>50 กม.</option>
                            <option value="100" <?php echo $radius == 100 ? 'selected' : ''; ?>>100 กม.</option>
                        </select>
                        <button onclick="refreshLocation()" class="btn btn-outline">
                            <i class="fas fa-sync-alt mr-2"></i>รีเฟรช
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($hasLocation): ?>
            <!-- Map -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-map text-primary-500 mr-2"></i>แผนที่
                </h2>
                <div id="map" class="h-96 rounded-xl"></div>
            </div>

            <!-- Results -->
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-800">
                    สถานที่ใกล้เคียง (<?php echo count($nearbyPlaces); ?> แห่ง)
                </h2>
            </div>

            <?php if (empty($nearbyPlaces)): ?>
                <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                    <i class="fas fa-map-marked-alt text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-700 mb-2">ไม่พบสถานที่ใกล้เคียง</h3>
                    <p class="text-gray-500 mb-4">ลองเพิ่มรัศมีการค้นหา</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($nearbyPlaces as $place): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/public/place.php?slug=<?php echo h($place['slug']); ?>"
                            class="place-card group">
                            <!-- Image -->
                            <div class="relative h-48 overflow-hidden">
                                <img src="<?php echo h($place['thumbnail'] ?: 'https://via.placeholder.com/400x300?text=No+Image'); ?>"
                                    alt="<?php echo h($place['name_th']); ?>"
                                    class="w-full h-full object-cover"
                                    loading="lazy">

                                <!-- Distance Badge -->
                                <div class="absolute top-3 left-3">
                                    <span class="px-3 py-1 bg-primary-500 text-white rounded-full text-xs font-bold">
                                        <i class="fas fa-location-arrow mr-1"></i>
                                        <?php echo number_format($place['distance'], 1); ?> กม.
                                    </span>
                                </div>

                                <!-- Category Badge -->
                                <div class="absolute top-3 right-3">
                                    <span class="px-3 py-1 bg-white/90 backdrop-blur rounded-full text-xs font-medium text-gray-700">
                                        <i class="fas <?php echo h($place['category_icon'] ?: 'fa-map-pin'); ?> mr-1 text-primary-500"></i>
                                        <?php echo h($place['category_name'] ?? 'ไม่ระบุ'); ?>
                                    </span>
                                </div>
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
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Leaflet Map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    const userLat = <?php echo $userLat ?: 'null'; ?>;
    const userLng = <?php echo $userLng ?: 'null'; ?>;
    const places = <?php echo json_encode($nearbyPlaces); ?>;

    function requestLocation() {
        document.getElementById('requestLocation').classList.add('hidden');
        document.getElementById('loadingLocation').classList.remove('hidden');
        document.getElementById('locationError').classList.add('hidden');

        if (!navigator.geolocation) {
            showError('เบราว์เซอร์ของคุณไม่รองรับ Geolocation');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                window.location.href = `?lat=${lat}&lng=${lng}&radius=50`;
            },
            (error) => {
                let message = 'ไม่สามารถเข้าถึงตำแหน่งได้';
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        message = 'คุณปฏิเสธการเข้าถึงตำแหน่ง กรุณาอนุญาตในการตั้งค่าเบราว์เซอร์';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message = 'ไม่สามารถระบุตำแหน่งได้';
                        break;
                    case error.TIMEOUT:
                        message = 'หมดเวลาการค้นหาตำแหน่ง';
                        break;
                }
                showError(message);
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    }

    function showError(message) {
        document.getElementById('loadingLocation').classList.add('hidden');
        document.getElementById('locationError').classList.remove('hidden');
        document.getElementById('errorMessage').textContent = message;
    }

    function updateRadius() {
        const radius = document.getElementById('radiusSelect').value;
        window.location.href = `?lat=${userLat}&lng=${userLng}&radius=${radius}`;
    }

    function refreshLocation() {
        document.getElementById('loadingLocation').classList.remove('hidden');
        document.querySelector('#locationStatus > div:first-child')?.classList.add('hidden');

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const radius = document.getElementById('radiusSelect')?.value || 50;
                window.location.href = `?lat=${lat}&lng=${lng}&radius=${radius}`;
            },
            () => {
                window.location.reload();
            }
        );
    }

    // Initialize map if location is available
    if (userLat && userLng) {
        document.addEventListener('DOMContentLoaded', function() {
            const map = L.map('map').setView([userLat, userLng], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);

            // User marker
            const userIcon = L.divIcon({
                html: '<div class="w-6 h-6 bg-blue-500 rounded-full border-4 border-white shadow-lg animate-pulse"></div>',
                className: 'user-marker',
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });

            L.marker([userLat, userLng], {
                    icon: userIcon
                })
                .addTo(map)
                .bindPopup('<b>ตำแหน่งของคุณ</b>');

            // Place markers
            places.forEach(place => {
                if (place.latitude && place.longitude) {
                    L.marker([place.latitude, place.longitude])
                        .addTo(map)
                        .bindPopup(`
                        <b>${place.name_th}</b><br>
                        <span class="text-sm">${place.province_name}</span><br>
                        <a href="<?php echo BASE_URL; ?>/pages/public/place.php?slug=${place.slug}" 
                           class="text-blue-600 text-sm">ดูรายละเอียด</a>
                    `);
                }
            });

            // Fit bounds to show all markers
            if (places.length > 0) {
                const bounds = L.latLngBounds([
                    [userLat, userLng]
                ]);
                places.forEach(place => {
                    if (place.latitude && place.longitude) {
                        bounds.extend([place.latitude, place.longitude]);
                    }
                });
                map.fitBounds(bounds, {
                    padding: [20, 20]
                });
            }
        });
    }
</script>

<style>
    .user-marker {
        background: transparent !important;
        border: none !important;
    }
</style>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>