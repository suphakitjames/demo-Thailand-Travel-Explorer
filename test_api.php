<?php

/**
 * =====================================================
 * TAT API Test Page
 * ทดสอบการเชื่อมต่อ TAT DATA API
 * =====================================================
 */

require_once __DIR__ . '/config/config.php';
require_once INCLUDES_PATH . '/functions.php';

start_session();

// API Endpoints สำหรับทดสอบ
$endpoints = [
    'attraction' => 'สถานที่ท่องเที่ยว',
    'accommodation' => 'ที่พัก',
    'restaurant' => 'ร้านอาหาร',
    'shop' => 'ร้านค้า',
    'event' => 'กิจกรรม/เทศกาล'
];

$selectedEndpoint = $_GET['endpoint'] ?? 'attraction';
$keyword = $_GET['keyword'] ?? '';
$provinceId = $_GET['province'] ?? '';
$result = null;
$error = null;
$isDemo = isset($_GET['demo']);

// ทำการเรียก API
if (isset($_GET['test'])) {

    // Demo Mode - ถ้าเชื่อมต่อ API ไม่ได้
    if ($isDemo) {
        $result = [
            'result' => [
                [
                    'placeId' => 'P03000001',
                    'placeName' => 'วัดพระศรีรัตนศาสดาราม (วัดพระแก้ว)',
                    'thumbnailUrl' => 'https://www.tourismthailand.org/fileadmin/upload_img/Attraction/12054-001.jpg',
                    'location' => ['province' => 'กรุงเทพมหานคร']
                ],
                [
                    'placeId' => 'P08000012',
                    'placeName' => 'อุทยานแห่งชาติดอยอินทนนท์',
                    'thumbnailUrl' => 'https://www.tourismthailand.org/fileadmin/upload_img/Attraction/17656-001.jpg',
                    'location' => ['province' => 'เชียงใหม่']
                ],
                [
                    'placeId' => 'P83000008',
                    'placeName' => 'หาดป่าตอง',
                    'thumbnailUrl' => 'https://www.tourismthailand.org/fileadmin/upload_img/Destination/83/patong-01.jpg',
                    'location' => ['province' => 'ภูเก็ต']
                ],
                [
                    'placeId' => 'P57000001',
                    'placeName' => 'วัดร่องขุ่น',
                    'thumbnailUrl' => 'https://www.tourismthailand.org/fileadmin/upload_img/Attraction/17638-001.jpg',
                    'location' => ['province' => 'เชียงราย']
                ]
            ],
            '_demo' => true
        ];
    } else {
        $url = TAT_API_URL . '/' . $selectedEndpoint;

        $params = [
            'numberOfResult' => 10,
            'pagenumber' => 1
        ];

        if (!empty($keyword)) {
            $params['keyword'] = $keyword;
        }

        if (!empty($provinceId)) {
            $params['provinceId'] = $provinceId;
        }

        // Build URL with params
        $url .= '?' . http_build_query($params);

        // Call API
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . TAT_API_KEY,
                'Accept: application/json',
                'Accept-Language: th'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($curlError) {
            $error = "CURL Error: " . $curlError;
        } elseif ($httpCode !== 200) {
            $error = "HTTP Error: " . $httpCode . " - " . $response;
        } else {
            $result = json_decode($response, true);
        }
    }
}

$pageTitle = 'ทดสอบ TAT API';
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Noto Sans Thai', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-8 mb-8 text-white">
            <h1 class="text-3xl font-bold mb-2">🧪 ทดสอบ TAT DATA API</h1>
            <p class="text-white/80">การท่องเที่ยวแห่งประเทศไทย API</p>
            <div class="mt-4 bg-white/20 rounded-lg p-4">
                <p class="text-sm"><strong>API URL:</strong> <?php echo h(TAT_API_URL); ?></p>
                <p class="text-sm"><strong>API Key:</strong> <?php echo h(substr(TAT_API_KEY, 0, 10)); ?>...***</p>
            </div>
        </div>

        <!-- Test Form -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">🔧 ตั้งค่าการทดสอบ</h2>

            <form method="GET" action="" class="space-y-4">
                <input type="hidden" name="test" value="1">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Endpoint -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Endpoint</label>
                        <select name="endpoint" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <?php foreach ($endpoints as $key => $label): ?>
                                <option value="<?php echo h($key); ?>" <?php echo $selectedEndpoint === $key ? 'selected' : ''; ?>>
                                    <?php echo h($label); ?> (<?php echo h($key); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Keyword -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keyword (ไม่บังคับ)</label>
                        <input type="text" name="keyword" value="<?php echo h($keyword); ?>"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                            placeholder="เช่น ดอยอินทนนท์">
                    </div>

                    <!-- Province ID -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Province ID (ไม่บังคับ)</label>
                        <input type="text" name="province" value="<?php echo h($provinceId); ?>"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                            placeholder="เช่น 1 (กรุงเทพฯ)">
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                        🚀 ทดสอบ API จริง
                    </button>
                    <a href="?test=1&demo=1" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium inline-flex items-center">
                        🎭 Demo Mode
                    </a>
                </div>
            </form>
        </div>

        <!-- Results -->
        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 p-4 mb-8 rounded-r-lg">
                <h3 class="font-bold text-red-700">❌ เกิดข้อผิดพลาด</h3>
                <p class="text-red-600 mb-3"><?php echo h($error); ?></p>
                <div class="bg-white/50 p-3 rounded">
                    <p class="font-medium text-gray-700 mb-2">💡 วิธีแก้ไข:</p>
                    <ul class="text-sm text-gray-600 list-disc list-inside">
                        <li>ตรวจสอบการเชื่อมต่อ Internet</li>
                        <li>ปิด Firewall/Antivirus ชั่วคราว</li>
                        <li>ลองใช้ <a href="?test=1&demo=1" class="text-blue-600 underline font-medium">Demo Mode</a> แทน</li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($result): ?>
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
                <?php if (isset($result['_demo'])): ?>
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 p-4 mb-6 rounded-r-lg">
                        <p class="text-yellow-800 font-medium">🎭 กำลังแสดงข้อมูล Demo Mode (ไม่ใช่ข้อมูลจาก API จริง)</p>
                    </div>
                <?php endif; ?>

                <h2 class="text-xl font-bold text-gray-800 mb-4">📊 ผลลัพธ์</h2>

                <!-- Summary -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-50 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-blue-600">
                            <?php echo isset($result['result']) ? count($result['result']) : 0; ?>
                        </p>
                        <p class="text-sm text-gray-600">รายการที่ได้</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-green-600">✓</p>
                        <p class="text-sm text-gray-600">สำเร็จ</p>
                    </div>
                </div>

                <!-- Data Cards -->
                <?php if (isset($result['result']) && is_array($result['result'])): ?>
                    <h3 class="font-bold text-gray-700 mb-4">รายการข้อมูล:</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <?php foreach ($result['result'] as $item): ?>
                            <div class="border rounded-xl p-4 hover:shadow-lg transition-shadow">
                                <?php if (isset($item['thumbnailUrl'])): ?>
                                    <img src="<?php echo h($item['thumbnailUrl']); ?>" alt=""
                                        class="w-full h-40 object-cover rounded-lg mb-3"
                                        onerror="this.src='https://via.placeholder.com/400x200?text=No+Image'">
                                <?php endif; ?>

                                <h4 class="font-bold text-gray-800">
                                    <?php echo h($item['placeName'] ?? $item['placeNameTh'] ?? 'ไม่มีชื่อ'); ?>
                                </h4>

                                <?php if (isset($item['location']['province'])): ?>
                                    <p class="text-sm text-gray-500">
                                        📍 <?php echo h($item['location']['province']); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (isset($item['placeId'])): ?>
                                    <p class="text-xs text-gray-400 mt-2">
                                        ID: <?php echo h($item['placeId']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Raw JSON -->
                <details class="mt-6">
                    <summary class="cursor-pointer font-medium text-gray-700 hover:text-blue-600">
                        📝 ดู Raw JSON Response
                    </summary>
                    <pre class="mt-4 bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto text-sm max-h-96 overflow-y-auto"><?php
                                                                                                                                    echo h(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                                                                                                                                    ?></pre>
                </details>
            </div>
        <?php endif; ?>

        <!-- Quick Links -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">🔗 ทดสอบด่วน</h2>
            <div class="flex flex-wrap gap-2">
                <a href="?test=1&demo=1" class="px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 font-medium">
                    🎭 Demo Mode
                </a>
                <a href="?test=1&endpoint=attraction" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200">
                    สถานที่ท่องเที่ยว
                </a>
                <a href="?test=1&endpoint=restaurant" class="px-4 py-2 bg-orange-100 text-orange-700 rounded-lg hover:bg-orange-200">
                    ร้านอาหาร
                </a>
                <a href="?test=1&endpoint=accommodation" class="px-4 py-2 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200">
                    ที่พัก
                </a>
            </div>
        </div>

        <!-- Back Link -->
        <div class="text-center mt-8">
            <a href="<?php echo BASE_URL; ?>" class="text-blue-600 hover:underline">
                ← กลับหน้าหลัก
            </a>
        </div>
    </div>
</body>

</html>