<?php

/**
 * =====================================================
 * Review Model
 * จัดการข้อมูลรีวิว - Phase 3: ระบบรีวิว
 * =====================================================
 */

class ReviewModel
{
    private $db;

    public function __construct()
    {
        $this->db = db();
    }

    // =====================================================
    // CRUD Operations
    // =====================================================

    /**
     * สร้างรีวิวใหม่
     */
    public function create($data)
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO reviews (
                    place_id, user_id, rating_overall, rating_cleanliness,
                    rating_service, rating_value, title, content, visit_date, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')
            ");

            $stmt->execute([
                $data['place_id'],
                $data['user_id'],
                $data['rating_overall'],
                $data['rating_cleanliness'] ?? null,
                $data['rating_service'] ?? null,
                $data['rating_value'] ?? null,
                $data['title'] ?? null,
                $data['content'],
                $data['visit_date'] ?? null
            ]);

            $reviewId = $this->db->lastInsertId();

            // Update place ratings
            $this->updatePlaceRatings($data['place_id']);

            $this->db->commit();

            return $reviewId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * อัปเดตรีวิว
     */
    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE reviews SET
                rating_overall = ?,
                rating_cleanliness = ?,
                rating_service = ?,
                rating_value = ?,
                title = ?,
                content = ?,
                visit_date = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $result = $stmt->execute([
            $data['rating_overall'],
            $data['rating_cleanliness'] ?? null,
            $data['rating_service'] ?? null,
            $data['rating_value'] ?? null,
            $data['title'] ?? null,
            $data['content'],
            $data['visit_date'] ?? null,
            $id
        ]);

        if ($result) {
            $review = $this->getById($id);
            if ($review) {
                $this->updatePlaceRatings($review['place_id']);
            }
        }

        return $result;
    }

    /**
     * ลบรีวิว
     */
    public function delete($id)
    {
        $review = $this->getById($id);
        if (!$review) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM reviews WHERE id = ?");
        $result = $stmt->execute([$id]);

        if ($result) {
            $this->updatePlaceRatings($review['place_id']);
        }

        return $result;
    }

    /**
     * ดึงรีวิวตาม ID
     */
    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT r.*, u.full_name, u.avatar, u.username
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        $review = $stmt->fetch();

        if ($review) {
            $review['images'] = $this->getImages($id);
        }

        return $review;
    }

    /**
     * ดึงรีวิวตามสถานที่
     */
    public function getByPlaceId($placeId, $limit = 10, $offset = 0)
    {
        $stmt = $this->db->prepare(
            "
            SELECT r.*, u.full_name, u.avatar, u.username
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.place_id = ? AND r.status = 'approved'
            ORDER BY r.created_at DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset
        );
        $stmt->execute([$placeId]);
        $reviews = $stmt->fetchAll();

        // Get images for each review
        foreach ($reviews as &$review) {
            $review['images'] = $this->getImages($review['id']);
        }

        return $reviews;
    }

    /**
     * นับจำนวนรีวิวของสถานที่
     */
    public function countByPlaceId($placeId)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM reviews 
            WHERE place_id = ? AND status = 'approved'
        ");
        $stmt->execute([$placeId]);
        return $stmt->fetchColumn();
    }

    /**
     * ตรวจสอบว่าผู้ใช้เคยรีวิวสถานที่นี้หรือยัง
     */
    public function hasUserReviewed($placeId, $userId)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM reviews 
            WHERE place_id = ? AND user_id = ?
        ");
        $stmt->execute([$placeId, $userId]);
        return $stmt->fetchColumn() > 0;
    }

    // =====================================================
    // Image Management
    // =====================================================

    /**
     * อัปโหลดรูปภาพรีวิว
     */
    public function uploadImages($reviewId, $files)
    {
        $uploadedImages = [];
        $basePath = defined('ROOT_PATH') ?: dirname(__DIR__);
        $uploadDir = $basePath . '/uploads/reviews/';

        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach ($files as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                continue;
            }

            // ตรวจสอบประเภทไฟล์
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowedTypes)) {
                continue;
            }

            // ตรวจสอบขนาด (สูงสุด 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                continue;
            }

            // สร้างชื่อไฟล์ใหม่
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'review_' . $reviewId . '_' . uniqid() . '.' . $ext;
            $filepath = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $imageUrl = BASE_URL . '/uploads/reviews/' . $filename;

                $stmt = $this->db->prepare("
                    INSERT INTO review_images (review_id, image_url) VALUES (?, ?)
                ");
                $stmt->execute([$reviewId, $imageUrl]);

                $uploadedImages[] = [
                    'id' => $this->db->lastInsertId(),
                    'url' => $imageUrl
                ];
            }
        }

        return $uploadedImages;
    }

    /**
     * ดึงรูปภาพของรีวิว
     */
    public function getImages($reviewId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM review_images WHERE review_id = ? ORDER BY id ASC
        ");
        $stmt->execute([$reviewId]);
        return $stmt->fetchAll();
    }

    /**
     * ลบรูปภาพ
     */
    public function deleteImage($imageId, $userId)
    {
        // ตรวจสอบว่าเป็นเจ้าของรีวิวหรือไม่
        $stmt = $this->db->prepare("
            SELECT ri.*, r.user_id 
            FROM review_images ri
            JOIN reviews r ON ri.review_id = r.id
            WHERE ri.id = ?
        ");
        $stmt->execute([$imageId]);
        $image = $stmt->fetch();

        if (!$image || $image['user_id'] != $userId) {
            return false;
        }

        // ลบไฟล์
        $basePath = defined('ROOT_PATH') ?: dirname(__DIR__);
        $filepath = $basePath . '/uploads/reviews/' . basename($image['image_url']);
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        // ลบจากฐานข้อมูล
        $stmt = $this->db->prepare("DELETE FROM review_images WHERE id = ?");
        return $stmt->execute([$imageId]);
    }

    // =====================================================
    // Like System
    // =====================================================

    /**
     * Toggle like/unlike
     */
    public function toggleLike($reviewId, $userId)
    {
        if ($this->hasLiked($reviewId, $userId)) {
            // Unlike
            $stmt = $this->db->prepare("
                DELETE FROM review_likes WHERE review_id = ? AND user_id = ?
            ");
            $stmt->execute([$reviewId, $userId]);

            // Update count
            $stmt = $this->db->prepare("
                UPDATE reviews SET helpful_count = helpful_count - 1 WHERE id = ?
            ");
            $stmt->execute([$reviewId]);

            return ['liked' => false, 'count' => $this->getLikeCount($reviewId)];
        } else {
            // Like
            $stmt = $this->db->prepare("
                INSERT INTO review_likes (user_id, review_id) VALUES (?, ?)
            ");
            $stmt->execute([$userId, $reviewId]);

            // Update count
            $stmt = $this->db->prepare("
                UPDATE reviews SET helpful_count = helpful_count + 1 WHERE id = ?
            ");
            $stmt->execute([$reviewId]);

            return ['liked' => true, 'count' => $this->getLikeCount($reviewId)];
        }
    }

    /**
     * ตรวจสอบว่ากด like หรือยัง
     */
    public function hasLiked($reviewId, $userId)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM review_likes WHERE review_id = ? AND user_id = ?
        ");
        $stmt->execute([$reviewId, $userId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * นับจำนวน like
     */
    public function getLikeCount($reviewId)
    {
        $stmt = $this->db->prepare("SELECT helpful_count FROM reviews WHERE id = ?");
        $stmt->execute([$reviewId]);
        return (int)$stmt->fetchColumn();
    }

    // =====================================================
    // Ratings & Statistics
    // =====================================================

    /**
     * อัปเดตคะแนนเฉลี่ยของสถานที่
     */
    public function updatePlaceRatings($placeId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as review_count,
                COALESCE(AVG(rating_overall), 0) as avg_rating,
                COALESCE(AVG(rating_cleanliness), 0) as avg_rating_cleanliness,
                COALESCE(AVG(rating_service), 0) as avg_rating_service,
                COALESCE(AVG(rating_value), 0) as avg_rating_value
            FROM reviews
            WHERE place_id = ? AND status = 'approved'
        ");
        $stmt->execute([$placeId]);
        $stats = $stmt->fetch();

        $stmt = $this->db->prepare("
            UPDATE places SET
                review_count = ?,
                avg_rating = ?,
                avg_rating_cleanliness = ?,
                avg_rating_service = ?,
                avg_rating_value = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $stats['review_count'],
            round($stats['avg_rating'], 1),
            round($stats['avg_rating_cleanliness'], 1),
            round($stats['avg_rating_service'], 1),
            round($stats['avg_rating_value'], 1),
            $placeId
        ]);

        // คำนวณ popularity score
        $this->calculatePopularityScore($placeId);
    }

    /**
     * คำนวณ Popularity Score
     * Score = (R × 0.4) + (V × 0.3) + (N × 0.3)
     */
    public function calculatePopularityScore($placeId)
    {
        // ดึงข้อมูลสถานที่
        $stmt = $this->db->prepare("
            SELECT p.avg_rating, p.view_count,
                   (SELECT MAX(view_count) FROM places WHERE status = 'approved') as max_views,
                   (SELECT MAX(created_at) FROM reviews WHERE place_id = ? AND status = 'approved') as last_review_date
            FROM places p
            WHERE p.id = ?
        ");
        $stmt->execute([$placeId, $placeId]);
        $data = $stmt->fetch();

        if (!$data) return;

        // R = Rating (40%)
        $R = (float)$data['avg_rating'];

        // V = Views Normalized (30%)
        // Using log scale to prevent high-view places from dominating
        $maxViews = max((int)$data['max_views'], 1);
        $viewCount = max((int)$data['view_count'], 0);
        $V = (log10($viewCount + 1) / log10($maxViews + 1)) * 5;

        // N = Newness/Recency (30%)
        // Reviews from last 30 days get higher weight
        $N = 0;
        if ($data['last_review_date']) {
            $daysSinceReview = (time() - strtotime($data['last_review_date'])) / 86400;
            $N = 5 * exp(-$daysSinceReview / 30);
        }

        // Calculate final score
        $score = ($R * 0.4) + ($V * 0.3) + ($N * 0.3);
        $score = round($score, 2);

        // Update place
        $stmt = $this->db->prepare("
            UPDATE places SET popularity_score = ? WHERE id = ?
        ");
        $stmt->execute([$score, $placeId]);

        return $score;
    }

    /**
     * ดึง Rating Breakdown ของสถานที่
     */
    public function getRatingBreakdown($placeId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                rating_overall,
                COUNT(*) as count
            FROM reviews
            WHERE place_id = ? AND status = 'approved'
            GROUP BY rating_overall
            ORDER BY rating_overall DESC
        ");
        $stmt->execute([$placeId]);
        $results = $stmt->fetchAll();

        // Format to array with all ratings
        $breakdown = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        $total = 0;

        foreach ($results as $row) {
            $breakdown[(int)$row['rating_overall']] = (int)$row['count'];
            $total += (int)$row['count'];
        }

        // Calculate percentages
        $percentages = [];
        foreach ($breakdown as $rating => $count) {
            $percentages[$rating] = $total > 0 ? round(($count / $total) * 100) : 0;
        }

        return [
            'breakdown' => $breakdown,
            'percentages' => $percentages,
            'total' => $total
        ];
    }

    // =====================================================
    // Owner Reply
    // =====================================================

    /**
     * เพิ่มการตอบกลับจากเจ้าของ
     */
    public function addOwnerReply($reviewId, $ownerId, $reply)
    {
        // ตรวจสอบว่าเป็นเจ้าของสถานที่หรือไม่
        $stmt = $this->db->prepare("
            SELECT r.id FROM reviews r
            JOIN places p ON r.place_id = p.id
            WHERE r.id = ? AND p.owner_id = ?
        ");
        $stmt->execute([$reviewId, $ownerId]);

        if (!$stmt->fetch()) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE reviews SET
                owner_reply = ?,
                owner_reply_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$reply, $reviewId]);
    }
}
