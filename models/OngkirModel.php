<?php
class OngkirModel {
    private $db;
    private $apiKey = 'c991b8daf3069a09ca3d0f52b7fcd3c8';
    private $baseUrl = 'https://api.rajaongkir.com/starter/';

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAllProvinsi() {
        $stmt = $this->db->prepare("SELECT * FROM provinsi ORDER BY province ASC");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getKotaByProvinsi($provinsiId) {
        $stmt = $this->db->prepare("SELECT * FROM kota WHERE province_id = ? ORDER BY city_name ASC");
        $stmt->bind_param("i", $provinsiId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAllKota() {
        $stmt = $this->db->prepare("
            SELECT k.*, p.province 
            FROM kota k 
            JOIN provinsi p ON k.province_id = p.province_id 
            ORDER BY p.province ASC, k.city_name ASC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function searchKota($query) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT k.*, p.province 
            FROM kota k 
            JOIN provinsi p ON k.province_id = p.province_id 
            WHERE k.city_name LIKE ? OR p.province LIKE ?
            ORDER BY k.city_name ASC LIMIT 20
        ");
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getCost($origin, $destination, $weight, $courier) {
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
        return $data['rajaongkir']['results'] ?? [];
    }

    public function importProvinsiFromApi() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . 'province');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['key: ' . $this->apiKey]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        
        if ($data && isset($data['rajaongkir']['results'])) {
            foreach ($data['rajaongkir']['results'] as $provinsi) {
                $stmt = $this->db->prepare("
                    INSERT INTO provinsi (province_id, province) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE province = VALUES(province)
                ");
                $stmt->bind_param("is", $provinsi['province_id'], $provinsi['province']);
                $stmt->execute();
            }
        }
    }

    public function importKotaFromApi() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . 'city');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['key: ' . $this->apiKey]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        
        if ($data && isset($data['rajaongkir']['results'])) {
            foreach ($data['rajaongkir']['results'] as $kota) {
                $stmt = $this->db->prepare("
                    INSERT INTO kota (city_id, province_id, type, city_name, postal_code) 
                    VALUES (?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                        type = VALUES(type),
                        city_name = VALUES(city_name),
                        postal_code = VALUES(postal_code)
                ");
                $stmt->bind_param(
                    "iisss", 
                    $kota['city_id'], 
                    $kota['province_id'], 
                    $kota['type'], 
                    $kota['city_name'], 
                    $kota['postal_code']
                );
                $stmt->execute();
            }
        }
    }

    public function getProvinsiById($id) {
        $stmt = $this->db->prepare("SELECT * FROM provinsi WHERE province_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getKotaById($id) {
        $stmt = $this->db->prepare("
            SELECT k.*, p.province 
            FROM kota k 
            JOIN provinsi p ON k.province_id = p.province_id 
            WHERE k.city_id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}