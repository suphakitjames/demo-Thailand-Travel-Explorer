<?php

/**
 * =====================================================
 * Owner Dashboard - หน้าจัดการสำหรับผู้ประกอบการ
 * =====================================================
 */

require_once __DIR__ . '/../../config/config.php';
require_once INCLUDES_PATH . '/functions.php';

start_session();
require_login();
require_role(['owner', 'admin']);

$db = db();
$userId = get_user_id();

// Get owner's places
$stmt = $db->prepare("
    SELECT p.*, c.name_th as category_name, pr.name_th as province_name
    FROM places p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN provinces pr ON p.province_id = pr.id
    WHERE p.owner_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$userId]);
$places = $stmt->fetchAll();

// Stats
$totalPlaces = count($places);
$approvedPlaces = count(array_filter($places, fn($p) => $p['status'] === 'approved'));
$pendingPlaces = count(array_filter($places, fn($p) => $p['status'] === 'pending'));
$totalViews = array_sum(array_column($places, 'view_count'));
$totalReviews = array_sum(array_column($places, 'review_count'));

// Get categories and provinces for form
$categories = $db->query("SELECT id, name_th FROM categories WHERE is_active = 1 ORDER BY name_th")->fetchAll();
$provinces = $db->query("SELECT id, name_th FROM provinces ORDER BY name_th")->fetchAll();

$pageTitle = 'จัดการสถานที่';
require_once INCLUDES_PATH . '/header.php';
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.tailwindcss.min.css">

<div class="bg-gray-100 min-h-screen">
    <!-- Owner Header -->
    <div class="bg-gradient-to-r from-green-600 to-emerald-700 text-white py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">
                        <i class="fas fa-store mr-3"></i>จัดการสถานที่ของฉัน
                    </h1>
                    <p class="text-green-100 mt-1">จัดการข้อมูลสถานที่ท่องเที่ยวของคุณ</p>
                </div>
                <button onclick="openAddModal()" class="px-5 py-3 bg-white text-green-600 rounded-xl hover:bg-green-50 transition-colors font-medium shadow-lg">
                    <i class="fas fa-plus mr-2"></i>เพิ่มสถานที่ใหม่
                </button>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-2xl p-5 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">สถานที่ทั้งหมด</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $totalPlaces; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-map-marker-alt text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">อนุมัติแล้ว</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo $approvedPlaces; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-check text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">เข้าชมทั้งหมด</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($totalViews); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-eye text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">รีวิวทั้งหมด</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($totalReviews); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-star text-yellow-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Places Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-list text-green-500 mr-2"></i>รายการสถานที่
                </h2>
            </div>

            <?php if (empty($places)): ?>
                <div class="p-12 text-center">
                    <i class="fas fa-map-marked-alt text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">ยังไม่มีสถานที่</h3>
                    <p class="text-gray-500 mb-6">เริ่มต้นเพิ่มสถานที่ท่องเที่ยวของคุณ</p>
                    <button onclick="openAddModal()" class="px-6 py-3 bg-green-500 text-white rounded-xl hover:bg-green-600 transition-colors">
                        <i class="fas fa-plus mr-2"></i>เพิ่มสถานที่ใหม่
                    </button>
                </div>
            <?php else: ?>
                <table id="places-table" class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">สถานที่</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">หมวดหมู่</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-700">สถานะ</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-700">เข้าชม</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-700">คะแนน</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-700">การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($places as $place): ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img src="<?php echo h($place['thumbnail'] ?: 'https://via.placeholder.com/60'); ?>"
                                            alt="" class="w-12 h-12 rounded-lg object-cover">
                                        <div>
                                            <a href="<?php echo BASE_URL; ?>/pages/public/place.php?slug=<?php echo h($place['slug']); ?>"
                                                target="_blank" class="font-medium text-gray-800 hover:text-green-600">
                                                <?php echo h($place['name_th']); ?>
                                            </a>
                                            <p class="text-sm text-gray-500"><?php echo h($place['province_name'] ?? '-'); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-600"><?php echo h($place['category_name'] ?? '-'); ?></td>
                                <td class="px-6 py-4 text-center">
                                    <?php
                                    $statusClass = [
                                        'approved' => 'bg-green-100 text-green-700',
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                        'rejected' => 'bg-red-100 text-red-700'
                                    ][$place['status']] ?? 'bg-gray-100 text-gray-700';
                                    $statusText = [
                                        'approved' => 'อนุมัติ',
                                        'pending' => 'รออนุมัติ',
                                        'rejected' => 'ปฏิเสธ'
                                    ][$place['status']] ?? $place['status'];
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-sm <?php echo $statusClass; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center text-gray-600">
                                    <?php echo number_format($place['view_count']); ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="flex items-center justify-center gap-1">
                                        <i class="fas fa-star text-yellow-400"></i>
                                        <?php echo number_format($place['avg_rating'] ?? 0, 1); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="<?php echo BASE_URL; ?>/pages/public/place.php?slug=<?php echo h($place['slug']); ?>"
                                            target="_blank"
                                            class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button onclick="editPlace(<?php echo $place['id']; ?>)"
                                            class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 text-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deletePlace(<?php echo $place['id']; ?>)"
                                            class="px-3 py-1 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add/Edit Place Modal -->
<div id="place-modal" class="fixed inset-0 hidden z-50 overflow-y-auto">
    <div class="min-h-screen px-4 py-8 flex items-start justify-center">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal()"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl max-w-4xl w-full my-8 max-h-[90vh] flex flex-col">
            <div class="sticky top-0 bg-white border-b px-6 py-4 rounded-t-2xl z-10 flex-shrink-0">
                <h3 class="text-xl font-bold text-gray-800" id="modal-title">
                    <i class="fas fa-plus-circle text-green-500 mr-2"></i>เพิ่มสถานที่ใหม่
                </h3>
                <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form id="place-form" class="p-6 overflow-y-auto flex-grow" onsubmit="return false;">
                <input type="hidden" name="id" id="place-id">

                <!-- Basic Info -->
                <div class="bg-gray-50 rounded-xl p-4 mb-6">
                    <h4 class="font-semibold text-gray-700 mb-4"><i class="fas fa-info-circle mr-2"></i>ข้อมูลพื้นฐาน</h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ (ภาษาไทย) <span class="text-red-500">*</span></label>
                            <input type="text" name="name_th" id="name_th" required
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ (English)</label>
                            <input type="text" name="name_en" id="name_en"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">หมวดหมู่</label>
                            <select name="category_id" id="category_id" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500">
                                <option value="">-- เลือกหมวดหมู่ --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo h($cat['name_th']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">จังหวัด</label>
                            <select name="province_id" id="province_id" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500">
                                <option value="">-- เลือกจังหวัด --</option>
                                <?php foreach ($provinces as $prov): ?>
                                    <option value="<?php echo $prov['id']; ?>"><?php echo h($prov['name_th']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด</label>
                        <textarea name="description_th" id="description_th" rows="4"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500"></textarea>
                    </div>
                </div>

                <!-- Location -->
                <div class="bg-gray-50 rounded-xl p-4 mb-6">
                    <h4 class="font-semibold text-gray-700 mb-4"><i class="fas fa-map-marker-alt mr-2"></i>ตำแหน่งที่ตั้ง</h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                            <input type="text" name="latitude" id="latitude" placeholder="เช่น 13.7563"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                            <input type="text" name="longitude" id="longitude" placeholder="เช่น 100.5018"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">ที่อยู่</label>
                        <textarea name="address" id="address" rows="2"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500"></textarea>
                    </div>
                </div>

                <!-- Media -->
                <div class="bg-gray-50 rounded-xl p-4 mb-6">
                    <h4 class="font-semibold text-gray-700 mb-4"><i class="fas fa-image mr-2"></i>รูปภาพปก</h4>

                    <div id="thumbnail-preview" class="w-full h-40 bg-gray-100 rounded-lg flex items-center justify-center mb-3 overflow-hidden">
                        <span class="text-gray-400"><i class="fas fa-image text-4xl"></i></span>
                    </div>

                    <input type="file" id="thumbnail-input" accept="image/*" class="hidden" onchange="previewImage(this)">
                    <input type="hidden" name="thumbnail" id="thumbnail-hidden">
                    <button type="button" onclick="document.getElementById('thumbnail-input').click()"
                        class="w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-green-500 hover:text-green-500 transition-colors">
                        <i class="fas fa-cloud-upload-alt mr-2"></i>คลิกเพื่ออัพโหลดรูปภาพ
                    </button>
                    <p class="text-xs text-gray-500 mt-2 text-center">รองรับ JPG, PNG, GIF, WebP</p>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="closeModal()" class="flex-1 px-4 py-3 border rounded-lg hover:bg-gray-50">
                        ยกเลิก
                    </button>
                    <button type="button" onclick="submitForm()" class="flex-1 px-4 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600" id="submit-btn">
                        <i class="fas fa-save mr-2"></i>บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
    const baseUrl = '<?php echo BASE_URL; ?>';
    let table;
    let isEditing = false;

    $(document).ready(function() {
        if ($('#places-table').length) {
            table = $('#places-table').DataTable({
                language: {
                    search: "ค้นหา:",
                    lengthMenu: "แสดง _MENU_ รายการ",
                    info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                    infoEmpty: "ไม่มีข้อมูล",
                    zeroRecords: "ไม่พบข้อมูล",
                    paginate: {
                        first: "แรก",
                        last: "สุดท้าย",
                        next: "ถัดไป",
                        previous: "ก่อนหน้า"
                    }
                },
                order: [
                    [0, 'asc']
                ],
                pageLength: 10
            });
        }
    });

    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('thumbnail-preview').innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                document.getElementById('thumbnail-hidden').value = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function openAddModal() {
        isEditing = false;
        document.getElementById('modal-title').innerHTML = '<i class="fas fa-plus-circle text-green-500 mr-2"></i>เพิ่มสถานที่ใหม่';
        document.getElementById('place-form').reset();
        document.getElementById('place-id').value = '';
        document.getElementById('thumbnail-hidden').value = '';
        document.getElementById('thumbnail-preview').innerHTML = '<span class="text-gray-400"><i class="fas fa-image text-4xl"></i></span>';
        document.getElementById('place-modal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    async function editPlace(id) {
        isEditing = true;
        document.getElementById('modal-title').innerHTML = '<i class="fas fa-edit text-yellow-500 mr-2"></i>แก้ไขสถานที่';

        try {
            const response = await fetch(`${baseUrl}/api/owner.php?action=get_place&place_id=${id}`);
            const result = await response.json();

            if (result.success) {
                const place = result.data;
                document.getElementById('place-id').value = place.id;
                document.getElementById('name_th').value = place.name_th || '';
                document.getElementById('name_en').value = place.name_en || '';
                document.getElementById('category_id').value = place.category_id || '';
                document.getElementById('province_id').value = place.province_id || '';
                document.getElementById('description_th').value = place.description_th || '';
                document.getElementById('latitude').value = place.latitude || '';
                document.getElementById('longitude').value = place.longitude || '';
                document.getElementById('address').value = place.address || '';
                document.getElementById('thumbnail-hidden').value = place.thumbnail || '';

                if (place.thumbnail) {
                    document.getElementById('thumbnail-preview').innerHTML = `<img src="${place.thumbnail}" class="w-full h-full object-cover">`;
                } else {
                    document.getElementById('thumbnail-preview').innerHTML = '<span class="text-gray-400"><i class="fas fa-image text-4xl"></i></span>';
                }

                document.getElementById('place-modal').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        } catch (error) {
            Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลได้', 'error');
        }
    }

    function closeModal() {
        document.getElementById('place-modal').classList.add('hidden');
        document.body.style.overflow = '';
    }

    async function submitForm() {
        const form = document.getElementById('place-form');
        const submitBtn = document.getElementById('submit-btn');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังบันทึก...';
        submitBtn.disabled = true;

        const formData = new FormData(form);
        const action = isEditing ? 'update_place' : 'create_place';

        try {
            const response = await fetch(`${baseUrl}/api/owner.php?action=${action}`, {
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
                    })
                    .then(() => location.reload());
            } else {
                Swal.fire('เกิดข้อผิดพลาด', result.message, 'error');
            }
        } catch (error) {
            Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
        } finally {
            submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>บันทึก';
            submitBtn.disabled = false;
        }
    }

    async function deletePlace(id) {
        const result = await Swal.fire({
            title: 'ลบสถานที่?',
            html: '<span class="text-red-600">ไม่สามารถย้อนกลับได้</span>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก'
        });

        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('place_id', id);
            const response = await fetch(`${baseUrl}/api/owner.php?action=delete_place`, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) location.reload();
            else Swal.fire('เกิดข้อผิดพลาด', data.message, 'error');
        }
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>