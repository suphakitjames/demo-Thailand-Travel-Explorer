<?php

/**
 * =====================================================
 * Search API - สำหรับ Real-time Search Autocomplete
 * =====================================================
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/functions.php';

// Get search query
$query = trim($_GET['q'] ?? '');
$limit = min(10, max(1, (int) ($_GET['limit'] ?? 10)));

if (strlen($query) < 1) {
    echo json_encode(['success' => true, 'results' => [], 'query' => '']);
    exit;
}

$db = db();
$results = [];
$searchQuery = '%' . $query . '%';

try {
    // Search places - use positional parameters only
    $sql = "
        SELECT 
            p.id,
            p.name_th,
            p.slug,
            p.thumbnail,
            pr.name_th as province_name,
            c.name_th as category_name,
            c.icon as category_icon
        FROM places p
        LEFT JOIN provinces pr ON p.province_id = pr.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'approved'
        AND (p.name_th LIKE ? OR p.name_en LIKE ? OR pr.name_th LIKE ?)
        ORDER BY p.popularity_score DESC
        LIMIT " . $limit;

    $stmt = $db->prepare($sql);
    $stmt->execute([$searchQuery, $searchQuery, $searchQuery]);
    $places = $stmt->fetchAll();

    foreach ($places as $place) {
        $results[] = [
            'id' => (int) $place['id'],
            'name' => $place['name_th'],
            'slug' => $place['slug'],
            'thumbnail' => $place['thumbnail'] ?: 'https://via.placeholder.com/100?text=No+Image',
            'province' => $place['province_name'] ?? '',
            'category' => $place['category_name'] ?? '',
            'icon' => $place['category_icon'] ?? 'fa-map-pin',
            'type' => 'place',
            'url' => BASE_URL . '/pages/public/place.php?slug=' . $place['slug']
        ];
    }

    // Search provinces (if less than limit)
    if (count($results) < $limit) {
        $remaining = $limit - count($results);
        $sql2 = "
            SELECT 
                id,
                name_th,
                name_en
            FROM provinces
            WHERE name_th LIKE ? OR name_en LIKE ?
            LIMIT " . $remaining;

        $stmt2 = $db->prepare($sql2);
        $stmt2->execute([$searchQuery, $searchQuery]);
        $provinces = $stmt2->fetchAll();

        foreach ($provinces as $prov) {
            $results[] = [
                'id' => (int) $prov['id'],
                'name' => $prov['name_th'],
                'slug' => '',
                'thumbnail' => '',
                'province' => $prov['name_th'],
                'category' => 'จังหวัด',
                'icon' => 'fa-map',
                'type' => 'province',
                'url' => BASE_URL . '/pages/public/search.php?province=' . $prov['id']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'query' => $query,
        'count' => count($results),
        'results' => $results
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => DEBUG_MODE ? $e->getMessage() : 'Database error',
        'query' => $query
    ], JSON_UNESCAPED_UNICODE);
}
