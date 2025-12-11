<?php

/**
 * =====================================================
 * Trip Model
 * จัดการข้อมูลทริปท่องเที่ยว
 * =====================================================
 */

class TripModel
{
    private $db;

    public function __construct()
    {
        $this->db = db();
    }

    // ===================================================
    // Trip CRUD Operations
    // ===================================================

    /**
     * สร้างทริปใหม่
     */
    public function create($userId, $name, $description = null, $startDate = null, $endDate = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO trips (user_id, name, description, start_date, end_date, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'planning', NOW())
        ");
        $stmt->execute([$userId, $name, $description, $startDate, $endDate]);
        return $this->db->lastInsertId();
    }

    /**
     * ดึงทริปตาม ID พร้อม items
     */
    public function getById($id, $userId = null)
    {
        $sql = "
            SELECT t.*, u.full_name as owner_name, u.avatar as owner_avatar
            FROM trips t
            JOIN users u ON t.user_id = u.id
            WHERE t.id = ?
        ";
        $params = [$id];

        // ถ้าส่ง userId มา ต้องเป็นเจ้าของ หรือ ทริป public
        if ($userId !== null) {
            $sql .= " AND (t.user_id = ? OR t.is_public = 1)";
            $params[] = $userId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $trip = $stmt->fetch();

        if ($trip) {
            $trip['items'] = $this->getItems($id);
            $trip['days'] = $this->groupItemsByDay($trip['items']);
        }

        return $trip;
    }

    /**
     * ดึงทริปสาธารณะ (ไม่ต้องเช็ค owner)
     */
    public function getPublic($id)
    {
        $stmt = $this->db->prepare("
            SELECT t.*, u.full_name as owner_name, u.avatar as owner_avatar
            FROM trips t
            JOIN users u ON t.user_id = u.id
            WHERE t.id = ? AND t.is_public = 1
        ");
        $stmt->execute([$id]);
        $trip = $stmt->fetch();

        if ($trip) {
            $trip['items'] = $this->getItems($id);
            $trip['days'] = $this->groupItemsByDay($trip['items']);
        }

        return $trip;
    }

    /**
     * ดึงทริปทั้งหมดของผู้ใช้
     */
    public function getByUserId($userId, $limit = 20, $offset = 0)
    {
        $stmt = $this->db->prepare(
            "
            SELECT t.*, 
                   (SELECT COUNT(*) FROM trip_items WHERE trip_id = t.id) as item_count,
                   (SELECT p.thumbnail FROM trip_items ti 
                    JOIN places p ON ti.place_id = p.id 
                    WHERE ti.trip_id = t.id 
                    ORDER BY ti.sort_order ASC LIMIT 1) as cover_thumbnail
            FROM trips t
            WHERE t.user_id = ?
            ORDER BY 
                CASE t.status 
                    WHEN 'ongoing' THEN 1 
                    WHEN 'planning' THEN 2 
                    WHEN 'completed' THEN 3 
                    WHEN 'cancelled' THEN 4 
                END,
                t.updated_at DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * นับจำนวนทริปของผู้ใช้
     */
    public function countByUserId($userId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM trips WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    /**
     * อัปเดตทริป
     */
    public function update($id, $userId, $data)
    {
        $allowedFields = ['name', 'description', 'start_date', 'end_date', 'status', 'is_public'];
        $updates = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        $params[] = $id;
        $params[] = $userId;

        $sql = "UPDATE trips SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * ลบทริป
     */
    public function delete($id, $userId)
    {
        $stmt = $this->db->prepare("DELETE FROM trips WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    // ===================================================
    // Trip Items Operations
    // ===================================================

    /**
     * ดึง items ของทริป
     */
    public function getItems($tripId)
    {
        $stmt = $this->db->prepare("
            SELECT ti.*, 
                   p.name_th as place_name, 
                   p.slug as place_slug,
                   p.thumbnail as place_thumbnail,
                   p.latitude, 
                   p.longitude,
                   p.address,
                   p.avg_rating,
                   pr.name_th as province_name,
                   c.name_th as category_name,
                   c.icon as category_icon
            FROM trip_items ti
            JOIN places p ON ti.place_id = p.id
            LEFT JOIN provinces pr ON p.province_id = pr.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE ti.trip_id = ?
            ORDER BY ti.day_number ASC, ti.sort_order ASC
        ");
        $stmt->execute([$tripId]);
        return $stmt->fetchAll();
    }

    /**
     * จัดกลุ่ม items ตามวัน
     */
    private function groupItemsByDay($items)
    {
        $days = [];
        foreach ($items as $item) {
            $dayNum = $item['day_number'] ?: 1;
            if (!isset($days[$dayNum])) {
                $days[$dayNum] = [];
            }
            $days[$dayNum][] = $item;
        }
        return $days;
    }

    /**
     * เพิ่มสถานที่ในทริป
     */
    public function addItem($tripId, $placeId, $dayNumber = 1, $startTime = null, $endTime = null, $note = null)
    {
        // ตรวจสอบว่าสถานที่ยังไม่อยู่ในทริป
        $stmt = $this->db->prepare("SELECT id FROM trip_items WHERE trip_id = ? AND place_id = ?");
        $stmt->execute([$tripId, $placeId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'สถานที่นี้อยู่ในทริปแล้ว'];
        }

        // หา sort_order สูงสุดของวันนั้น
        $stmt = $this->db->prepare("
            SELECT COALESCE(MAX(sort_order), 0) + 1 FROM trip_items 
            WHERE trip_id = ? AND day_number = ?
        ");
        $stmt->execute([$tripId, $dayNumber]);
        $sortOrder = $stmt->fetchColumn();

        $stmt = $this->db->prepare("
            INSERT INTO trip_items (trip_id, place_id, day_number, sort_order, start_time, end_time, note, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$tripId, $placeId, $dayNumber, $sortOrder, $startTime, $endTime, $note]);

        // อัปเดต total_distance
        $this->updateTripStats($tripId);

        return ['success' => true, 'id' => $this->db->lastInsertId()];
    }

    /**
     * ลบสถานที่ออกจากทริป
     */
    public function removeItem($itemId, $userId)
    {
        // ตรวจสอบ ownership
        $stmt = $this->db->prepare("
            SELECT ti.trip_id FROM trip_items ti
            JOIN trips t ON ti.trip_id = t.id
            WHERE ti.id = ? AND t.user_id = ?
        ");
        $stmt->execute([$itemId, $userId]);
        $result = $stmt->fetch();

        if (!$result) {
            return false;
        }

        $tripId = $result['trip_id'];

        $stmt = $this->db->prepare("DELETE FROM trip_items WHERE id = ?");
        $stmt->execute([$itemId]);

        // อัปเดต total_distance
        $this->updateTripStats($tripId);

        return true;
    }

    /**
     * จัดลำดับ items ใหม่
     */
    public function reorderItems($tripId, $userId, $items)
    {
        // ตรวจสอบ ownership
        $stmt = $this->db->prepare("SELECT id FROM trips WHERE id = ? AND user_id = ?");
        $stmt->execute([$tripId, $userId]);
        if (!$stmt->fetch()) {
            return false;
        }

        foreach ($items as $item) {
            $stmt = $this->db->prepare("
                UPDATE trip_items 
                SET day_number = ?, sort_order = ?
                WHERE id = ? AND trip_id = ?
            ");
            $stmt->execute([
                $item['day_number'] ?? 1,
                $item['sort_order'] ?? 0,
                $item['id'],
                $tripId
            ]);
        }

        // อัปเดต total_distance
        $this->updateTripStats($tripId);

        return true;
    }

    // ===================================================
    // Route Optimization
    // ===================================================

    /**
     * จัดเส้นทางอัตโนมัติด้วย Nearest Neighbor + 2-opt
     */
    public function optimizeRoute($tripId, $userId, $dayNumber = null)
    {
        // ตรวจสอบ ownership
        $stmt = $this->db->prepare("SELECT id FROM trips WHERE id = ? AND user_id = ?");
        $stmt->execute([$tripId, $userId]);
        if (!$stmt->fetch()) {
            return false;
        }

        $items = $this->getItems($tripId);

        if (empty($items)) {
            return true;
        }

        // Group by day และ optimize แต่ละวัน
        $days = $this->groupItemsByDay($items);

        foreach ($days as $day => $dayItems) {
            if ($dayNumber !== null && $day != $dayNumber) {
                continue;
            }

            if (count($dayItems) <= 2) {
                continue; // ไม่ต้อง optimize ถ้ามีแค่ 1-2 จุด
            }

            // สร้าง distance matrix
            $n = count($dayItems);
            $distances = [];

            for ($i = 0; $i < $n; $i++) {
                $distances[$i] = [];
                for ($j = 0; $j < $n; $j++) {
                    if ($i === $j) {
                        $distances[$i][$j] = 0;
                    } else {
                        $distances[$i][$j] = $this->haversine(
                            $dayItems[$i]['latitude'],
                            $dayItems[$i]['longitude'],
                            $dayItems[$j]['latitude'],
                            $dayItems[$j]['longitude']
                        );
                    }
                }
            }

            // Nearest Neighbor Algorithm
            $route = $this->nearestNeighbor($distances, $n);

            // 2-opt Improvement
            $route = $this->twoOpt($route, $distances);

            // Update sort_order ตาม route ใหม่
            foreach ($route as $sortOrder => $originalIndex) {
                $stmt = $this->db->prepare("
                    UPDATE trip_items SET sort_order = ? WHERE id = ?
                ");
                $stmt->execute([$sortOrder, $dayItems[$originalIndex]['id']]);
            }
        }

        // อัปเดต total_distance
        $this->updateTripStats($tripId);

        return true;
    }

    /**
     * Haversine formula - คำนวณระยะทางระหว่าง 2 พิกัด (km)
     */
    public function haversine($lat1, $lon1, $lat2, $lon2)
    {
        if (empty($lat1) || empty($lon1) || empty($lat2) || empty($lon2)) {
            return 0;
        }

        $R = 6371; // Earth radius in km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $R * $c;
    }

    /**
     * Nearest Neighbor Algorithm
     */
    private function nearestNeighbor($distances, $n)
    {
        $visited = array_fill(0, $n, false);
        $route = [0]; // เริ่มจากจุดแรก
        $visited[0] = true;

        for ($i = 1; $i < $n; $i++) {
            $current = end($route);
            $nearest = -1;
            $nearestDist = PHP_FLOAT_MAX;

            for ($j = 0; $j < $n; $j++) {
                if (!$visited[$j] && $distances[$current][$j] < $nearestDist) {
                    $nearest = $j;
                    $nearestDist = $distances[$current][$j];
                }
            }

            if ($nearest !== -1) {
                $route[] = $nearest;
                $visited[$nearest] = true;
            }
        }

        return $route;
    }

    /**
     * 2-opt Improvement
     */
    private function twoOpt($route, $distances)
    {
        $n = count($route);
        $improved = true;

        while ($improved) {
            $improved = false;

            for ($i = 0; $i < $n - 2; $i++) {
                for ($j = $i + 2; $j < $n; $j++) {
                    // คำนวณระยะทางก่อนและหลัง swap
                    $d1 = $distances[$route[$i]][$route[$i + 1]] +
                        ($j + 1 < $n ? $distances[$route[$j]][$route[$j + 1]] : 0);

                    $d2 = $distances[$route[$i]][$route[$j]] +
                        ($j + 1 < $n ? $distances[$route[$i + 1]][$route[$j + 1]] : 0);

                    if ($d2 < $d1) {
                        // Reverse ส่วนระหว่าง i+1 และ j
                        $newRoute = array_slice($route, 0, $i + 1);
                        $reversed = array_reverse(array_slice($route, $i + 1, $j - $i));
                        $rest = array_slice($route, $j + 1);
                        $route = array_merge($newRoute, $reversed, $rest);
                        $improved = true;
                    }
                }
            }
        }

        return $route;
    }

    /**
     * คำนวณระยะทางรวมของทริป
     */
    public function calculateTotalDistance($tripId)
    {
        $items = $this->getItems($tripId);
        $totalDistance = 0;

        for ($i = 0; $i < count($items) - 1; $i++) {
            // คำนวณเฉพาะสถานที่ในวันเดียวกัน
            if ($items[$i]['day_number'] === $items[$i + 1]['day_number']) {
                $totalDistance += $this->haversine(
                    $items[$i]['latitude'],
                    $items[$i]['longitude'],
                    $items[$i + 1]['latitude'],
                    $items[$i + 1]['longitude']
                );
            }
        }

        return round($totalDistance, 2);
    }

    /**
     * ประมาณเวลาเดินทาง (นาที) - สมมติความเร็ว 40 km/h
     */
    public function estimateTravelTime($distance)
    {
        $avgSpeed = 40; // km/h
        return round(($distance / $avgSpeed) * 60);
    }

    /**
     * อัปเดต stats ของทริป (ระยะทาง, เวลา)
     */
    public function updateTripStats($tripId)
    {
        $totalDistance = $this->calculateTotalDistance($tripId);
        $totalDuration = $this->estimateTravelTime($totalDistance);

        $stmt = $this->db->prepare("
            UPDATE trips SET total_distance = ?, total_duration = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$totalDistance, $totalDuration, $tripId]);
    }

    // ===================================================
    // Utility Functions
    // ===================================================

    /**
     * ตรวจสอบว่าเป็นเจ้าของทริปหรือไม่
     */
    public function isOwner($tripId, $userId)
    {
        $stmt = $this->db->prepare("SELECT id FROM trips WHERE id = ? AND user_id = ?");
        $stmt->execute([$tripId, $userId]);
        return $stmt->fetch() !== false;
    }

    /**
     * ดึงรายการทริปของ user (สำหรับ dropdown)
     */
    public function getListForUser($userId)
    {
        $stmt = $this->db->prepare("
            SELECT id, name, status, 
                   (SELECT COUNT(*) FROM trip_items WHERE trip_id = trips.id) as item_count
            FROM trips 
            WHERE user_id = ? AND status IN ('planning', 'ongoing')
            ORDER BY updated_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Copy ทริปไปเป็นของ user อื่น
     */
    public function copyTrip($tripId, $newUserId)
    {
        $original = $this->getPublic($tripId);
        if (!$original) {
            return false;
        }

        // สร้างทริปใหม่
        $newTripId = $this->create(
            $newUserId,
            $original['name'] . ' (คัดลอก)',
            $original['description'],
            $original['start_date'],
            $original['end_date']
        );

        // คัดลอก items
        foreach ($original['items'] as $item) {
            $this->addItem(
                $newTripId,
                $item['place_id'],
                $item['day_number'],
                $item['start_time'],
                $item['end_time'],
                $item['note']
            );
        }

        return $newTripId;
    }
}
