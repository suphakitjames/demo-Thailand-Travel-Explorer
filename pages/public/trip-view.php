<?php

/**
 * =====================================================
 * Public Trip View - หน้าดูทริปสาธารณะ
 * =====================================================
 */

require_once __DIR__ . '/../../config/config.php';
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/TripModel.php';

start_session();

$tripModel = new TripModel();

$tripId = (int)($_GET['id'] ?? 0);
if (!$tripId) {
    set_flash('error', 'ไม่พบทริปที่ต้องการ');
    redirect(BASE_URL);
}

$trip = $tripModel->getPublic($tripId);
if (!$trip) {
    set_flash('error', 'ไม่พบทริปหรือทริปไม่เปิดสาธารณะ');
    redirect(BASE_URL);
}

$pageTitle = $trip['name'] . ' - ทริปท่องเที่ยว';
require_once INCLUDES_PATH . '/header.php';
?>

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css">
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js"></script>

<style>
    .leaflet-routing-container {
        display: none;
    }
</style>

<div class="bg-gradient-to-b from-primary-100 to-white min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-3 py-1 bg-primary-100 text-primary-700 text-sm rounded-full">
                            <i class="fas fa-globe mr-1"></i>ทริปสาธารณะ
                        </span>
                    </div>

                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">
                        <?php echo h($trip['name']); ?>
                    </h1>

                    <?php if (!empty($trip['description'])): ?>
                        <p class="text-gray-600 mb-3"><?php echo h($trip['description']); ?></p>
                    <?php endif; ?>

                    <!-- Owner Info -->
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                            <?php if (!empty($trip['owner_avatar'])): ?>
                                <img src="<?php echo h($trip['owner_avatar']); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <i class="fas fa-user text-gray-400"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800"><?php echo h($trip['owner_name']); ?></p>
                            <p class="text-sm text-gray-500">สร้างเมื่อ <?php echo format_date_thai($trip['created_at']); ?></p>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                        <?php if (!empty($trip['start_date'])): ?>
                            <span class="flex items-center">
                                <i class="fas fa-calendar-alt mr-2 text-primary-500"></i>
                                <?php echo format_date_thai($trip['start_date']); ?>
                                <?php if (!empty($trip['end_date'])): ?>
                                    - <?php echo format_date_thai($trip['end_date']); ?>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>

                        <span class="flex items-center">
                            <i class="fas fa-map-pin mr-2 text-primary-500"></i>
                            <?php echo count($trip['items']); ?> สถานที่
                        </span>

                        <?php if (!empty($trip['total_distance'])): ?>
                            <span class="flex items-center">
                                <i class="fas fa-road mr-2 text-primary-500"></i>
                                <?php echo number_format($trip['total_distance'], 1); ?> กม.
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($trip['total_duration'])): ?>
                            <span class="flex items-center">
                                <i class="fas fa-clock mr-2 text-primary-500"></i>
                                <?php
                                $hours = floor($trip['total_duration'] / 60);
                                $mins = $trip['total_duration'] % 60;
                                echo $hours > 0 ? "{$hours} ชม. " : '';
                                echo "{$mins} นาที";
                                ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col gap-2">
                    <?php if (is_logged_in()): ?>
                        <button onclick="copyTrip()" class="btn btn-primary flex items-center justify-center">
                            <i class="fas fa-copy mr-2"></i>คัดลอกทริปนี้
                        </button>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/pages/user/login.php" class="btn btn-primary flex items-center justify-center">
                            <i class="fas fa-sign-in-alt mr-2"></i>เข้าสู่ระบบเพื่อคัดลอก
                        </a>
                    <?php endif; ?>
                    <button onclick="shareTrip()" class="btn btn-outline flex items-center justify-center">
                        <i class="fas fa-share-alt mr-2"></i>แชร์
                    </button>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Trip Items List -->
            <div class="w-full lg:w-2/5">
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-list text-primary-500 mr-2"></i>สถานที่ในทริป
                    </h2>

                    <?php if (empty($trip['items'])): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-map-marker-alt text-5xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">ยังไม่มีสถานที่ในทริป</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php
                            $currentDay = 0;
                            foreach ($trip['items'] as $index => $item):
                                if ($item['day_number'] != $currentDay):
                                    $currentDay = $item['day_number'];
                            ?>
                                    <div class="flex items-center gap-2 py-2">
                                        <div style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px;">
                                            <?php echo $currentDay; ?>
                                        </div>
                                        <span class="font-semibold text-gray-700">วันที่ <?php echo $currentDay; ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="bg-gray-50 rounded-xl p-4 flex gap-4 hover:bg-gray-100 transition-colors">
                                    <div class="flex-shrink-0 w-6 text-center">
                                        <span class="text-lg font-bold text-primary-500"><?php echo $index + 1; ?></span>
                                    </div>

                                    <div class="flex-shrink-0">
                                        <img src="<?php echo h($item['place_thumbnail'] ?: 'https://via.placeholder.com/60'); ?>"
                                            alt="<?php echo h($item['place_name']); ?>"
                                            class="w-16 h-16 rounded-lg object-cover">
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <a href="<?php echo BASE_URL; ?>/pages/public/place.php?slug=<?php echo h($item['place_slug']); ?>"
                                            class="font-semibold text-gray-800 hover:text-primary-600 line-clamp-1">
                                            <?php echo h($item['place_name']); ?>
                                        </a>

                                        <div class="flex items-center gap-2 text-sm text-gray-500 mt-1">
                                            <?php if (!empty($item['category_icon'])): ?>
                                                <i class="fas <?php echo h($item['category_icon']); ?>"></i>
                                            <?php endif; ?>
                                            <span><?php echo h($item['province_name']); ?></span>
                                        </div>

                                        <?php if (!empty($item['start_time']) || !empty($item['end_time'])): ?>
                                            <div class="text-sm text-gray-500 mt-1">
                                                <i class="fas fa-clock mr-1"></i>
                                                <?php echo h($item['start_time']); ?> - <?php echo h($item['end_time']); ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($item['note'])): ?>
                                            <div class="text-sm text-gray-600 mt-2 p-2 bg-yellow-50 rounded">
                                                <i class="fas fa-sticky-note mr-1 text-yellow-500"></i>
                                                <?php echo h($item['note']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Map -->
            <div class="w-full lg:w-3/5">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden sticky top-4">
                    <div id="map" class="h-[500px] lg:h-[600px]"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const baseUrl = '<?php echo BASE_URL; ?>';
    const tripId = <?php echo $tripId; ?>;
    const items = <?php echo json_encode($trip['items']); ?>;

    let map;

    document.addEventListener('DOMContentLoaded', function() {
        initMap();
    });

    function initMap() {
        let center = [13.7563, 100.5018];
        let zoom = 6;

        if (items.length > 0) {
            const firstItem = items.find(i => i.latitude && i.longitude);
            if (firstItem) {
                center = [parseFloat(firstItem.latitude), parseFloat(firstItem.longitude)];
                zoom = 10;
            }
        }

        map = L.map('map').setView(center, zoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        const bounds = [];
        const waypoints = [];

        items.forEach((item, index) => {
            if (!item.latitude || !item.longitude) return;

            const lat = parseFloat(item.latitude);
            const lng = parseFloat(item.longitude);

            bounds.push([lat, lng]);
            waypoints.push(L.latLng(lat, lng));

            const icon = L.divIcon({
                className: 'custom-marker',
                html: `<div style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">${index + 1}</div>`,
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });

            L.marker([lat, lng], {
                    icon
                })
                .bindPopup(`
                <div style="min-width: 200px;">
                    <img src="${item.place_thumbnail || 'https://via.placeholder.com/200'}" 
                         style="width: 100%; height: 100px; object-fit: cover; border-radius: 8px; margin-bottom: 10px;">
                    <h4 style="font-weight: bold; margin-bottom: 5px;">${item.place_name}</h4>
                    <p style="color: #666; font-size: 12px; margin: 0;">
                        <i class="fas fa-map-marker-alt"></i> ${item.province_name}
                    </p>
                </div>
            `)
                .addTo(map);
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds, {
                padding: [50, 50]
            });
        }

        if (waypoints.length > 1) {
            try {
                L.Routing.control({
                    waypoints: waypoints,
                    routeWhileDragging: false,
                    addWaypoints: false,
                    draggableWaypoints: false,
                    fitSelectedRoutes: false,
                    showAlternatives: false,
                    createMarker: () => null,
                    lineOptions: {
                        styles: [{
                                color: '#6366f1',
                                opacity: 0.8,
                                weight: 5
                            },
                            {
                                color: '#fff',
                                opacity: 0.3,
                                weight: 3
                            }
                        ]
                    }
                }).addTo(map);
            } catch (e) {
                console.log('Routing not available:', e);
            }
        }
    }

    // Copy Trip
    async function copyTrip() {
        const result = await Swal.fire({
            title: 'คัดลอกทริป?',
            text: 'ทริปนี้จะถูกคัดลอกไปยังรายการทริปของคุณ',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'คัดลอก',
            cancelButtonText: 'ยกเลิก'
        });

        if (result.isConfirmed) {
            try {
                const formData = new FormData();
                formData.append('trip_id', tripId);

                const response = await fetch(`${baseUrl}/api/trips.php?action=copy`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'คัดลอกสำเร็จ!',
                        text: 'ทริปถูกเพิ่มในรายการทริปของคุณแล้ว',
                        showCancelButton: true,
                        confirmButtonText: 'ดูทริป',
                        cancelButtonText: 'ปิด'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = `${baseUrl}/pages/user/trip-detail.php?id=${data.data.id}`;
                        }
                    });
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', data.message, 'error');
                }
            } catch (error) {
                Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        }
    }

    // Share Trip
    function shareTrip() {
        const url = window.location.href;
        const title = '<?php echo addslashes($trip['name']); ?>';

        if (navigator.share) {
            navigator.share({
                title,
                url
            });
        } else {
            navigator.clipboard.writeText(url).then(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'คัดลอกลิงก์แล้ว',
                    showConfirmButton: false,
                    timer: 2000
                });
            });
        }
    }
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>