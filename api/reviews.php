<?php

/**
 * =====================================================
 * Reviews API
 * REST API สำหรับจัดการรีวิว
 * =====================================================
 * 
 * GET    /api/reviews.php?place_id=X     - ดึงรีวิวของสถานที่
 * GET    /api/reviews.php?id=X           - ดึงรีวิวตาม ID
 * POST   /api/reviews.php                - สร้างรีวิวใหม่
 * DELETE /api/reviews.php?id=X           - ลบรีวิว
 */

require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/ReviewModel.php';

header('Content-Type: application/json; charset=utf-8');

start_session();

$reviewModel = new ReviewModel();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($reviewModel);
            break;
        case 'POST':
            handlePost($reviewModel);
            break;
        case 'DELETE':
            handleDelete($reviewModel);
            break;
        default:
            json_error('Method not allowed', 405);
    }
} catch (Exception $e) {
    json_error($e->getMessage(), 500);
}

/**
 * GET: ดึงรีวิว
 */
function handleGet($reviewModel)
{
    // ดึงรีวิวตาม ID
    if (isset($_GET['id'])) {
        $review = $reviewModel->getById((int)$_GET['id']);
        if (!$review) {
            json_error('Review not found', 404);
        }

        // Check if current user liked this review
        if (is_logged_in()) {
            $review['user_liked'] = $reviewModel->hasLiked($review['id'], current_user()['id']);
        } else {
            $review['user_liked'] = false;
        }

        json_response(['success' => true, 'review' => $review]);
    }

    // ดึงรีวิวตามสถานที่
    if (isset($_GET['place_id'])) {
        $placeId = (int)$_GET['place_id'];
        $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $reviews = $reviewModel->getByPlaceId($placeId, $limit, $offset);
        $total = $reviewModel->countByPlaceId($placeId);
        $breakdown = $reviewModel->getRatingBreakdown($placeId);

        // Check likes for logged in user
        if (is_logged_in()) {
            $userId = current_user()['id'];
            foreach ($reviews as &$review) {
                $review['user_liked'] = $reviewModel->hasLiked($review['id'], $userId);
            }
        }

        json_response([
            'success' => true,
            'reviews' => $reviews,
            'total' => $total,
            'breakdown' => $breakdown,
            'has_more' => ($offset + $limit) < $total
        ]);
    }

    json_error('Missing required parameters', 400);
}

/**
 * POST: สร้างรีวิวใหม่
 */
function handlePost($reviewModel)
{
    // ต้อง login ก่อน
    if (!is_logged_in()) {
        json_error('กรุณาเข้าสู่ระบบก่อนเขียนรีวิว', 401);
    }

    $user = current_user();

    // Validate input
    $placeId = post('place_id');
    $ratingOverall = post('rating_overall');
    $content = post('content');

    if (empty($placeId) || empty($ratingOverall) || empty($content)) {
        json_error('กรุณากรอกข้อมูลให้ครบถ้วน', 400);
    }

    if ($ratingOverall < 1 || $ratingOverall > 5) {
        json_error('คะแนนต้องอยู่ระหว่าง 1-5', 400);
    }

    if (strlen($content) < 10) {
        json_error('เนื้อหารีวิวต้องมีอย่างน้อย 10 ตัวอักษร', 400);
    }

    // ตรวจสอบว่ารีวิวซ้ำหรือไม่
    if ($reviewModel->hasUserReviewed($placeId, $user['id'])) {
        json_error('คุณเคยรีวิวสถานที่นี้แล้ว', 400);
    }

    // Prepare data
    $data = [
        'place_id' => $placeId,
        'user_id' => $user['id'],
        'rating_overall' => (int)$ratingOverall,
        'rating_cleanliness' => post('rating_cleanliness') ?: null,
        'rating_service' => post('rating_service') ?: null,
        'rating_value' => post('rating_value') ?: null,
        'title' => post('title') ?: null,
        'content' => $content,
        'visit_date' => post('visit_date') ?: null
    ];

    // Create review
    $reviewId = $reviewModel->create($data);

    if (!$reviewId) {
        json_error('ไม่สามารถสร้างรีวิวได้', 500);
    }

    // Upload images if any
    $uploadedImages = [];
    if (!empty($_FILES['images'])) {
        // Reorganize files array for multiple uploads
        $files = [];
        $fileCount = count($_FILES['images']['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $files[] = [
                    'name' => $_FILES['images']['name'][$i],
                    'type' => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error' => $_FILES['images']['error'][$i],
                    'size' => $_FILES['images']['size'][$i]
                ];
            }
        }

        if (!empty($files)) {
            $uploadedImages = $reviewModel->uploadImages($reviewId, $files);
        }
    }

    // Get created review
    $review = $reviewModel->getById($reviewId);

    json_response([
        'success' => true,
        'message' => 'สร้างรีวิวเรียบร้อยแล้ว',
        'review' => $review,
        'images_uploaded' => count($uploadedImages)
    ]);
}

/**
 * DELETE: ลบรีวิว
 */
function handleDelete($reviewModel)
{
    // ต้อง login ก่อน
    if (!is_logged_in()) {
        json_error('กรุณาเข้าสู่ระบบก่อน', 401);
    }

    $user = current_user();
    $reviewId = get('id');

    if (empty($reviewId)) {
        json_error('กรุณาระบุ ID รีวิว', 400);
    }

    // ตรวจสอบว่าเป็นเจ้าของหรือ admin
    $review = $reviewModel->getById($reviewId);

    if (!$review) {
        json_error('ไม่พบรีวิว', 404);
    }

    if ($review['user_id'] != $user['id'] && $user['role'] !== 'admin') {
        json_error('คุณไม่มีสิทธิ์ลบรีวิวนี้', 403);
    }

    // Delete review
    if ($reviewModel->delete($reviewId)) {
        json_response([
            'success' => true,
            'message' => 'ลบรีวิวเรียบร้อยแล้ว'
        ]);
    } else {
        json_error('ไม่สามารถลบรีวิวได้', 500);
    }
}

/**
 * Send JSON response
 */
function json_response($data)
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send JSON error
 */
function json_error($message, $code = 400)
{
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}
