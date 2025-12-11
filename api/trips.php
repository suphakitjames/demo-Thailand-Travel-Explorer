<?php

/**
 * =====================================================
 * Trip API Endpoint
 * RESTful API สำหรับจัดการทริป
 * =====================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/TripModel.php';

start_session();

header('Content-Type: application/json; charset=utf-8');

$tripModel = new TripModel();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Helper function for JSON response
function jsonResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Helper function for error response
function jsonError($message, $statusCode = 400)
{
    jsonResponse(['success' => false, 'message' => $message], $statusCode);
}

// =====================================================
// Public Actions (ไม่ต้อง login)
// =====================================================

if ($action === 'public') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        jsonError('กรุณาระบุ ID ทริป');
    }

    $trip = $tripModel->getPublic($id);
    if (!$trip) {
        jsonError('ไม่พบทริปหรือทริปไม่เปิดสาธารณะ', 404);
    }

    jsonResponse([
        'success' => true,
        'data' => $trip
    ]);
}

// =====================================================
// Protected Actions (ต้อง login)
// =====================================================

if (!is_logged_in()) {
    jsonError('กรุณาเข้าสู่ระบบ', 401);
}

$userId = get_user_id();

switch ($action) {
    // =====================================================
    // LIST - ดึงรายการทริปของ user
    // =====================================================
    case 'list':
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);

        $trips = $tripModel->getByUserId($userId, $limit, $offset);
        $total = $tripModel->countByUserId($userId);

        jsonResponse([
            'success' => true,
            'data' => $trips,
            'total' => $total
        ]);
        break;

    // =====================================================
    // LIST_FOR_SELECT - ดึงรายการทริปสำหรับ dropdown
    // =====================================================
    case 'list_for_select':
        $trips = $tripModel->getListForUser($userId);
        jsonResponse([
            'success' => true,
            'data' => $trips
        ]);
        break;

    // =====================================================
    // CREATE - สร้างทริปใหม่
    // =====================================================
    case 'create':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $startDate = $_POST['start_date'] ?? null;
        $endDate = $_POST['end_date'] ?? null;

        if (empty($name)) {
            jsonError('กรุณาระบุชื่อทริป');
        }

        $tripId = $tripModel->create($userId, $name, $description ?: null, $startDate ?: null, $endDate ?: null);

        if ($tripId) {
            jsonResponse([
                'success' => true,
                'message' => 'สร้างทริปสำเร็จ',
                'data' => ['id' => $tripId]
            ]);
        } else {
            jsonError('ไม่สามารถสร้างทริปได้');
        }
        break;

    // =====================================================
    // GET - ดึงข้อมูลทริป
    // =====================================================
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            jsonError('กรุณาระบุ ID ทริป');
        }

        $trip = $tripModel->getById($id, $userId);
        if (!$trip) {
            jsonError('ไม่พบทริป', 404);
        }

        jsonResponse([
            'success' => true,
            'data' => $trip
        ]);
        break;

    // =====================================================
    // UPDATE - อัปเดตทริป
    // =====================================================
    case 'update':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }

        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if (!$id) {
            jsonError('กรุณาระบุ ID ทริป');
        }

        $data = [];
        if (isset($_POST['name'])) $data['name'] = trim($_POST['name']);
        if (isset($_POST['description'])) $data['description'] = trim($_POST['description']);
        if (isset($_POST['start_date'])) $data['start_date'] = $_POST['start_date'] ?: null;
        if (isset($_POST['end_date'])) $data['end_date'] = $_POST['end_date'] ?: null;
        if (isset($_POST['status'])) $data['status'] = $_POST['status'];
        if (isset($_POST['is_public'])) $data['is_public'] = $_POST['is_public'] ? 1 : 0;

        if (empty($data)) {
            jsonError('ไม่มีข้อมูลที่ต้องอัปเดต');
        }

        $result = $tripModel->update($id, $userId, $data);

        if ($result) {
            jsonResponse([
                'success' => true,
                'message' => 'อัปเดตทริปสำเร็จ'
            ]);
        } else {
            jsonError('ไม่สามารถอัปเดตทริปได้');
        }
        break;

    // =====================================================
    // DELETE - ลบทริป
    // =====================================================
    case 'delete':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }

        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if (!$id) {
            jsonError('กรุณาระบุ ID ทริป');
        }

        $result = $tripModel->delete($id, $userId);

        if ($result) {
            jsonResponse([
                'success' => true,
                'message' => 'ลบทริปสำเร็จ'
            ]);
        } else {
            jsonError('ไม่สามารถลบทริปได้');
        }
        break;

    // =====================================================
    // ADD_ITEM - เพิ่มสถานที่ในทริป
    // =====================================================
    case 'add_item':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }

        $tripId = (int)($_POST['trip_id'] ?? 0);
        $placeId = (int)($_POST['place_id'] ?? 0);
        $dayNumber = (int)($_POST['day_number'] ?? 1);
        $startTime = $_POST['start_time'] ?? null;
        $endTime = $_POST['end_time'] ?? null;
        $note = trim($_POST['note'] ?? '');

        if (!$tripId || !$placeId) {
            jsonError('กรุณาระบุ ID ทริปและสถานที่');
        }

        // ตรวจสอบ ownership
        if (!$tripModel->isOwner($tripId, $userId)) {
            jsonError('คุณไม่มีสิทธิ์แก้ไขทริปนี้', 403);
        }

        $result = $tripModel->addItem($tripId, $placeId, $dayNumber, $startTime ?: null, $endTime ?: null, $note ?: null);

        if ($result['success']) {
            jsonResponse([
                'success' => true,
                'message' => 'เพิ่มสถานที่ในทริปสำเร็จ',
                'data' => ['id' => $result['id']]
            ]);
        } else {
            jsonError($result['message']);
        }
        break;

    // =====================================================
    // REMOVE_ITEM - ลบสถานที่ออกจากทริป
    // =====================================================
    case 'remove_item':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }

        $itemId = (int)($_POST['item_id'] ?? 0);
        if (!$itemId) {
            jsonError('กรุณาระบุ ID รายการ');
        }

        $result = $tripModel->removeItem($itemId, $userId);

        if ($result) {
            jsonResponse([
                'success' => true,
                'message' => 'ลบสถานที่ออกจากทริปสำเร็จ'
            ]);
        } else {
            jsonError('ไม่สามารถลบสถานที่ได้');
        }
        break;

    // =====================================================
    // REORDER - จัดลำดับสถานที่ใหม่
    // =====================================================
    case 'reorder':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }

        $tripId = (int)($_POST['trip_id'] ?? 0);
        $items = json_decode($_POST['items'] ?? '[]', true);

        if (!$tripId || empty($items)) {
            jsonError('กรุณาระบุ ID ทริปและรายการ');
        }

        $result = $tripModel->reorderItems($tripId, $userId, $items);

        if ($result) {
            $trip = $tripModel->getById($tripId, $userId);
            jsonResponse([
                'success' => true,
                'message' => 'จัดลำดับสำเร็จ',
                'data' => [
                    'total_distance' => $trip['total_distance'],
                    'total_duration' => $trip['total_duration']
                ]
            ]);
        } else {
            jsonError('ไม่สามารถจัดลำดับได้');
        }
        break;

    // =====================================================
    // OPTIMIZE - จัดเส้นทางอัตโนมัติ
    // =====================================================
    case 'optimize':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }

        $tripId = (int)($_POST['trip_id'] ?? 0);
        $dayNumber = isset($_POST['day_number']) ? (int)$_POST['day_number'] : null;

        if (!$tripId) {
            jsonError('กรุณาระบุ ID ทริป');
        }

        $result = $tripModel->optimizeRoute($tripId, $userId, $dayNumber);

        if ($result) {
            $trip = $tripModel->getById($tripId, $userId);
            jsonResponse([
                'success' => true,
                'message' => 'จัดเส้นทางสำเร็จ',
                'data' => [
                    'items' => $trip['items'],
                    'total_distance' => $trip['total_distance'],
                    'total_duration' => $trip['total_duration']
                ]
            ]);
        } else {
            jsonError('ไม่สามารถจัดเส้นทางได้');
        }
        break;

    // =====================================================
    // COPY - คัดลอกทริปสาธารณะ
    // =====================================================
    case 'copy':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }

        $tripId = (int)($_POST['trip_id'] ?? 0);
        if (!$tripId) {
            jsonError('กรุณาระบุ ID ทริป');
        }

        $newTripId = $tripModel->copyTrip($tripId, $userId);

        if ($newTripId) {
            jsonResponse([
                'success' => true,
                'message' => 'คัดลอกทริปสำเร็จ',
                'data' => ['id' => $newTripId]
            ]);
        } else {
            jsonError('ไม่สามารถคัดลอกทริปได้ (ทริปต้องเป็นสาธารณะ)');
        }
        break;

    default:
        jsonError('Invalid action', 400);
}
