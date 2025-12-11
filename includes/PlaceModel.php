<?php

/**
 * =====================================================
 * Place Model
 * จัดการข้อมูลสถานที่ท่องเที่ยว
 * =====================================================
 */

class PlaceModel
{
    private $db;

    public function __construct()
    {
        $this->db = db();
    }

    /**
     * ดึงสถานที่ทั้งหมด
     */
    public function getAll($limit = 20, $offset = 0)
    {
        $stmt = $this->db->prepare(
            "
            SELECT p.*, 
                   pr.name_th as province_name, 
                   c.name_th as category_name, 
                   c.icon as category_icon
            FROM places p
            LEFT JOIN provinces pr ON p.province_id = pr.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'approved'
            ORDER BY p.popularity_score DESC, p.created_at DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * นับจำนวนสถานที่ทั้งหมด
     */
    public function countAll($filters = [])
    {
        $sql = "SELECT COUNT(DISTINCT p.id) FROM places p 
                LEFT JOIN place_moods pm ON p.id = pm.place_id
                LEFT JOIN place_seasons ps ON p.id = ps.place_id
                WHERE p.status = 'approved'";
        $params = [];

        // Build filters
        if (!empty($filters['keyword'])) {
            $sql .= " AND (p.name_th LIKE ? OR p.name_en LIKE ? OR p.description_th LIKE ?)";
            $keyword = '%' . $filters['keyword'] . '%';
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }

        if (!empty($filters['category'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['province'])) {
            $sql .= " AND p.province_id = ?";
            $params[] = $filters['province'];
        }

        if (!empty($filters['mood'])) {
            $sql .= " AND pm.mood_id = ?";
            $params[] = $filters['mood'];
        }

        if (!empty($filters['budget'])) {
            $sql .= " AND p.budget_level_id = ?";
            $params[] = $filters['budget'];
        }

        if (!empty($filters['season'])) {
            $sql .= " AND ps.season_id = ?";
            $params[] = $filters['season'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * ค้นหาสถานที่
     */
    public function search($filters = [], $limit = 12, $offset = 0, $orderBy = 'popular')
    {
        $params = [];

        $sql = "
            SELECT DISTINCT p.*, 
                   pr.name_th as province_name, 
                   c.name_th as category_name, 
                   c.icon as category_icon
            FROM places p
            LEFT JOIN provinces pr ON p.province_id = pr.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN place_moods pm ON p.id = pm.place_id
            LEFT JOIN place_seasons ps ON p.id = ps.place_id
            WHERE p.status = 'approved'
        ";

        // Build filters using positional parameters only
        if (!empty($filters['keyword'])) {
            $sql .= " AND (p.name_th LIKE ? OR p.name_en LIKE ? OR p.description_th LIKE ?)";
            $keyword = '%' . $filters['keyword'] . '%';
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }

        if (!empty($filters['category'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['province'])) {
            $sql .= " AND p.province_id = ?";
            $params[] = $filters['province'];
        }

        if (!empty($filters['mood'])) {
            $sql .= " AND pm.mood_id = ?";
            $params[] = $filters['mood'];
        }

        if (!empty($filters['budget'])) {
            $sql .= " AND p.budget_level_id = ?";
            $params[] = $filters['budget'];
        }

        if (!empty($filters['season'])) {
            $sql .= " AND ps.season_id = ?";
            $params[] = $filters['season'];
        }

        if (!empty($filters['rating'])) {
            $sql .= " AND p.avg_rating >= ?";
            $params[] = $filters['rating'];
        }

        if (!empty($filters['featured'])) {
            $sql .= " AND p.is_featured = 1";
        }

        // Order By
        switch ($orderBy) {
            case 'newest':
                $sql .= " ORDER BY p.created_at DESC";
                break;
            case 'rating':
                $sql .= " ORDER BY p.avg_rating DESC, p.review_count DESC";
                break;
            case 'views':
                $sql .= " ORDER BY p.view_count DESC";
                break;
            case 'name':
                $sql .= " ORDER BY p.name_th ASC";
                break;
            default:
                $sql .= " ORDER BY p.popularity_score DESC, p.avg_rating DESC";
        }

        // Add limit/offset directly to prevent parameter binding issues
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * ดึงสถานที่ตาม slug
     */
    public function getBySlug($slug)
    {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   pr.name_th as province_name,
                   pr.name_en as province_name_en,
                   c.name_th as category_name, 
                   c.icon as category_icon,
                   bl.name_th as budget_name
            FROM places p
            LEFT JOIN provinces pr ON p.province_id = pr.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN budget_levels bl ON p.budget_level_id = bl.id
            WHERE p.slug = ? AND p.status = 'approved'
        ");
        $stmt->execute([$slug]);
        $place = $stmt->fetch();

        if ($place) {
            $this->incrementView($place['id']);
            $place['images'] = $this->getPlaceImages($place['id']);
            $place['moods'] = $this->getPlaceMoods($place['id']);
            $place['seasons'] = $this->getPlaceSeasons($place['id']);
        }

        return $place;
    }

    /**
     * ดึงสถานที่ตาม ID
     */
    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   pr.name_th as province_name,
                   c.name_th as category_name, 
                   c.icon as category_icon,
                   bl.name_th as budget_name
            FROM places p
            LEFT JOIN provinces pr ON p.province_id = pr.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN budget_levels bl ON p.budget_level_id = bl.id
            WHERE p.id = ? AND p.status = 'approved'
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * ดึงรูปภาพของสถานที่
     */
    public function getPlaceImages($placeId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM place_images 
            WHERE place_id = ? 
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([$placeId]);
        return $stmt->fetchAll();
    }

    /**
     * ดึง moods ของสถานที่
     */
    public function getPlaceMoods($placeId)
    {
        $stmt = $this->db->prepare("
            SELECT m.* FROM moods m
            JOIN place_moods pm ON m.id = pm.mood_id
            WHERE pm.place_id = ?
        ");
        $stmt->execute([$placeId]);
        return $stmt->fetchAll();
    }

    /**
     * ดึง seasons ของสถานที่
     */
    public function getPlaceSeasons($placeId)
    {
        $stmt = $this->db->prepare("
            SELECT s.* FROM seasons s
            JOIN place_seasons ps ON s.id = ps.season_id
            WHERE ps.place_id = ?
        ");
        $stmt->execute([$placeId]);
        return $stmt->fetchAll();
    }

    /**
     * เพิ่ม view count
     */
    public function incrementView($placeId)
    {
        $stmt = $this->db->prepare("UPDATE places SET view_count = view_count + 1 WHERE id = ?");
        $stmt->execute([$placeId]);
    }

    /**
     * ดึงสถานที่ใกล้เคียง
     */
    public function getNearby($lat, $lng, $radius = 50, $limit = 10, $excludeId = null)
    {
        $sql = "
            SELECT p.*, 
                   pr.name_th as province_name, 
                   c.name_th as category_name, 
                   c.icon as category_icon,
                   (6371 * acos(cos(radians(?)) * cos(radians(p.latitude)) * 
                   cos(radians(p.longitude) - radians(?)) + sin(radians(?)) * 
                   sin(radians(p.latitude)))) AS distance
            FROM places p
            LEFT JOIN provinces pr ON p.province_id = pr.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'approved'
            AND p.latitude IS NOT NULL 
            AND p.longitude IS NOT NULL
        ";

        $params = [$lat, $lng, $lat];

        if ($excludeId) {
            $sql .= " AND p.id != ?";
            $params[] = $excludeId;
        }

        $sql .= " HAVING distance < ? ORDER BY distance ASC LIMIT " . (int)$limit;
        $params[] = $radius;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * ดึงสถานที่ยอดนิยม
     */
    public function getFeatured($limit = 8)
    {
        $stmt = $this->db->prepare(
            "
            SELECT p.*, 
                   pr.name_th as province_name, 
                   c.name_th as category_name, 
                   c.icon as category_icon
            FROM places p
            LEFT JOIN provinces pr ON p.province_id = pr.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'approved' AND p.is_featured = 1
            ORDER BY p.popularity_score DESC
            LIMIT " . (int)$limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * ดึงสถานที่ที่เกี่ยวข้อง
     */
    public function getRelated($placeId, $categoryId, $provinceId, $limit = 4)
    {
        $stmt = $this->db->prepare(
            "
            SELECT p.*, 
                   pr.name_th as province_name, 
                   c.name_th as category_name, 
                   c.icon as category_icon
            FROM places p
            LEFT JOIN provinces pr ON p.province_id = pr.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'approved' 
            AND p.id != ?
            AND (p.category_id = ? OR p.province_id = ?)
            ORDER BY p.popularity_score DESC
            LIMIT " . (int)$limit
        );
        $stmt->execute([$placeId, $categoryId, $provinceId]);
        return $stmt->fetchAll();
    }

    /**
     * ดึง Filters สำหรับ Sidebar
     */
    public function getFilters()
    {
        return [
            'categories' => $this->getCategories(),
            'provinces' => $this->getProvinces(),
            'moods' => $this->getMoods(),
            'budgets' => $this->getBudgetLevels(),
            'seasons' => $this->getSeasons()
        ];
    }

    /**
     * ดึงหมวดหมู่ทั้งหมด
     */
    public function getCategories()
    {
        $stmt = $this->db->query("
            SELECT c.*, COUNT(p.id) as place_count 
            FROM categories c
            LEFT JOIN places p ON c.id = p.category_id AND p.status = 'approved'
            WHERE c.is_active = 1
            GROUP BY c.id
            ORDER BY c.sort_order ASC, c.name_th ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * ดึงจังหวัดทั้งหมด (ที่มีสถานที่ท่องเที่ยว)
     */
    public function getProvinces()
    {
        $stmt = $this->db->query("
            SELECT pr.*, COUNT(p.id) as place_count 
            FROM provinces pr
            LEFT JOIN places p ON pr.id = p.province_id AND p.status = 'approved'
            GROUP BY pr.id
            ORDER BY pr.name_th ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * ดึง moods ทั้งหมด
     */
    public function getMoods()
    {
        $stmt = $this->db->query("
            SELECT m.*, COUNT(pm.place_id) as place_count 
            FROM moods m
            LEFT JOIN place_moods pm ON m.id = pm.mood_id
            GROUP BY m.id
            ORDER BY m.name_th ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * ดึงระดับงบประมาณทั้งหมด
     */
    public function getBudgetLevels()
    {
        $stmt = $this->db->query("SELECT * FROM budget_levels ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    /**
     * ดึงฤดูกาลทั้งหมด
     */
    public function getSeasons()
    {
        $stmt = $this->db->query("SELECT * FROM seasons ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    /**
     * ดึงรีวิวของสถานที่
     */
    public function getReviews($placeId, $limit = 10, $offset = 0)
    {
        $stmt = $this->db->prepare(
            "
            SELECT r.*, u.full_name, u.avatar
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.place_id = ? AND r.status = 'approved'
            ORDER BY r.created_at DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset
        );
        $stmt->execute([$placeId]);
        return $stmt->fetchAll();
    }

    /**
     * นับจำนวนรีวิว
     */
    public function countReviews($placeId)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM reviews 
            WHERE place_id = ? AND status = 'approved'
        ");
        $stmt->execute([$placeId]);
        return $stmt->fetchColumn();
    }
}
