<?php

/**
 * =====================================================
 * Admin Users Management - จัดการผู้ใช้
 * =====================================================
 */

require_once __DIR__ . '/../../config/config.php';
require_once INCLUDES_PATH . '/functions.php';

start_session();
require_login();
require_role('admin');

$db = db();

$pageTitle = 'จัดการผู้ใช้';
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
                        <i class="fas fa-users mr-3"></i>จัดการผู้ใช้
                    </h1>
                    <p class="text-gray-300 text-sm">จัดการบัญชีผู้ใช้, สิทธิ์, และสถานะ</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Users Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <table id="users-table" class="w-full stripe hover">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left">ผู้ใช้</th>
                        <th class="px-6 py-4 text-left">Email</th>
                        <th class="px-6 py-4 text-center">Role</th>
                        <th class="px-6 py-4 text-center">สถานะ</th>
                        <th class="px-6 py-4 text-left">วันสมัคร</th>
                        <th class="px-6 py-4 text-center">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $users = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
                    foreach ($users as $user):
                        $roleClass = [
                            'admin' => 'bg-purple-100 text-purple-700',
                            'owner' => 'bg-blue-100 text-blue-700',
                            'user' => 'bg-gray-100 text-gray-700'
                        ][$user['role']] ?? 'bg-gray-100 text-gray-700';
                        $roleText = [
                            'admin' => 'Admin',
                            'owner' => 'ผู้ประกอบการ',
                            'user' => 'นักท่องเที่ยว'
                        ][$user['role']] ?? $user['role'];
                    ?>
                        <tr data-id="<?php echo $user['id']; ?>">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-primary-400 to-accent-400 rounded-full flex items-center justify-center text-white font-bold">
                                        <?php echo mb_substr($user['full_name'], 0, 1); ?>
                                    </div>
                                    <span class="font-medium text-gray-800"><?php echo h($user['full_name']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600"><?php echo h($user['email']); ?></td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full text-sm <?php echo $roleClass; ?>">
                                    <?php echo $roleText; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php
                                $statusClass = [
                                    'active' => 'bg-green-100 text-green-700',
                                    'inactive' => 'bg-gray-100 text-gray-700',
                                    'banned' => 'bg-red-100 text-red-700'
                                ][$user['status']] ?? 'bg-gray-100 text-gray-700';
                                ?>
                                <span class="px-3 py-1 rounded-full text-sm <?php echo $statusClass; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                <?php echo format_date_thai($user['created_at'], 'd M Y'); ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick="editRole(<?php echo $user['id']; ?>, '<?php echo $user['role']; ?>')"
                                        class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-sm" title="แก้ไข Role">
                                        <i class="fas fa-user-cog"></i>
                                    </button>
                                    <?php if ($user['status'] === 'banned'): ?>
                                        <button onclick="unbanUser(<?php echo $user['id']; ?>)"
                                            class="px-3 py-1 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 text-sm" title="Unban">
                                            <i class="fas fa-unlock"></i>
                                        </button>
                                    <?php else: ?>
                                        <button onclick="banUser(<?php echo $user['id']; ?>)"
                                            class="px-3 py-1 bg-orange-100 text-orange-700 rounded-lg hover:bg-orange-200 text-sm" title="Ban">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="deleteUser(<?php echo $user['id']; ?>)"
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
    #users-table_wrapper .dataTables_filter input {
        padding: 8px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-left: 8px;
    }

    #users-table_wrapper .dataTables_length select {
        padding: 8px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin: 0 8px;
    }

    #users-table_wrapper .dataTables_paginate .paginate_button {
        padding: 8px 14px;
        margin: 2px;
        border-radius: 8px;
        border: 1px solid #e5e7eb !important;
    }

    #users-table_wrapper .dataTables_paginate .paginate_button.current {
        background: linear-gradient(to right, #0ea5e9, #d946ef) !important;
        color: white !important;
        border: none !important;
    }

    #users-table_wrapper .dataTables_info,
    #users-table_wrapper .dataTables_filter,
    #users-table_wrapper .dataTables_length {
        padding: 16px;
    }
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
    const baseUrl = '<?php echo BASE_URL; ?>';

    $(document).ready(function() {
        $('#users-table').DataTable({
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
                [4, 'desc']
            ],
            pageLength: 25
        });
    });

    async function editRole(userId, currentRole) {
        const {
            value: newRole
        } = await Swal.fire({
            title: 'เปลี่ยน Role',
            input: 'select',
            inputOptions: {
                'user': 'นักท่องเที่ยว',
                'owner': 'ผู้ประกอบการ',
                'admin': 'Admin'
            },
            inputValue: currentRole,
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก',
            inputValidator: (value) => !value && 'กรุณาเลือก Role'
        });

        if (newRole && newRole !== currentRole) {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('role', newRole);

            const response = await fetch(`${baseUrl}/api/admin.php?action=update_user_role`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) location.reload();
            else Swal.fire('เกิดข้อผิดพลาด', result.message, 'error');
        }
    }

    async function banUser(userId) {
        const r = await Swal.fire({
            title: 'Ban ผู้ใช้?',
            text: 'ผู้ใช้จะไม่สามารถเข้าใช้งานได้',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Ban',
            cancelButtonText: 'ยกเลิก'
        });
        if (r.isConfirmed) await updateStatus(userId, 'banned');
    }

    async function unbanUser(userId) {
        await updateStatus(userId, 'active');
    }

    async function updateStatus(userId, status) {
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('status', status);
        const response = await fetch(`${baseUrl}/api/admin.php?action=update_user_status`, {
            method: 'POST',
            body: formData
        });
        if ((await response.json()).success) location.reload();
    }

    async function deleteUser(userId) {
        const r = await Swal.fire({
            title: 'ลบผู้ใช้?',
            html: '<span class="text-red-600">ไม่สามารถย้อนกลับได้</span>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก'
        });
        if (r.isConfirmed) {
            const formData = new FormData();
            formData.append('user_id', userId);
            const response = await fetch(`${baseUrl}/api/admin.php?action=delete_user`, {
                method: 'POST',
                body: formData
            });
            if ((await response.json()).success) location.reload();
        }
    }
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>