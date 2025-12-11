<?php

/**
 * =====================================================
 * Review Likes API
 * API สำหรับกด Like/Unlike รีวิว
 * =====================================================
 * 
 * POST /api/review-likes.php  - Toggle like/unlike
 *      Body: { review_id: X }
 */

require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/ReviewModel.php';

header('Content-Type: application/json; charset=utf-8');

start_session();

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Must be logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบก่อน'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $reviewModel = new ReviewModel();
    $user = current_user();

    // Get review_id from POST or JSON body
    $input = json_decode(file_get_contents('php://input'), true);
    $reviewId = $input['review_id'] ?? post('review_id');

    if (empty($reviewId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'กรุณาระบุ review_id'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Check if review exists
    $review = $reviewModel->getById($reviewId);
    if (!$review) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'ไม่พบรีวิว'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Toggle like
    $result = $reviewModel->toggleLike($reviewId, $user['id']);

    echo json_encode([
        'success' => true,
        'liked' => $result['liked'],
        'count' => $result['count'],
        'message' => $result['liked'] ? 'กด Like แล้ว' : 'ยกเลิก Like แล้ว'
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
