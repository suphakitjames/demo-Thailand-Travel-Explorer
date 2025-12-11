<?php

/**
 * =====================================================
 * Admin Places Management - จัดการสถานที่
 * =====================================================
 */

require_once __DIR__ . '/../../config/config.php';
require_once INCLUDES_PATH . '/functions.php';

start_session();
require_login();
require_role('admin');

$db = db();

// Get categories and provinces for dropdown
$categories = $db->query("SELECT id, name_th FROM categories WHERE is_active = 1 ORDER BY name_th")->fetchAll();
$provinces = $db->query("SELECT id, name_th FROM provinces ORDER BY name_th")->fetchAll();

$pageTitle = 'จัดการสถานที่';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="bg-gray-100 min-h-screen">
    <!-- Admin Header -->
    <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="<?php echo BASE_URL; ?>/pages/admin/dashboard.php" class="text-gray-400 hover:text-white">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold">
                            <i class="fas fa-map-marker-alt mr-3"></i>จัดการสถานที่
                        </h1>
                        <p class="text-gray-300 text-sm">เพิ่ม, แก้ไข, อนุมัติ และจัดการสถานที่ท่องเที่ยว</p>
                    </div>
                </div>
                <button onclick="openAddModal()" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>เพิ่มสถานที่
                </button>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <div class="flex flex-wrap items-center gap-4">
                <span class="text-gray-700 font-medium">กรองสถานะ:</span>
                <div class="flex gap-2">
                    <button onclick="filterStatus('')" class="filter-btn px-4 py-2 rounded-lg border text-sm transition-colors active" data-status="">
                        ทั้งหมด
                    </button>
                    <button onclick="filterStatus('อนุมัติ')" class="filter-btn px-4 py-2 rounded-lg border text-sm transition-colors" data-status="อนุมัติ">
                        <i class="fas fa-check text-green-500 mr-1"></i>อนุมัติ
                    </button>
                    <button onclick="filterStatus('รออนุมัติ')" class="filter-btn px-4 py-2 rounded-lg border text-sm transition-colors" data-status="รออนุมัติ">
                        <i class="fas fa-clock text-yellow-500 mr-1"></i>รออนุมัติ
                    </button>
                    <button onclick="filterStatus('ปฏิเสธ')" class="filter-btn px-4 py-2 rounded-lg border text-sm transition-colors" data-status="ปฏิเสธ">
                        <i class="fas fa-times text-red-500 mr-1"></i>ปฏิเสธ
                    </button>
                </div>
            </div>
        </div>

        <!-- Places Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <table id="places-table" class="w-full stripe hover">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left">สถานที่</th>
                        <th class="px-6 py-4 text-left">หมวดหมู่</th>
                        <th class="px-6 py-4 text-left">จังหวัด</th>
                        <th class="px-6 py-4 text-center">สถานะ</th>
                        <th class="px-6 py-4 text-center">คะแนน</th>
                        <th class="px-6 py-4 text-center">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $places = $db->query("
                        SELECT p.*, c.name_th as category_name, pr.name_th as province_name
                        FROM places p
                        LEFT JOIN categories c ON p.category_id = c.id
                        LEFT JOIN provinces pr ON p.province_id = pr.id
                        ORDER BY p.created_at DESC
                    ")->fetchAll();
                    foreach ($places as $place):
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
                        <tr data-id="<?php echo $place['id']; ?>">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="<?php echo h($place['thumbnail'] ?: 'https://via.placeholder.com/48'); ?>"
                                        alt="" class="w-12 h-12 rounded-lg object-cover">
                                    <a href="<?php echo BASE_URL; ?>/pages/public/place.php?slug=<?php echo h($place['slug']); ?>"
                                        target="_blank" class="font-medium text-gray-800 hover:text-primary-600">
                                        <?php echo h($place['name_th']); ?>
                                    </a>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600"><?php echo h($place['category_name'] ?? '-'); ?></td>
                            <td class="px-6 py-4 text-gray-600"><?php echo h($place['province_name'] ?? '-'); ?></td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full text-sm <?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="flex items-center justify-center gap-1">
                                    <i class="fas fa-star text-yellow-400"></i>
                                    <?php echo number_format($place['avg_rating'] ?? 0, 1); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <?php if ($place['status'] === 'pending'): ?>
                                        <button onclick="approvePlace(<?php echo $place['id']; ?>)"
                                            class="px-3 py-1 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 text-sm" title="อนุมัติ">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button onclick="rejectPlace(<?php echo $place['id']; ?>)"
                                            class="px-3 py-1 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm" title="ปฏิเสธ">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="editPlace(<?php echo $place['id']; ?>)"
                                        class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-sm" title="แก้ไข">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deletePlace(<?php echo $place['id']; ?>)"
                                        class="px-3 py-1 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm" title="ลบ">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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

            <form id="place-form" class="p-6 overflow-y-auto flex-grow">
                <input type="hidden" name="id" id="place-id">
                <input type="hidden" name="thumbnail" id="thumbnail-hidden">

                <!-- Basic Info -->
                <div class="bg-gray-50 rounded-xl p-4 mb-6">
                    <h4 class="font-semibold text-gray-700 mb-4"><i class="fas fa-info-circle mr-2"></i>ข้อมูลพื้นฐาน</h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ (ภาษาไทย) <span class="text-red-500">*</span></label>
                            <input type="text" name="name_th" id="name_th" required
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ (English)</label>
                            <input type="text" name="name_en" id="name_en"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">หมวดหมู่</label>
                            <select name="category_id" id="category_id" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500">
                                <option value="">-- เลือก --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo h($cat['name_th']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">จังหวัด</label>
                            <select name="province_id" id="province_id" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500">
                                <option value="">-- เลือก --</option>
                                <?php foreach ($provinces as $prov): ?>
                                    <option value="<?php echo $prov['id']; ?>"><?php echo h($prov['name_th']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด</label>
                        <textarea name="description_th" id="description_th" rows="3"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500"></textarea>
                    </div>
                </div>

                <!-- Location -->
                <div class="bg-gray-50 rounded-xl p-4 mb-6">
                    <h4 class="font-semibold text-gray-700 mb-4"><i class="fas fa-map-marker-alt mr-2"></i>ตำแหน่งที่ตั้ง</h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                            <input type="text" name="latitude" id="latitude" placeholder="13.7563"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                            <input type="text" name="longitude" id="longitude" placeholder="100.5018"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">ที่อยู่</label>
                        <textarea name="address" id="address" rows="2"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500"></textarea>
                    </div>
                </div>

                <!-- Image Upload & Status -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <h4 class="font-semibold text-gray-700 mb-4"><i class="fas fa-image mr-2"></i>รูปภาพปก</h4>

                        <div id="thumbnail-preview" class="w-full h-40 bg-gray-100 rounded-lg flex items-center justify-center mb-3 overflow-hidden">
                            <span class="text-gray-400"><i class="fas fa-image text-4xl"></i></span>
                        </div>

                        <input type="file" id="thumbnail-input" accept="image/*" class="hidden" onchange="previewImage(this)">
                        <button type="button" onclick="document.getElementById('thumbnail-input').click()"
                            class="w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-primary-500 hover:text-primary-500 transition-colors">
                            <i class="fas fa-cloud-upload-alt mr-2"></i>คลิกเพื่ออัพโหลดรูปภาพ
                        </button>
                        <p class="text-xs text-gray-500 mt-2 text-center">รองรับ JPG, PNG, GIF, WebP</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <h4 class="font-semibold text-gray-700 mb-4"><i class="fas fa-cog mr-2"></i>สถานะ</h4>
                        <select name="status" id="status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500">
                            <option value="approved">อนุมัติ</option>
                            <option value="pending">รออนุมัติ</option>
                            <option value="rejected">ปฏิเสธ</option>
                        </select>
                        <div class="flex items-center gap-4 mt-4">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="is_featured" id="is_featured" class="rounded">
                                แนะนำพิเศษ
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="is_free" id="is_free" class="rounded">
                                เข้าชมฟรี
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="closeModal()" class="flex-1 px-4 py-3 border rounded-lg hover:bg-gray-50">
                        ยกเลิก
                    </button>
                    <button type="submit" class="flex-1 px-4 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600" id="submit-btn">
                        <i class="fas fa-save mr-2"></i>บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<style>
    #places-table_wrapper .dataTables_filter input {
        padding: 8px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-left: 8px;
    }

    #places-table_wrapper .dataTables_length select {
        padding: 8px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin: 0 8px;
    }

    #places-table_wrapper .dataTables_paginate .paginate_button {
        padding: 8px 14px;
        margin: 2px;
        border-radius: 8px;
        border: 1px solid #e5e7eb !important;
    }

    #places-table_wrapper .dataTables_paginate .paginate_button.current {
        background: linear-gradient(to right, #0ea5e9, #d946ef) !important;
        color: white !important;
        border: none !important;
    }

    #places-table_wrapper .dataTables_info,
    #places-table_wrapper .dataTables_filter,
    #places-table_wrapper .dataTables_length {
        padding: 16px;
    }

    .filter-btn {
        border-color: #e5e7eb;
    }

    .filter-btn:hover {
        background: #f3f4f6;
    }

    .filter-btn.active {
        background: linear-gradient(to right, #0ea5e9, #d946ef);
        color: white;
        border-color: transparent;
    }
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
    const baseUrl = '<?php echo BASE_URL; ?>';
    let table;
    let isEditing = false;

    $(document).ready(function() {
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
            pageLength: 25
        });
    });

    function filterStatus(status) {
        table.column(3).search(status).draw();
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelector(`.filter-btn[data-status="${status}"]`).classList.add('active');
    }

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
        document.getElementById('modal-title').innerHTML = '<i class="fas fa-edit text-blue-500 mr-2"></i>แก้ไขสถานที่';

        const response = await fetch(`${baseUrl}/api/admin.php?action=get_place&place_id=${id}`);
        const result = await response.json();

        if (result.success) {
            const p = result.data;
            document.getElementById('place-id').value = p.id;
            document.getElementById('name_th').value = p.name_th || '';
            document.getElementById('name_en').value = p.name_en || '';
            document.getElementById('category_id').value = p.category_id || '';
            document.getElementById('province_id').value = p.province_id || '';
            document.getElementById('description_th').value = p.description_th || '';
            document.getElementById('latitude').value = p.latitude || '';
            document.getElementById('longitude').value = p.longitude || '';
            document.getElementById('address').value = p.address || '';
            document.getElementById('thumbnail-hidden').value = p.thumbnail || '';
            document.getElementById('status').value = p.status || 'approved';
            document.getElementById('is_featured').checked = p.is_featured == 1;
            document.getElementById('is_free').checked = p.is_free == 1;

            if (p.thumbnail) {
                document.getElementById('thumbnail-preview').innerHTML = `<img src="${p.thumbnail}" class="w-full h-full object-cover">`;
            } else {
                document.getElementById('thumbnail-preview').innerHTML = '<span class="text-gray-400"><i class="fas fa-image text-4xl"></i></span>';
            }

            document.getElementById('place-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal() {
        document.getElementById('place-modal').classList.add('hidden');
        document.body.style.overflow = '';
    }

    document.getElementById('place-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('submit-btn');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังบันทึก...';
        btn.disabled = true;

        const formData = new FormData(e.target);
        const action = isEditing ? 'update_place' : 'create_place';

        const response = await fetch(`${baseUrl}/api/admin.php?action=${action}`, {
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
            }).then(() => location.reload());
        } else {
            Swal.fire('เกิดข้อผิดพลาด', result.message, 'error');
            btn.innerHTML = '<i class="fas fa-save mr-2"></i>บันทึก';
            btn.disabled = false;
        }
    });

    async function approvePlace(id) {
        const r = await Swal.fire({
            title: 'อนุมัติ?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#22c55e',
            confirmButtonText: 'อนุมัติ',
            cancelButtonText: 'ยกเลิก'
        });
        if (r.isConfirmed) await updateStatus(id, 'approved');
    }

    async function rejectPlace(id) {
        const r = await Swal.fire({
            title: 'ปฏิเสธ?',
            input: 'text',
            inputPlaceholder: 'เหตุผล...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'ปฏิเสธ',
            cancelButtonText: 'ยกเลิก'
        });
        if (r.isConfirmed) await updateStatus(id, 'rejected', r.value);
    }

    async function updateStatus(id, status, reason = '') {
        const formData = new FormData();
        formData.append('place_id', id);
        formData.append('status', status);
        if (reason) formData.append('reason', reason);
        const response = await fetch(`${baseUrl}/api/admin.php?action=update_place_status`, {
            method: 'POST',
            body: formData
        });
        if ((await response.json()).success) location.reload();
    }

    async function deletePlace(id) {
        const r = await Swal.fire({
            title: 'ลบสถานที่?',
            html: '<span class="text-red-600">ไม่สามารถย้อนกลับได้</span>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก'
        });
        if (r.isConfirmed) {
            const formData = new FormData();
            formData.append('place_id', id);
            const response = await fetch(`${baseUrl}/api/admin.php?action=delete_place`, {
                method: 'POST',
                body: formData
            });
            if ((await response.json()).success) location.reload();
        }
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>