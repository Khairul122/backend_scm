<?php
class OngkirCacheModel {
    private $db;
    private $apiKey = 'c991b8daf3069a09ca3d0f52b7fcd3c8';
    private $baseUrl = 'https://api.rajaongkir.com/starter/';

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAllCache() {
        $stmt = $this->db->prepare("
            SELECT oc.*, 
                ko.city_name as origin_city, po.province as origin_province,
                kd.city_name as destination_city, pd.province as destination_province,
                CASE 
                    WHEN oc.expired_at > NOW() THEN 'active'
                    ELSE 'expired'
                END as cache_status
            FROM ongkir_cache oc
            LEFT JOIN kota ko ON oc.origin_city_id = ko.city_id
            LEFT JOIN provinsi po ON ko.province_id = po.province_id
            LEFT JOIN kota kd ON oc.destination_city_id = kd.city_id
            LEFT JOIN provinsi pd ON kd.province_id = pd.province_id
            ORDER BY oc.created_at DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getCacheById($id) {
        $stmt = $this->db->prepare("
            SELECT oc.*, 
                ko.city_name as origin_city, po.province as origin_province,
                kd.city_name as destination_city, pd.province as destination_province,
                CASE 
                    WHEN oc.expired_at > NOW() THEN 'active'
                    ELSE 'expired'
                END as cache_status
            FROM ongkir_cache oc
            LEFT JOIN kota ko ON oc.origin_city_id = ko.city_id
            LEFT JOIN provinsi po ON ko.province_id = po.province_id
            LEFT JOIN kota kd ON oc.destination_city_id = kd.city_id
            LEFT JOIN provinsi pd ON kd.province_id = pd.province_id
            WHERE oc.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getCacheByParams($origin, $destination, $weight, $courier) {
        $stmt = $this->db->prepare("
            SELECT * FROM ongkir_cache 
            WHERE origin_city_id = ? AND destination_city_id = ? 
            AND weight = ? AND courier = ?
            ORDER BY created_at DESC
        ");
        $stmt->bind_param("iiis", $origin, $destination, $weight, $courier);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getActiveCache($origin, $destination, $weight, $courier) {
        $stmt = $this->db->prepare("
            SELECT * FROM ongkir_cache 
            WHERE origin_city_id = ? AND destination_city_id = ? 
            AND weight = ? AND courier = ? 
            AND expired_at > NOW()
            ORDER BY created_at DESC
        ");
        $stmt->bind_param("iiis", $origin, $destination, $weight, $courier);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function createCache($data) {
        $stmt = $this->db->prepare("
            INSERT INTO ongkir_cache (origin_city_id, destination_city_id, weight, courier, service, cost, etd, expired_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))
        ");
        
        $stmt->bind_param(
            "iiissis",
            $data['origin_city_id'],
            $data['destination_city_id'],
            $data['weight'],
            $data['courier'],
            $data['service'],
            $data['cost'],
            $data['etd']
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function generateCacheFromApi($origin, $destination, $weight, $courier) {
        $url = $this->baseUrl . 'cost';
        $postData = [
            'origin' => $origin,
            'destination' => $destination,
            'weight' => $weight,
            'courier' => $courier
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['key: ' . $this->apiKey]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        
        if ($data && isset($data['rajaongkir']['results'])) {
            $cached = 0;
            foreach ($data['rajaongkir']['results'] as $result) {
                foreach ($result['costs'] as $cost) {
                    $cacheData = [
                        'origin_city_id' => $origin,
                        'destination_city_id' => $destination,
                        'weight' => $weight,
                        'courier' => $courier,
                        'service' => $cost['service'],
                        'cost' => $cost['cost'][0]['value'],
                        'etd' => $cost['cost'][0]['etd']
                    ];
                    
                    if ($this->createCache($cacheData)) {
                        $cached++;
                    }
                }
            }
            return $cached;
        }
        
        return false;
    }

    public function updateCache($id, $data) {
        $fields = [];
        $types = "";
        $values = [];

        if (isset($data['service'])) {
            $fields[] = "service = ?";
            $types .= "s";
            $values[] = $data['service'];
        }

        if (isset($data['cost'])) {
            $fields[] = "cost = ?";
            $types .= "i";
            $values[] = $data['cost'];
        }

        if (isset($data['etd'])) {
            $fields[] = "etd = ?";
            $types .= "s";
            $values[] = $data['etd'];
        }

        if (isset($data['refresh']) && $data['refresh']) {
            $fields[] = "expired_at = DATE_ADD(NOW(), INTERVAL 24 HOUR)";
        }

        if (empty($fields)) {
            return false;
        }

        $types .= "i";
        $values[] = $id;

        $sql = "UPDATE ongkir_cache SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    public function deleteCache($id) {
        $stmt = $this->db->prepare("DELETE FROM ongkir_cache WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function clearExpiredCache() {
        $stmt = $this->db->prepare("DELETE FROM ongkir_cache WHERE expired_at < NOW()");
        
        if ($stmt->execute()) {
            return $stmt->affected_rows;
        }
        return false;
    }

    public function clearAllCache() {
        $stmt = $this->db->prepare("DELETE FROM ongkir_cache");
        
        if ($stmt->execute()) {
            return $stmt->affected_rows;
        }
        return false;
    }

    public function clearOldCache($days = 30) {
        $stmt = $this->db->prepare("DELETE FROM ongkir_cache WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->bind_param("i", $days);
        
        if ($stmt->execute()) {
            return $stmt->affected_rows;
        }
        return false;
    }

    public function getCacheStats() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_cache,
                SUM(CASE WHEN expired_at > NOW() THEN 1 ELSE 0 END) as active_cache,
                SUM(CASE WHEN expired_at <= NOW() THEN 1 ELSE 0 END) as expired_cache,
                COUNT(DISTINCT CONCAT(origin_city_id, '-', destination_city_id)) as unique_routes,
                COUNT(DISTINCT courier) as unique_couriers,
                AVG(cost) as avg_cost,
                MAX(created_at) as last_cache_created
            FROM ongkir_cache
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getCacheHitRate($days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN expired_at > created_at THEN 1 ELSE 0 END) as cache_hits,
                ROUND((SUM(CASE WHEN expired_at > created_at THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as hit_rate
            FROM ongkir_cache
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->bind_param("i", $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getApiUsageStats($days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as api_calls,
                COUNT(DISTINCT CONCAT(origin_city_id, '-', destination_city_id, '-', weight, '-', courier)) as unique_combinations
            FROM ongkir_cache
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stmt->bind_param("i", $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPopularRoutes($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT 
                oc.origin_city_id,
                oc.destination_city_id,
                ko.city_name as origin_city,
                kd.city_name as destination_city,
                COUNT(*) as request_count,
                AVG(oc.cost) as avg_cost,
                MAX(oc.created_at) as last_request
            FROM ongkir_cache oc
            LEFT JOIN kota ko ON oc.origin_city_id = ko.city_id
            LEFT JOIN kota kd ON oc.destination_city_id = kd.city_id
            GROUP BY oc.origin_city_id, oc.destination_city_id
            ORDER BY request_count DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getCacheByCourier() {
        $stmt = $this->db->prepare("
            SELECT 
                courier,
                COUNT(*) as total_cache,
                SUM(CASE WHEN expired_at > NOW() THEN 1 ELSE 0 END) as active_cache,
                AVG(cost) as avg_cost,
                MIN(cost) as min_cost,
                MAX(cost) as max_cost
            FROM ongkir_cache
            GROUP BY courier
            ORDER BY total_cache DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getExpiredCache() {
        $stmt = $this->db->prepare("
            SELECT oc.*, 
                ko.city_name as origin_city,
                kd.city_name as destination_city,
                DATEDIFF(NOW(), oc.expired_at) as days_expired
            FROM ongkir_cache oc
            LEFT JOIN kota ko ON oc.origin_city_id = ko.city_id
            LEFT JOIN kota kd ON oc.destination_city_id = kd.city_id
            WHERE oc.expired_at < NOW()
            ORDER BY oc.expired_at ASC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function refreshExpiredCache() {
        $stmt = $this->db->prepare("
            SELECT DISTINCT origin_city_id, destination_city_id, weight, courier
            FROM ongkir_cache 
            WHERE expired_at < NOW()
            LIMIT 50
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $refreshed = 0;
        while ($row = $result->fetch_assoc()) {
            $this->clearCacheByParams($row['origin_city_id'], $row['destination_city_id'], $row['weight'], $row['courier']);
            
            if ($this->generateCacheFromApi($row['origin_city_id'], $row['destination_city_id'], $row['weight'], $row['courier'])) {
                $refreshed++;
            }
        }
        
        return $refreshed;
    }

    public function clearCacheByParams($origin, $destination, $weight, $courier) {
        $stmt = $this->db->prepare("
            DELETE FROM ongkir_cache 
            WHERE origin_city_id = ? AND destination_city_id = ? AND weight = ? AND courier = ?
        ");
        $stmt->bind_param("iiis", $origin, $destination, $weight, $courier);
        return $stmt->execute();
    }

    public function searchCache($query) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT oc.*, 
                ko.city_name as origin_city,
                kd.city_name as destination_city
            FROM ongkir_cache oc
            LEFT JOIN kota ko ON oc.origin_city_id = ko.city_id
            LEFT JOIN kota kd ON oc.destination_city_id = kd.city_id
            WHERE ko.city_name LIKE ? OR kd.city_name LIKE ? OR oc.courier LIKE ? OR oc.service LIKE ?
            ORDER BY oc.created_at DESC
            LIMIT 50
        ");
        $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function optimizeCache() {
        $stmt = $this->db->prepare("OPTIMIZE TABLE ongkir_cache");
        return $stmt->execute();
    }

    public function getCacheSize() {
        $stmt = $this->db->prepare("
            SELECT 
                ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb,
                table_rows as total_rows
            FROM information_schema.TABLES 
            WHERE table_schema = DATABASE() AND table_name = 'ongkir_cache'
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}