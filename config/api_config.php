<?php

/**
 * =====================================================
 * TAT DATA API Configuration
 * การท่องเที่ยวแห่งประเทศไทย API
 * =====================================================
 */

// ======================
// TAT API Settings
// ======================
define('TAT_API_KEY', 'dluV3dY4qTrAiyOat5DxPC8yAGBM4M8g');
define('TAT_API_URL', 'https://tatapi.tourismthailand.org/tatapi/v5');

// ======================
// API Endpoints
// ======================
define('TAT_ENDPOINT_PLACES', TAT_API_URL . '/places');
define('TAT_ENDPOINT_ATTRACTION', TAT_API_URL . '/attraction');
define('TAT_ENDPOINT_ACCOMMODATION', TAT_API_URL . '/accommodation');
define('TAT_ENDPOINT_RESTAURANT', TAT_API_URL . '/restaurant');
define('TAT_ENDPOINT_SHOP', TAT_API_URL . '/shop');
define('TAT_ENDPOINT_EVENT', TAT_API_URL . '/event');

// ======================
// API Request Settings
// ======================
define('TAT_REQUEST_TIMEOUT', 30); // seconds
define('TAT_CACHE_DURATION', 3600); // 1 hour cache

/**
 * TAT API Helper Class
 */
class TatApi
{

    /**
     * Make API Request
     * 
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @return array|false Response data or false on failure
     */
    public static function request($endpoint, $params = [])
    {
        $url = $endpoint;

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => TAT_REQUEST_TIMEOUT,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . TAT_API_KEY,
                'Accept: application/json',
                'Accept-Language: th'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            error_log("TAT API Error: " . $error);
            return false;
        }

        if ($httpCode !== 200) {
            error_log("TAT API HTTP Error: " . $httpCode);
            return false;
        }

        return json_decode($response, true);
    }

    /**
     * Get Places/Attractions
     */
    public static function getAttractions($params = [])
    {
        return self::request(TAT_ENDPOINT_ATTRACTION, $params);
    }

    /**
     * Get Place Details
     */
    public static function getPlaceDetails($placeId)
    {
        return self::request(TAT_ENDPOINT_ATTRACTION . '/' . $placeId);
    }

    /**
     * Search Places
     */
    public static function searchPlaces($keyword, $params = [])
    {
        $params['keyword'] = $keyword;
        return self::request(TAT_ENDPOINT_PLACES . '/search', $params);
    }
}
