<?php

/**
 * =====================================================
 * My Trips Page - หน้าทริปของฉัน
 * =====================================================
 */

require_once __DIR__ . '/../../config/config.php';
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/TripModel.php';

start_session();
require_login();

$tripModel = new TripModel();
$userId = get_user_id();

$trips = $tripModel->getByUserId($userId);
$totalTrips = $tripModel->countByUserId($userId);

$pageTitle = 'ทริปของฉัน';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="bg-gradient-to-b from-primary-100 to-white min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-route text-primary-500 mr-3"></i>ทริปของฉัน
                </h1>
                <p class="text-gray-600">วางแผนการเดินทางของคุณ พร้อมจัดเส้นทางอัตโนมัติ</p>
            </div>
            <button onclick="openCreateModal()"
                class="btn btn-primary mt-4 md:mt-0 flex items-center justify-center">
                <i class="fas fa-plus mr-2"></i>สร้างทริปใหม่
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-5 text-center">
                <div class="text-3xl font-bold text-primary-600 mb-1"><?php echo $totalTrips; ?></div>
                <div class="text-sm text-gray-500">ทริปทั้งหมด</div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-5 text-center">
                <div class="text-3xl font-bold text-yellow-500 mb-1">
                    <?php echo count(array_filter($trips, fn($t) => $t['status'] === 'planning')); ?>
                </div>
                <div class="text-sm text-gray-500">กำลังวางแผน</div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-5 text-center">
                <div class="text-3xl font-bold text-green-500 mb-1">
                    <?php echo count(array_filter($trips, fn($t) => $t['status'] === 'ongoing')); ?>
                </div>
                <div class="text-sm text-gray-500">กำลังเที่ยว</div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-5 text-center">
                <div class="text-3xl font-bold text-blue-500 mb-1">
                    <?php echo count(array_filter($trips, fn($t) => $t['status'] === 'completed')); ?>
                </div>
                <div class="text-sm text-gray-500">เสร็จสิ้น</div>
            </div>
        </div>

        <!-- Trips Grid -->
        <?php if (empty($trips)): ?>
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <i class="fas fa-map-marked-alt text-6xl text-gray-300 mb-6"></i>
                <h2 class="text-2xl font-bold text-gray-700 mb-3">ยังไม่มีทริป</h2>
                <p class="text-gray-500 mb-6">เริ่มวางแผนการท่องเที่ยวของคุณกันเถอะ!</p>
                <button onclick="openCreateModal()" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus mr-2"></i>สร้างทริปแรกของคุณ
                </button>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($trips as $trip): ?>
                    <div class="trip-card bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <!-- Cover Image -->
                        <div class="relative h-48 bg-gradient-to-br from-primary-400 to-accent-500">
                            <?php if (!empty($trip['cover_thumbnail'])): ?>
                                <img src="<?php echo h($trip['cover_thumbnail']); ?>"
                                    alt="<?php echo h($trip['name']); ?>"
                                    class="absolute inset-0 w-full h-full object-cover">
                            <?php else: ?>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <i class="fas fa-route text-6xl text-white/30"></i>
                                </div>
                            <?php endif; ?>

                            <!-- Status Badge -->
                            <div class="absolute top-3 left-3">
                                <?php
                                $statusConfig = [
                                    'planning' => ['bg' => 'bg-yellow-500', 'text' => 'กำลังวางแผน', 'icon' => 'fa-edit'],
                                    'ongoing' => ['bg' => 'bg-green-500', 'text' => 'กำลังเที่ยว', 'icon' => 'fa-plane'],
                                    'completed' => ['bg' => 'bg-blue-500', 'text' => 'เสร็จสิ้น', 'icon' => 'fa-check'],
                                    'cancelled' => ['bg' => 'bg-gray-500', 'text' => 'ยกเลิก', 'icon' => 'fa-times'],
                                ];
                                $status = $statusConfig[$trip['status']] ?? $statusConfig['planning'];
                                ?>
                                <span class="inline-flex items-center px-3 py-1 <?php echo $status['bg']; ?> text-white text-sm rounded-full">
                                    <i class="fas <?php echo $status['icon']; ?> mr-1"></i>
                                    <?php echo $status['text']; ?>
                                </span>
                            </div>

                            <!-- Public Badge -->
                            <?php if (!empty($trip['is_public'])): ?>
                                <div class="absolute top-3 right-3">
                                    <span class="inline-flex items-center px-3 py-1 bg-white/90 text-primary-600 text-sm rounded-full">
                                        <i class="fas fa-globe mr-1"></i>สาธารณะ
                                    </span>
                                </div>
                            <?php endif; ?>

                            <!-- Item Count -->
                            <div class="absolute bottom-3 right-3">
                                <span class="inline-flex items-center px-3 py-1 bg-black/50 text-white text-sm rounded-full">
                                    <i class="fas fa-map-pin mr-1"></i>
                                    <?php echo $trip['item_count'] ?? 0; ?> สถานที่
                                </span>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-5">
                            <h3 class="text-xl font-bold text-gray-800 mb-2 line-clamp-1">
                                <?php echo h($trip['name']); ?>
                            </h3>

                            <!-- Dates -->
                            <?php if (!empty($trip['start_date']) || !empty($trip['end_date'])): ?>
                                <div class="flex items-center text-gray-500 text-sm mb-3">
                                    <i class="fas fa-calendar-alt mr-2"></i>
                                    <?php
                                    if (!empty($trip['start_date']) && !empty($trip['end_date'])) {
                                        echo format_date_thai($trip['start_date'], 'd M') . ' - ' . format_date_thai($trip['end_date'], 'd M Y');
                                    } elseif (!empty($trip['start_date'])) {
                                        echo format_date_thai($trip['start_date']);
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>

                            <!-- Stats -->
                            <?php if (!empty($trip['total_distance'])): ?>
                                <div class="flex items-center gap-4 text-sm text-gray-600 mb-4">
                                    <span class="flex items-center">
                                        <i class="fas fa-road mr-1 text-primary-500"></i>
                                        <?php echo number_format($trip['total_distance'], 1); ?> กม.
                                    </span>
                                    <?php if (!empty($trip['total_duration'])): ?>
                                        <span class="flex items-center">
                                            <i class="fas fa-clock mr-1 text-primary-500"></i>
                                            <?php
                                            $hours = floor($trip['total_duration'] / 60);
                                            $mins = $trip['total_duration'] % 60;
                                            echo $hours > 0 ? "{$hours} ชม. " : '';
                                            echo "{$mins} นาที";
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Actions -->
                            <div class="flex gap-2">
                                <a href="<?php echo BASE_URL; ?>/pages/user/trip-detail.php?id=<?php echo $trip['id']; ?>"
                                    class="flex-1 btn btn-primary text-center">
                                    <i class="fas fa-eye mr-2"></i>ดูทริป
                                </a>
                                <button onclick="editTrip(<?php echo $trip['id']; ?>, '<?php echo addslashes($trip['name']); ?>')"
                                    class="btn btn-outline">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteTrip(<?php echo $trip['id']; ?>, '<?php echo addslashes($trip['name']); ?>')"
                                    class="btn btn-outline text-red-500 hover:bg-red-50">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create/Edit Trip Modal -->
<div id="trip-modal" class="fixed inset-0 hidden z-50">
    <div class="min-h-screen px-4 py-8 flex items-center justify-center">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal()"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4 rounded-t-2xl">
                <h3 class="text-xl font-bold text-gray-800" id="modal-title">
                    <i class="fas fa-route text-primary-500 mr-2"></i>สร้างทริปใหม่
                </h3>
                <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form id="trip-form" class="p-6">
                <input type="hidden" name="id" id="trip-id">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        ชื่อทริป <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="trip-name" required
                        placeholder="เช่น ทริปเชียงใหม่ 3 วัน 2 คืน"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        รายละเอียด
                    </label>
                    <textarea name="description" id="trip-description" rows="3"
                        placeholder="รายละเอียดเพิ่มเติม..."
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar-alt mr-1"></i>วันเริ่มต้น
                        </label>
                        <input type="date" name="start_date" id="trip-start-date"
                            class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar-check mr-1"></i>วันสิ้นสุด
                        </label>
                        <input type="date" name="end_date" id="trip-end-date"
                            class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="closeModal()" class="flex-1 btn btn-outline">
                        ยกเลิก
                    </button>
                    <button type="submit" class="flex-1 btn btn-primary" id="submit-btn">
                        <i class="fas fa-save mr-2"></i>บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const baseUrl = '<?php echo BASE_URL; ?>';
    let isEditing = false;

    function openCreateModal() {
        isEditing = false;
        document.getElementById('modal-title').innerHTML = '<i class="fas fa-route text-primary-500 mr-2"></i>สร้างทริปใหม่';
        document.getElementById('trip-form').reset();
        document.getElementById('trip-id').value = '';
        document.getElementById('trip-modal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function editTrip(id, name) {
        isEditing = true;
        document.getElementById('modal-title').innerHTML = '<i class="fas fa-edit text-primary-500 mr-2"></i>แก้ไขทริป';
        document.getElementById('trip-id').value = id;
        document.getElementById('trip-name').value = name;
        document.getElementById('trip-modal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('trip-modal').classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Form Submit
    document.getElementById('trip-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const submitBtn = document.getElementById('submit-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังบันทึก...';
        submitBtn.disabled = true;

        const formData = new FormData(e.target);
        const action = isEditing ? 'update' : 'create';
        formData.append('action', action);

        try {
            const response = await fetch(`${baseUrl}/api/trips.php?action=${action}`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ!',
                    text: result.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    if (!isEditing && result.data?.id) {
                        window.location.href = `${baseUrl}/pages/user/trip-detail.php?id=${result.data.id}`;
                    } else {
                        window.location.reload();
                    }
                });
            } else {
                Swal.fire('เกิดข้อผิดพลาด', result.message, 'error');
            }
        } catch (error) {
            Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });

    // Delete Trip
    async function deleteTrip(id, name) {
        const result = await Swal.fire({
            title: 'ลบทริป?',
            html: `คุณต้องการลบทริป <b>${name}</b> หรือไม่?<br><small class="text-red-500">การดำเนินการนี้ไม่สามารถย้อนกลับได้</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'ลบทริป',
            cancelButtonText: 'ยกเลิก'
        });

        if (result.isConfirmed) {
            try {
                const formData = new FormData();
                formData.append('id', id);

                const response = await fetch(`${baseUrl}/api/trips.php?action=delete`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'ลบสำเร็จ!',
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

    // Close modal on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>