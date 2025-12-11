<?php

/**
 * =====================================================
 * Owner API Endpoint
 * RESTful API สำหรับผู้ประกอบการ
 * =====================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/functions.php';

start_session();

header('Content-Type: application/json; charset=utf-8');

// Check owner/admin role
if (!is_logged_in() || (!has_role('owner') && !has_role('admin'))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = db();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$userId = get_user_id();

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

switch ($action) {
    case 'get_place':
        $placeId = (int)($_GET['place_id'] ?? 0);
        if (!$placeId) jsonError('ไม่พบสถานที่');

        $stmt = $db->prepare("SELECT * FROM places WHERE id = ? AND owner_id = ?");
        $stmt->execute([$placeId, $userId]);
        $place = $stmt->fetch();

        // Admin can view any place
        if (!$place && has_role('admin')) {
            $stmt = $db->prepare("SELECT * FROM places WHERE id = ?");
            $stmt->execute([$placeId]);
            $place = $stmt->fetch();
        }

        if ($place) {
            jsonResponse(['success' => true, 'data' => $place]);
        } else {
            jsonError('ไม่พบสถานที่หรือไม่มีสิทธิ์');
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
                owner_id, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
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
            $userId
        ]);

        if ($result) {
            jsonResponse(['success' => true, 'message' => 'เพิ่มสถานที่สำเร็จ รอการอนุมัติ']);
        } else {
            jsonError('ไม่สามารถเพิ่มได้');
        }
        break;

    case 'update_place':
        if ($method !== 'POST') jsonError('Method not allowed', 405);

        $placeId = (int)($_POST['id'] ?? 0);
        if (!$placeId) jsonError('ไม่พบสถานที่');

        // Check ownership and get current thumbnail
        $stmt = $db->prepare("SELECT owner_id, thumbnail FROM places WHERE id = ?");
        $stmt->execute([$placeId]);
        $place = $stmt->fetch();

        if (!$place || ($place['owner_id'] != $userId && !has_role('admin'))) {
            jsonError('ไม่มีสิทธิ์แก้ไข');
        }

        $name_th = trim($_POST['name_th'] ?? '');
        if (empty($name_th)) jsonError('กรุณากรอกชื่อสถานที่');

        // Handle thumbnail upload
        $thumbnailUrl = $place['thumbnail'] ?? null;
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
                updated_at = NOW()
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
            $placeId
        ]);

        if ($result) {
            jsonResponse(['success' => true, 'message' => 'อัปเดตสถานที่สำเร็จ']);
        } else {
            jsonError('ไม่สามารถอัปเดตได้');
        }
        break;

    case 'delete_place':
        if ($method !== 'POST') jsonError('Method not allowed', 405);

        $placeId = (int)($_POST['place_id'] ?? 0);
        if (!$placeId) jsonError('ไม่พบสถานที่');

        // Check ownership
        $stmt = $db->prepare("SELECT owner_id FROM places WHERE id = ?");
        $stmt->execute([$placeId]);
        $place = $stmt->fetch();

        if (!$place || ($place['owner_id'] != $userId && !has_role('admin'))) {
            jsonError('ไม่มีสิทธิ์ลบ');
        }

        // Delete related data
        $db->prepare("DELETE FROM trip_items WHERE place_id = ?")->execute([$placeId]);
        $db->prepare("DELETE FROM reviews WHERE place_id = ?")->execute([$placeId]);

        $stmt = $db->prepare("DELETE FROM places WHERE id = ?");
        $result = $stmt->execute([$placeId]);

        if ($result) {
            jsonResponse(['success' => true, 'message' => 'ลบสถานที่สำเร็จ']);
        } else {
            jsonError('ไม่สามารถลบได้');
        }
        break;

    default:
        jsonError('Invalid action', 400);
}
