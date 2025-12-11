<?php

/**
 * =====================================================
 * Admin Reviews Management - จัดการรีวิว
 * =====================================================
 */

require_once __DIR__ . '/../../config/config.php';
require_once INCLUDES_PATH . '/functions.php';

start_session();
require_login();
require_role('admin');

$db = db();

$pageTitle = 'จัดการรีวิว';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="bg-gray-100 min-h-screen">
    <!-- Admin Header -->
    <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4">
                <a href="<?php echo BASE_URL; ?>/pages/admin/dashboard.php" class="text-gray-400 hover:text-white">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold">
                        <i class="fas fa-comments mr-3"></i>จัดการรีวิว
                    </h1>
                    <p class="text-gray-300 text-sm">อนุมัติ, ลบ และจัดการรีวิวจากผู้ใช้</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Reviews Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <table id="reviews-table" class="w-full stripe hover">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left">ผู้รีวิว</th>
                        <th class="px-6 py-4 text-left">สถานที่</th>
                        <th class="px-6 py-4 text-left">ความคิดเห็น</th>
                        <th class="px-6 py-4 text-center">คะแนน</th>
                        <th class="px-6 py-4 text-center">สถานะ</th>
                        <th class="px-6 py-4 text-center">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $reviews = $db->query("
                        SELECT r.*, u.full_name as user_name, u.email as user_email, p.name_th as place_name, p.slug as place_slug
                        FROM reviews r
                        LEFT JOIN users u ON r.user_id = u.id
                        LEFT JOIN places p ON r.place_id = p.id
                        ORDER BY r.created_at DESC
                    ")->fetchAll();
                    foreach ($reviews as $review):
                    ?>
                        <tr data-id="<?php echo $review['id']; ?>">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-400 rounded-full flex items-center justify-center text-white font-bold">
                                        <?php echo mb_substr($review['user_name'] ?? 'U', 0, 1); ?>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800"><?php echo h($review['user_name'] ?? 'ผู้ใช้'); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo format_date_thai($review['created_at'], 'd M Y'); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <a href="<?php echo BASE_URL; ?>/pages/public/place.php?slug=<?php echo h($review['place_slug']); ?>"
                                    target="_blank" class="text-primary-600 hover:underline">
                                    <?php echo h($review['place_name'] ?? '-'); ?>
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-gray-600 line-clamp-2 max-w-xs">
                                    <?php echo h(mb_substr($review['content'] ?? $review['comment'] ?? '', 0, 80)); ?>...
                                </p>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-1 text-yellow-500">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= ($review['rating'] ?? 0) ? '' : 'text-gray-300'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php
                                $status = $review['status'] ?? 'approved';
                                $statusClass = [
                                    'approved' => 'bg-green-100 text-green-700',
                                    'pending' => 'bg-yellow-100 text-yellow-700',
                                    'spam' => 'bg-red-100 text-red-700'
                                ][$status] ?? 'bg-gray-100 text-gray-700';
                                $statusText = [
                                    'approved' => 'อนุมัติ',
                                    'pending' => 'รอตรวจสอบ',
                                    'spam' => 'Spam'
                                ][$status] ?? $status;
                                ?>
                                <span class="px-3 py-1 rounded-full text-sm <?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <?php if (($review['status'] ?? 'approved') !== 'approved'): ?>
                                        <button onclick="approveReview(<?php echo $review['id']; ?>)"
                                            class="px-3 py-1 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 text-sm" title="อนุมัติ">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if (($review['status'] ?? 'approved') !== 'spam'): ?>
                                        <button onclick="markSpam(<?php echo $review['id']; ?>)"
                                            class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 text-sm" title="Spam">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="deleteReview(<?php echo $review['id']; ?>)"
                                        class="px-3 py-1 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm" title="ลบ">
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

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<style>
    #reviews-table_wrapper .dataTables_filter input {
        padding: 8px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-left: 8px;
    }

    #reviews-table_wrapper .dataTables_length select {
        padding: 8px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin: 0 8px;
    }

    #reviews-table_wrapper .dataTables_paginate .paginate_button {
        padding: 8px 14px;
        margin: 2px;
        border-radius: 8px;
        border: 1px solid #e5e7eb !important;
    }

    #reviews-table_wrapper .dataTables_paginate .paginate_button.current {
        background: linear-gradient(to right, #0ea5e9, #d946ef) !important;
        color: white !important;
        border: none !important;
    }

    #reviews-table_wrapper .dataTables_info,
    #reviews-table_wrapper .dataTables_filter,
    #reviews-table_wrapper .dataTables_length {
        padding: 16px;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
    const baseUrl = '<?php echo BASE_URL; ?>';

    $(document).ready(function() {
        $('#reviews-table').DataTable({
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
                [0, 'desc']
            ],
            pageLength: 25
        });
    });

    async function approveReview(id) {
        const r = await Swal.fire({
            title: 'อนุมัติรีวิว?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#22c55e',
            confirmButtonText: 'อนุมัติ',
            cancelButtonText: 'ยกเลิก'
        });
        if (r.isConfirmed) await updateStatus(id, 'approved');
    }

    async function markSpam(id) {
        const r = await Swal.fire({
            title: 'ทำเครื่องหมายเป็น Spam?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        });
        if (r.isConfirmed) await updateStatus(id, 'spam');
    }

    async function updateStatus(id, status) {
        const formData = new FormData();
        formData.append('review_id', id);
        formData.append('status', status);
        const response = await fetch(`${baseUrl}/api/admin.php?action=update_review_status`, {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) location.reload();
        else Swal.fire('เกิดข้อผิดพลาด', result.message, 'error');
    }

    async function deleteReview(id) {
        const r = await Swal.fire({
            title: 'ลบรีวิว?',
            html: '<span class="text-red-600">ไม่สามารถย้อนกลับได้</span>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก'
        });
        if (r.isConfirmed) {
            const formData = new FormData();
            formData.append('review_id', id);
            const response = await fetch(`${baseUrl}/api/admin.php?action=delete_review`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) location.reload();
            else Swal.fire('เกิดข้อผิดพลาด', result.message, 'error');
        }
    }
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>