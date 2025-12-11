<?php

/**
 * =====================================================
 * Admin API Endpoint
 * RESTful API สำหรับ Admin
 * =====================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/functions.php';

start_session();

header('Content-Type: application/json; charset=utf-8');

// Check admin role
if (!is_logged_in() || !has_role('admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = db();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$adminId = get_user_id();

// Helper function for JSON response
function jsonResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError($message, $statusCode = 400)
{
    jsonResponse(['success' => false, 'message' => $message], $statusCode);
}

function logActivity($db, $adminId, $action, $targetType, $targetId, $details = null)
{
    try {
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, target_type, target_id, details, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$adminId, $action, $targetType, $targetId, $details ? json_encode($details) : null]);
    } catch (Exception $e) {
        // Silently fail if activity_logs table doesn't exist
    }
}

switch ($action) {
    // =====================================================
    // USER MANAGEMENT
    // =====================================================

    case 'update_user_role':
        if ($method !== 'POST') jsonError('Method not allowed', 405);

        $userId = (int)($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? '';

        if (!$userId || !in_array($role, ['user', 'owner', 'admin'])) {
            jsonError('ข้อมูลไม่ถูกต้อง');
        }

        // Prevent self-demotion
        if ($userId == $adminId && $role !== 'admin') {
            jsonError('ไม่สามารถลดสิทธิ์ตัวเองได้');
        }

        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $result = $stmt->execute([$role, $userId]);

        if ($result) {
            logActivity($db, $adminId, 'update_role', 'user', $userId, ['new_role' => $role]);
            jsonResponse(['success' => true, 'message' => 'อัปเดต Role สำเร็จ']);
        } else {
            jsonError('ไม่สามารถอัปเดตได้');
        }
        break;

    case 'update_user_status':
        if ($method !== 'POST') jsonError('Method not allowed', 405);

        $userId = (int)($_POST['user_id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if (!$userId || !in_array($status, ['active', 'inactive', 'banned'])) {
            jsonError('ข้อมูลไม่ถูกต้อง');
        }

        // Prevent self-ban
        if ($userId == $adminId && $status === 'banned') {
            jsonError('ไม่สามารถ Ban ตัวเองได้');
        }

        $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $result = $stmt->execute([$status, $userId]);

        if ($result) {
            logActivity($db, $adminId, 'update_status', 'user', $userId, ['new_status' => $status]);
            jsonResponse(['success' => true, 'message' => 'อัปเดตสถานะสำเร็จ']);
        } else {
            jsonError('ไม่สามารถอัปเดตได้');
        }
        break;

    case 'delete_user':
        if ($method !== 'POST') jsonError('Method not allowed', 405);

        $userId = (int)($_POST['user_id'] ?? 0);
        if (!$userId) jsonError('ไม่พบผู้ใช้');

        // Prevent self-delete
        if ($userId == $adminId) {
            jsonError('ไม่สามารถลบตัวเองได้');
        }

        // Check if user is admin
        $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user && $user['role'] === 'admin') {
            jsonError('ไม่สามารถลบ Admin ได้');
        }

        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $result = $stmt->execute([$userId]);

        if ($result) {
            logActivity($db, $adminId, 'delete', 'user', $userId);
            jsonResponse(['success' => true, 'message' => 'ลบผู้ใช้สำเร็จ']);
        } else {
            jsonError('ไม่สามารถลบได้');
        }
        break;

    // =====================================================
    // PLACES MANAGEMENT
    // =====================================================

    case 'update_place_status':
        if ($method !== 'POST') jsonError('Method not allowed', 405);

        $placeId = (int)($_POST['place_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $reason = trim($_POST['reason'] ?? '');

        if (!$placeId || !in_array($status, ['approved', 'pending', 'rejected'])) {
            jsonError('ข้อมูลไม่ถูกต้อง');
        }

        $stmt = $db->prepare("UPDATE places SET status = ? WHERE id = ?");
        $result = $stmt->execute([$status, $placeId]);

        if ($result) {
            logActivity($db, $adminId, 'update_status', 'place', $placeId, [
                'new_status' => $status,
                'reason' => $reason ?: null
            ]);
            jsonResponse(['success' => true, 'message' => 'อัปเดตสถานะสำเร็จ']);
        } else {
            jsonError('ไม่สามารถอัปเดตได้');
        }
        break;

    case 'delete_place':
        if ($method !== 'POST') jsonError('Method not allowed', 405);

        $placeId = (int)($_POST['place_id'] ?? 0);
        if (!$placeId) jsonError('ไม่พบสถานที่');

        // Delete related data first
        $db->prepare("DELETE FROM trip_items WHERE place_id = ?")->execute([$placeId]);
        $db->prepare("DELETE FROM reviews WHERE place_id = ?")->execute([$placeId]);

        $stmt = $db->prepare("DELETE FROM places WHERE id = ?");
        $result = $stmt->execute([$placeId]);

        if ($result) {
            logActivity($db, $adminId, 'delete', 'place', $placeId);
            jsonResponse(['success' => true, 'message' => 'ลบสถานที่สำเร็จ']);
        } else {
            jsonError('ไม่สามารถลบได้');
        }
        break;

    case 'get_place':
        $placeId = (int)($_GET['place_id'] ?? 0);
        if (!$placeId) jsonError('ไม่พบสถานที่');

        $stmt = $db->prepare("SELECT * FROM places WHERE id = ?");
        $stmt->execute([$placeId]);
        $place = $stmt->fetch();

        if ($place) {
            jsonResponse(['success' => true, 'data' => $place]);
        } else {
            jsonError('ไม่พบสถานที่');
        }
        break;

    case 'create_place':
        if ($method !== 'POST') jsonError('Method not allowed', 405);

        $name_th = trim($_POST['name_th'] ?? '');
        if (empty($name_th)) jsonError('กรุณากรอกชื่อสถานที่');

        // Generate slug
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $_POST['name_en'] ?? $name_th));
        $slug = trim($slug, '-') . '-' . time();

        // Handle thumbnail upload
        $thumbnailUrl = null;
        if (!empty($_POST['thumbnail'])) {
            // Check if it's base64 data
            if (preg_match('/^data:image\/(\w+);base64,/', $_POST['thumbnail'], $type)) {
                $data = substr($_POST['thumbnail'], strpos($_POST['thumbnail'], ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif

                if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $type = 'jpg';
                }

                $data = base64_decode($data);
                if ($data !== false) {
                    $uploadDir = __DIR__ . '/../uploads/places/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $filename = 'place_' . time() . '_' . uniqid() . '.' . $type;
                    $filepath = $uploadDir . $filename;

                    if (file_put_contents($filepath, $data)) {
                        $thumbnailUrl = BASE_URL . '/uploads/places/' . $filename;
                    }
                }
            } else {
                // It's a URL
                $thumbnailUrl = $_POST['thumbnail'];
            }
        }

        $stmt = $db->prepare("
            INSERT INTO places (
                name_th, name_en, slug, description_th, category_id, province_id,
                latitude, longitude, address, thumbnail, phone, website,
                status, is_featured, is_free, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $result = $stmt->execute([
            $name_th,
            $_POST['name_en'] ?? null,
            $slug,
            $_POST['description_th'] ?? null,
            $_POST['category_id'] ?: null,
            $_POST['province_id'] ?: null,
            $_POST['latitude'] ?: null,
            $_POST['longitude'] ?: null,
            $_POST['address'] ?? null,
            $thumbnailUrl,
            $_POST['phone'] ?? null,
            $_POST['website'] ?? null,
            $_POST['status'] ?? 'approved',
            isset($_POST['is_featured']) ? 1 : 0,
            isset($_POST['is_free']) ? 1 : 0
        ]);

        if ($result) {
            $newId = $db->lastInsertId();
            logActivity($db, $adminId, 'create', 'place', $newId);
            jsonResponse(['success' => true, 'message' => 'เพิ่มสถานที่สำเร็จ', 'id' => $newId]);
        } else {
            jsonError('ไม่สามารถเพิ่มได้');
        }
        break;

    case 'update_place':
        if ($method !== 'POST') jsonError('Method not allowed', 405);

        $placeId = (int)($_POST['id'] ?? 0);
        if (!$placeId) jsonError('ไม่พบสถานที่');

        $name_th = trim($_POST['name_th'] ?? '');
        if (empty($name_th)) jsonError('กรุณากรอกชื่อสถานที่');

        // Get current thumbnail
        $stmt = $db->prepare("SELECT thumbnail FROM places WHERE id = ?");
        $stmt->execute([$placeId]);
        $currentPlace = $stmt->fetch();
        $thumbnailUrl = $currentPlace['thumbnail'] ?? null;

        // Handle thumbnail upload
        if (!empty($_POST['thumbnail'])) {
            // Check if it's base64 data
            if (preg_match('/^data:image\/(\w+);base64,/', $_POST['thumbnail'], $type)) {
                $data = substr($_POST['thumbnail'], strpos($_POST['thumbnail'], ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif

                if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $type = 'jpg';
                }

                $data = base64_decode($data);
                if ($data !== false) {
                    $uploadDir = __DIR__ . '/../uploads/places/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $filename = 'place_' . time() . '_' . uniqid() . '.' . $type;
                    $filepath = $uploadDir . $filename;

                    if (file_put_contents($filepath, $data)) {
                        $thumbnailUrl = BASE_URL . '/uploads/places/' . $filename;
                    }
                }
            } else {
                // It's a URL
                $thumbnailUrl = $_POST['thumbnail'];
            }
        }

        $stmt = $db->prepare("
            UPDATE places SET
                name_th = ?, name_en = ?, description_th = ?, category_id = ?, province_id = ?,
                latitude = ?, longitude = ?, address = ?, thumbnail = ?, phone = ?, website = ?,
                status = ?, is_featured = ?, is_free = ?, updated_at = NOW()
            WHERE id = ?
        ");

        $result = $stmt->execute([
            $name_th,
            $_POST['name_en'] ?? null,
            $_POST['description_th'] ?? null,
            $_POST['category_id'] ?: null,
            $_POST['province_id'] ?: null,
            $_POST['latitude'] ?: null,
            $_POST['longitude'] ?: null,
            $_POST['address'] ?? null,
            $thumbnailUrl,
            $_POST['phone'] ?? null,
            $_POST['website'] ?? null,
            $_POST['status'] ?? 'approved',
            isset($_POST['is_featured']) ? 1 : 0,
            isset($_POST['is_free']) ? 1 : 0,
            $placeId
        ]);

        if ($result) {
            logActivity($db, $adminId, 'update', 'place', $placeId);
            jsonResponse(['success' => true, 'message' => 'อัปเดตสถานที่สำเร็จ']);
        } else {
            jsonError('ไม่สามารถอัปเดตได้');
        }
        break;

    // =====================================================
    // REVIEWS MANAGEMENT
    // =====================================================

    case 'update_review_status':
        if ($method !== 'POST') jsonError('Method not allowed', 405);

        $reviewId = (int)($_POST['review_id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if (!$reviewId || !in_array($status, ['approved', 'pending', 'spam'])) {
            jsonError('ข้อมูลไม่ถูกต้อง');
        }

        $stmt = $db->prepare("UPDATE reviews SET status = ? WHERE id = ?");
        $result = $stmt->execute([$status, $reviewId]);

        if ($result) {
            logActivity($db, $adminId, 'update_status', 'review', $reviewId, ['new_status' => $status]);
            jsonResponse(['success' => true, 'message' => 'อัปเดตสถานะสำเร็จ']);
        } else {
            jsonError('ไม่สามารถอัปเดตได้');
        }
        break;

    case 'delete_review':
        if ($method !== 'POST') jsonError('Method not allowed', 405);

        $reviewId = (int)($_POST['review_id'] ?? 0);
        if (!$reviewId) jsonError('ไม่พบรีวิว');

        $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
        $result = $stmt->execute([$reviewId]);

        if ($result) {
            logActivity($db, $adminId, 'delete', 'review', $reviewId);
            jsonResponse(['success' => true, 'message' => 'ลบรีวิวสำเร็จ']);
        } else {
            jsonError('ไม่สามารถลบได้');
        }
        break;

    // =====================================================
    // STATS & DASHBOARD
    // =====================================================

    case 'stats':
        $stats = [
            'users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'places' => $db->query("SELECT COUNT(*) FROM places")->fetchColumn(),
            'reviews' => $db->query("SELECT COUNT(*) FROM reviews")->fetchColumn(),
            'trips' => $db->query("SELECT COUNT(*) FROM trips")->fetchColumn(),
            'pending_places' => $db->query("SELECT COUNT(*) FROM places WHERE status = 'pending'")->fetchColumn(),
            'pending_reviews' => $db->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetchColumn(),
        ];
        jsonResponse(['success' => true, 'data' => $stats]);
        break;

    default:
        jsonError('Invalid action', 400);
}
