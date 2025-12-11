<?php

/**
 * =====================================================
 * Trip Detail Page - หน้าจัดการทริป
 * =====================================================
 */

require_once __DIR__ . '/../../config/config.php';
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/TripModel.php';

start_session();
require_login();

$tripModel = new TripModel();
$userId = get_user_id();

$tripId = (int)($_GET['id'] ?? 0);
if (!$tripId) {
    set_flash('error', 'ไม่พบทริปที่ต้องการ');
    redirect(BASE_URL . '/pages/user/trips.php');
}

$trip = $tripModel->getById($tripId, $userId);
if (!$trip) {
    set_flash('error', 'ไม่พบทริปหรือคุณไม่มีสิทธิ์เข้าถึง');
    redirect(BASE_URL . '/pages/user/trips.php');
}

$isOwner = $trip['user_id'] == $userId;

$pageTitle = $trip['name'];
require_once INCLUDES_PATH . '/header.php';
?>

<!-- Include SortableJS for Drag & Drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Leaflet Routing Machine -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css">
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js"></script>

<style>
    .trip-item {
        cursor: grab;
        transition: all 0.2s;
    }

    .trip-item:active {
        cursor: grabbing;
    }

    .trip-item.sortable-ghost {
        opacity: 0.4;
        background: #e0e7ff;
    }

    .trip-item.sortable-chosen {
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        transform: scale(1.02);
    }

    .day-marker {
        background: linear-gradient(135deg, var(--primary-500), var(--accent-500));
        color: white;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
    }

    #map {
        z-index: 1;
    }

    .leaflet-routing-container {
        display: none;
    }
</style>

<div class="bg-gradient-to-b from-primary-100 to-white min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center text-sm text-gray-500">
                <li><a href="<?php echo BASE_URL; ?>" class="hover:text-primary-600">หน้าแรก</a></li>
                <li class="mx-2">/</li>
                <li><a href="<?php echo BASE_URL; ?>/pages/user/trips.php" class="hover:text-primary-600">ทริปของฉัน</a></li>
                <li class="mx-2">/</li>
                <li class="text-gray-800 font-medium"><?php echo h($trip['name']); ?></li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-800">
                            <?php echo h($trip['name']); ?>
                        </h1>
                        <?php
                        $statusConfig = [
                            'planning' => ['bg' => 'bg-yellow-100 text-yellow-700', 'text' => 'กำลังวางแผน'],
                            'ongoing' => ['bg' => 'bg-green-100 text-green-700', 'text' => 'กำลังเที่ยว'],
                            'completed' => ['bg' => 'bg-blue-100 text-blue-700', 'text' => 'เสร็จสิ้น'],
                            'cancelled' => ['bg' => 'bg-gray-100 text-gray-700', 'text' => 'ยกเลิก'],
                        ];
                        $status = $statusConfig[$trip['status']] ?? $statusConfig['planning'];
                        ?>
                        <span class="px-3 py-1 rounded-full text-sm <?php echo $status['bg']; ?>">
                            <?php echo $status['text']; ?>
                        </span>
                    </div>

                    <?php if (!empty($trip['description'])): ?>
                        <p class="text-gray-600"><?php echo h($trip['description']); ?></p>
                    <?php endif; ?>

                    <div class="flex flex-wrap items-center gap-4 mt-3 text-sm text-gray-500">
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
                            <span id="item-count"><?php echo count($trip['items']); ?></span> สถานที่
                        </span>

                        <?php if (!empty($trip['total_distance'])): ?>
                            <span class="flex items-center">
                                <i class="fas fa-road mr-2 text-primary-500"></i>
                                <span id="total-distance"><?php echo number_format($trip['total_distance'], 1); ?></span> กม.
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($trip['total_duration'])): ?>
                            <span class="flex items-center">
                                <i class="fas fa-clock mr-2 text-primary-500"></i>
                                <span id="total-duration">
                                    <?php
                                    $hours = floor($trip['total_duration'] / 60);
                                    $mins = $trip['total_duration'] % 60;
                                    echo $hours > 0 ? "{$hours} ชม. " : '';
                                    echo "{$mins} นาที";
                                    ?>
                                </span>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($isOwner): ?>
                    <div class="flex flex-wrap gap-2">
                        <button onclick="optimizeRoute()" class="btn btn-outline flex items-center" id="optimize-btn">
                            <i class="fas fa-magic mr-2"></i>จัดเส้นทาง
                        </button>
                        <button onclick="togglePublic()" class="btn btn-outline flex items-center" id="share-btn">
                            <i class="fas <?php echo $trip['is_public'] ? 'fa-lock' : 'fa-share-alt'; ?> mr-2"></i>
                            <?php echo $trip['is_public'] ? 'ปิดสาธารณะ' : 'แชร์ทริป'; ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Trip Items List -->
            <div class="w-full lg:w-2/5">
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-800">
                            <i class="fas fa-list text-primary-500 mr-2"></i>สถานที่ในทริป
                        </h2>
                        <?php if ($isOwner): ?>
                            <a href="<?php echo BASE_URL; ?>/pages/public/search.php"
                                class="text-sm text-primary-600 hover:underline">
                                + เพิ่มสถานที่
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($trip['items'])): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-map-marker-alt text-5xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 mb-4">ยังไม่มีสถานที่ในทริป</p>
                            <a href="<?php echo BASE_URL; ?>/pages/public/search.php" class="btn btn-primary">
                                <i class="fas fa-plus mr-2"></i>ค้นหาสถานที่
                            </a>
                        </div>
                    <?php else: ?>
                        <div id="trip-items" class="space-y-3">
                            <?php
                            $currentDay = 0;
                            foreach ($trip['items'] as $index => $item):
                                if ($item['day_number'] != $currentDay):
                                    $currentDay = $item['day_number'];
                            ?>
                                    <div class="day-header flex items-center gap-2 py-2">
                                        <div class="day-marker"><?php echo $currentDay; ?></div>
                                        <span class="font-semibold text-gray-700">วันที่ <?php echo $currentDay; ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="trip-item bg-gray-50 rounded-xl p-4 flex gap-4 hover:bg-gray-100 transition-colors"
                                    data-id="<?php echo $item['id']; ?>"
                                    data-day="<?php echo $item['day_number']; ?>"
                                    data-lat="<?php echo $item['latitude']; ?>"
                                    data-lng="<?php echo $item['longitude']; ?>">

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
                                    </div>

                                    <?php if ($isOwner): ?>
                                        <button onclick="removeItem(<?php echo $item['id']; ?>)"
                                            class="flex-shrink-0 text-gray-400 hover:text-red-500 transition-colors">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
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

                <!-- Share Link (if public) -->
                <?php if ($trip['is_public']): ?>
                    <div class="bg-white rounded-2xl shadow-lg p-4 mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-link mr-1"></i>ลิงก์แชร์
                        </label>
                        <div class="flex gap-2">
                            <input type="text" readonly id="share-link"
                                value="<?php echo BASE_URL; ?>/pages/public/trip-view.php?id=<?php echo $trip['id']; ?>"
                                class="flex-1 px-4 py-2 border rounded-lg bg-gray-50">
                            <button onclick="copyShareLink()" class="btn btn-primary">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    const baseUrl = '<?php echo BASE_URL; ?>';
    const tripId = <?php echo $tripId; ?>;
    const isOwner = <?php echo $isOwner ? 'true' : 'false'; ?>;
    const items = <?php echo json_encode($trip['items']); ?>;

    let map, markers = [],
        routingControl;

    // Initialize Map
    document.addEventListener('DOMContentLoaded', function() {
        initMap();
        if (isOwner) {
            initSortable();
        }
    });

    function initMap() {
        // Default center (Thailand)
        let center = [13.7563, 100.5018];
        let zoom = 6;

        if (items.length > 0) {
            // Center on first item
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

        // Add markers
        addMarkers();
    }

    function addMarkers() {
        // Clear existing markers
        markers.forEach(m => map.removeLayer(m));
        markers = [];

        if (routingControl) {
            map.removeControl(routingControl);
            routingControl = null;
        }

        const bounds = [];
        const waypoints = [];

        items.forEach((item, index) => {
            if (!item.latitude || !item.longitude) return;

            const lat = parseFloat(item.latitude);
            const lng = parseFloat(item.longitude);

            bounds.push([lat, lng]);
            waypoints.push(L.latLng(lat, lng));

            // Custom numbered marker
            const icon = L.divIcon({
                className: 'custom-marker',
                html: `<div style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">${index + 1}</div>`,
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });

            const marker = L.marker([lat, lng], {
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

            markers.push(marker);
        });

        // Fit bounds
        if (bounds.length > 0) {
            map.fitBounds(bounds, {
                padding: [50, 50]
            });
        }

        // Add routing if more than 1 point
        if (waypoints.length > 1) {
            try {
                routingControl = L.Routing.control({
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

    // Sortable Drag & Drop
    function initSortable() {
        const container = document.getElementById('trip-items');
        if (!container) return;

        new Sortable(container, {
            animation: 150,
            handle: '.trip-item',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: function(evt) {
                saveOrder();
            }
        });
    }

    async function saveOrder() {
        const itemElements = document.querySelectorAll('.trip-item');
        const orderedItems = [];

        itemElements.forEach((el, index) => {
            orderedItems.push({
                id: parseInt(el.dataset.id),
                day_number: parseInt(el.dataset.day) || 1,
                sort_order: index
            });
        });

        // Update item numbers
        itemElements.forEach((el, index) => {
            el.querySelector('.text-primary-500').textContent = index + 1;
        });

        try {
            const formData = new FormData();
            formData.append('trip_id', tripId);
            formData.append('items', JSON.stringify(orderedItems));

            const response = await fetch(`${baseUrl}/api/trips.php?action=reorder`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                // Update stats
                if (result.data.total_distance) {
                    document.getElementById('total-distance').textContent =
                        parseFloat(result.data.total_distance).toFixed(1);
                }
                if (result.data.total_duration) {
                    const hours = Math.floor(result.data.total_duration / 60);
                    const mins = result.data.total_duration % 60;
                    document.getElementById('total-duration').textContent =
                        (hours > 0 ? `${hours} ชม. ` : '') + `${mins} นาที`;
                }

                // Rebuild items array for map
                items.length = 0;
                itemElements.forEach(el => {
                    const originalItem = <?php echo json_encode($trip['items']); ?>
                        .find(i => i.id == el.dataset.id);
                    if (originalItem) items.push(originalItem);
                });

                addMarkers();
            }
        } catch (error) {
            console.error('Save order failed:', error);
        }
    }

    // Optimize Route
    async function optimizeRoute() {
        const btn = document.getElementById('optimize-btn');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังจัด...';
        btn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('trip_id', tripId);

            const response = await fetch(`${baseUrl}/api/trips.php?action=optimize`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'จัดเส้นทางสำเร็จ!',
                    text: `ระยะทางรวม ${parseFloat(result.data.total_distance).toFixed(1)} กม.`,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire('เกิดข้อผิดพลาด', result.message, 'error');
            }
        } catch (error) {
            Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
        } finally {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    }

    // Remove Item
    async function removeItem(itemId) {
        const result = await Swal.fire({
            title: 'ลบสถานที่?',
            text: 'ต้องการลบสถานที่นี้ออกจากทริปหรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก'
        });

        if (result.isConfirmed) {
            try {
                const formData = new FormData();
                formData.append('item_id', itemId);

                const response = await fetch(`${baseUrl}/api/trips.php?action=remove_item`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Remove from DOM
                    const el = document.querySelector(`.trip-item[data-id="${itemId}"]`);
                    if (el) el.remove();

                    // Update count
                    const countEl = document.getElementById('item-count');
                    countEl.textContent = parseInt(countEl.textContent) - 1;

                    // Remove from items array
                    const index = items.findIndex(i => i.id == itemId);
                    if (index > -1) items.splice(index, 1);

                    addMarkers();
                    showToast('success', 'ลบสถานที่สำเร็จ');
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', data.message, 'error');
                }
            } catch (error) {
                Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        }
    }

    // Toggle Public
    async function togglePublic() {
        const isCurrentlyPublic = <?php echo $trip['is_public'] ? 'true' : 'false'; ?>;

        const result = await Swal.fire({
            title: isCurrentlyPublic ? 'ปิดสาธารณะ?' : 'แชร์ทริป?',
            text: isCurrentlyPublic ?
                'ทริปนี้จะไม่สามารถเข้าถึงได้จากลิงก์สาธารณะ' :
                'ทริปนี้จะเปิดให้คนอื่นดูได้ผ่านลิงก์',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: isCurrentlyPublic ? 'ปิดสาธารณะ' : 'แชร์',
            cancelButtonText: 'ยกเลิก'
        });

        if (result.isConfirmed) {
            try {
                const formData = new FormData();
                formData.append('id', tripId);
                formData.append('is_public', isCurrentlyPublic ? '0' : '1');

                const response = await fetch(`${baseUrl}/api/trips.php?action=update`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', data.message, 'error');
                }
            } catch (error) {
                Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        }
    }

    // Copy Share Link
    function copyShareLink() {
        const input = document.getElementById('share-link');
        input.select();
        document.execCommand('copy');
        showToast('success', 'คัดลอกลิงก์แล้ว');
    }

    // Toast helper
    function showToast(type, message) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: type,
            title: message,
            showConfirmButton: false,
            timer: 2000
        });
    }
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>