<?php

/**
 * สร้าง Admin user สำหรับทดสอบ
 * ลบไฟล์นี้หลังใช้งาน!
 */

require_once __DIR__ . '/config/config.php';

$db = db();

// ตรวจสอบ users ทั้งหมด
echo "<h2>Users ในระบบ:</h2>";
$users = $db->query("SELECT id, email, full_name, role, status FROM users")->fetchAll();
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Role</th><th>Status</th></tr>";
foreach ($users as $user) {
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['email']}</td>";
    echo "<td>{$user['full_name']}</td>";
    echo "<td>{$user['role']}</td>";
    echo "<td>{$user['status']}</td>";
    echo "</tr>";
}
echo "</table>";

// ถ้ายังไม่มี admin
$adminExists = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetch();

if (!$adminExists) {
    echo "<h3 style='color: orange;'>ไม่มี Admin ในระบบ!</h3>";

    // สร้าง admin user
    if (isset($_GET['create'])) {
        $hashedPassword = password_hash('password123', PASSWORD_BCRYPT);
        $stmt = $db->prepare("
            INSERT INTO users (username, email, password, full_name, role, status, created_at)
            VALUES ('admin', 'admin@tourism.com', ?, 'Admin User', 'admin', 'active', NOW())
        ");
        $stmt->execute([$hashedPassword]);
        echo "<h3 style='color: green;'>สร้าง Admin สำเร็จ! Email: admin@tourism.com, Password: password123</h3>";
        echo "<a href='?'>รีเฟรช</a>";
    } else {
        echo "<a href='?create=1' style='font-size: 20px; padding: 10px 20px; background: green; color: white; text-decoration: none;'>คลิกเพื่อสร้าง Admin</a>";
    }
} else {
    echo "<h3 style='color: green;'>มี Admin ในระบบแล้ว</h3>";

    // Update existing user to admin if requested
    if (isset($_GET['make_admin']) && is_numeric($_GET['make_admin'])) {
        $userId = (int)$_GET['make_admin'];
        $db->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$userId]);
        echo "<h3 style='color: green;'>เปลี่ยน User ID $userId เป็น Admin สำเร็จ!</h3>";
        echo "<a href='?'>รีเฟรช</a>";
    }
}

echo "<br><br><h4>เปลี่ยน User เป็น Admin:</h4>";
foreach ($users as $user) {
    if ($user['role'] !== 'admin') {
        echo "<a href='?make_admin={$user['id']}'>[ทำให้ {$user['email']} เป็น Admin]</a><br>";
    }
}

echo "<br><br><strong style='color: red;'>⚠️ ลบไฟล์นี้หลังใช้งาน!</strong>";
