<?php
class PembeliAlamatModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAllAddresses($userId) {
        $stmt = $this->db->prepare("
            SELECT 
                ap.*,
                prov.province as province_name,
                kota.city_name,
                kota.type as city_type,
                kota.postal_code as city_postal_code
            FROM alamat_pengiriman ap
            JOIN provinsi prov ON ap.province_id = prov.province_id
            JOIN kota ON ap.city_id = kota.city_id
            WHERE ap.user_id = ?
            ORDER BY ap.is_default DESC, ap.created_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAddressById($addressId, $userId) {
        $stmt = $this->db->prepare("
            SELECT 
                ap.*,
                prov.province as province_name,
                kota.city_name,
                kota.type as city_type,
                kota.postal_code as city_postal_code
            FROM alamat_pengiriman ap
            JOIN provinsi prov ON ap.province_id = prov.province_id
            JOIN kota ON ap.city_id = kota.city_id
            WHERE ap.id = ? AND ap.user_id = ?
        ");
        $stmt->bind_param("ii", $addressId, $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function createAddress($userId, $data) {
        if (!$this->validateLocationIds($data['province_id'], $data['city_id'])) {
            return false;
        }

        if (isset($data['is_default']) && $data['is_default']) {
            $this->removeDefaultAddress($userId);
        }

        $stmt = $this->db->prepare("
            INSERT INTO alamat_pengiriman 
            (user_id, label, nama_penerima, no_telepon, province_id, city_id, alamat_lengkap, kode_pos, is_default) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $isDefault = isset($data['is_default']) ? (int)$data['is_default'] : 0;
        
        $stmt->bind_param(
            "isssiissi",
            $userId,
            $data['label'],
            $data['nama_penerima'],
            $data['no_telepon'],
            $data['province_id'],
            $data['city_id'],
            $data['alamat_lengkap'],
            $data['kode_pos'],
            $isDefault
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function updateAddress($addressId, $userId, $data) {
        if (!$this->checkAddressOwnership($addressId, $userId)) {
            return false;
        }

        if (isset($data['province_id']) && isset($data['city_id'])) {
            if (!$this->validateLocationIds($data['province_id'], $data['city_id'])) {
                return false;
            }
        }

        if (isset($data['is_default']) && $data['is_default']) {
            $this->removeDefaultAddress($userId);
        }

        $fields = [];
        $types = "";
        $values = [];

        $allowedFields = ['label', 'nama_penerima', 'no_telepon', 'province_id', 'city_id', 'alamat_lengkap', 'kode_pos', 'is_default'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                if (in_array($field, ['province_id', 'city_id', 'is_default'])) {
                    $types .= "i";
                } else {
                    $types .= "s";
                }
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $types .= "ii";
        $values[] = $addressId;
        $values[] = $userId;

        $sql = "UPDATE alamat_pengiriman SET " . implode(", ", $fields) . " WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    public function deleteAddress($addressId, $userId) {
        if (!$this->checkAddressOwnership($addressId, $userId)) {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*) as order_count 
            FROM pesanan 
            WHERE alamat_pengiriman_id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $addressId, $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['order_count'] > 0) {
            return $this->updateAddress($addressId, $userId, ['label' => '[DELETED] ' . date('Y-m-d')]);
        }

        $stmt = $this->db->prepare("
            DELETE FROM alamat_pengiriman WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $addressId, $userId);
        return $stmt->execute();
    }

    public function setDefaultAddress($addressId, $userId) {
        if (!$this->checkAddressOwnership($addressId, $userId)) {
            return false;
        }

        $this->db->begin_transaction();
        
        try {
            $this->removeDefaultAddress($userId);
            
            $stmt = $this->db->prepare("
                UPDATE alamat_pengiriman SET is_default = 1 WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("ii", $addressId, $userId);
            $stmt->execute();
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function getDefaultAddress($userId) {
        $stmt = $this->db->prepare("
            SELECT 
                ap.*,
                prov.province as province_name,
                kota.city_name,
                kota.type as city_type
            FROM alamat_pengiriman ap
            JOIN provinsi prov ON ap.province_id = prov.province_id
            JOIN kota ON ap.city_id = kota.city_id
            WHERE ap.user_id = ? AND ap.is_default = 1
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function searchAddresses($userId, $query) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT 
                ap.*,
                prov.province as province_name,
                kota.city_name
            FROM alamat_pengiriman ap
            JOIN provinsi prov ON ap.province_id = prov.province_id
            JOIN kota ON ap.city_id = kota.city_id
            WHERE ap.user_id = ? 
            AND (ap.label LIKE ? OR ap.nama_penerima LIKE ? OR ap.alamat_lengkap LIKE ? OR kota.city_name LIKE ?)
            ORDER BY ap.is_default DESC, ap.created_at DESC
        ");
        $stmt->bind_param("issss", $userId, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAddressesByCity($userId, $cityId) {
        $stmt = $this->db->prepare("
            SELECT 
                ap.*,
                prov.province as province_name,
                kota.city_name
            FROM alamat_pengiriman ap
            JOIN provinsi prov ON ap.province_id = prov.province_id
            JOIN kota ON ap.city_id = kota.city_id
            WHERE ap.user_id = ? AND ap.city_id = ?
            ORDER BY ap.is_default DESC, ap.created_at DESC
        ");
        $stmt->bind_param("ii", $userId, $cityId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAddressesByProvince($userId, $provinceId) {
        $stmt = $this->db->prepare("
            SELECT 
                ap.*,
                prov.province as province_name,
                kota.city_name
            FROM alamat_pengiriman ap
            JOIN provinsi prov ON ap.province_id = prov.province_id
            JOIN kota ON ap.city_id = kota.city_id
            WHERE ap.user_id = ? AND ap.province_id = ?
            ORDER BY ap.is_default DESC, ap.created_at DESC
        ");
        $stmt->bind_param("ii", $userId, $provinceId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAddressStatistics($userId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_addresses,
                COUNT(CASE WHEN is_default = 1 THEN 1 END) as default_addresses,
                COUNT(DISTINCT province_id) as provinces_covered,
                COUNT(DISTINCT city_id) as cities_covered,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_addresses
            FROM alamat_pengiriman 
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getFrequentlyUsedAddresses($userId, $limit = 5) {
        $stmt = $this->db->prepare("
            SELECT 
                ap.*,
                prov.province as province_name,
                kota.city_name,
                COUNT(p.id) as usage_count,
                MAX(p.created_at) as last_used
            FROM alamat_pengiriman ap
            JOIN provinsi prov ON ap.province_id = prov.province_id
            JOIN kota ON ap.city_id = kota.city_id
            LEFT JOIN pesanan p ON ap.id = p.alamat_pengiriman_id
            WHERE ap.user_id = ?
            GROUP BY ap.id, ap.user_id, ap.label, ap.nama_penerima, ap.no_telepon, 
                     ap.province_id, ap.city_id, ap.alamat_lengkap, ap.kode_pos, 
                     ap.is_default, ap.created_at, prov.province, kota.city_name
            ORDER BY usage_count DESC, last_used DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function validatePostalCode($kodePos, $cityId) {
        $stmt = $this->db->prepare("
            SELECT postal_code FROM kota WHERE city_id = ?
        ");
        $stmt->bind_param("i", $cityId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!$result || !$result['postal_code']) {
            return true;
        }
        
        return substr($kodePos, 0, 2) === substr($result['postal_code'], 0, 2);
    }

    public function getProvinces() {
        $stmt = $this->db->prepare("
            SELECT province_id, province 
            FROM provinsi 
            ORDER BY province ASC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getCitiesByProvince($provinceId) {
        $stmt = $this->db->prepare("
            SELECT city_id, city_name, type, postal_code 
            FROM kota 
            WHERE province_id = ? 
            ORDER BY city_name ASC
        ");
        $stmt->bind_param("i", $provinceId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function duplicateAddress($addressId, $userId, $newLabel = null) {
        $originalAddress = $this->getAddressById($addressId, $userId);
        
        if (!$originalAddress) {
            return false;
        }

        $newAddressData = [
            'label' => $newLabel ?? ($originalAddress['label'] . ' (Copy)'),
            'nama_penerima' => $originalAddress['nama_penerima'],
            'no_telepon' => $originalAddress['no_telepon'],
            'province_id' => $originalAddress['province_id'],
            'city_id' => $originalAddress['city_id'],
            'alamat_lengkap' => $originalAddress['alamat_lengkap'],
            'kode_pos' => $originalAddress['kode_pos'],
            'is_default' => 0
        ];

        return $this->createAddress($userId, $newAddressData);
    }

    public function getAddressUsageHistory($addressId, $userId) {
        if (!$this->checkAddressOwnership($addressId, $userId)) {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.kode_pesanan,
                p.total,
                p.status_pesanan,
                p.created_at,
                COUNT(dp.id) as total_items
            FROM pesanan p
            LEFT JOIN detail_pesanan dp ON p.id = dp.pesanan_id
            WHERE p.alamat_pengiriman_id = ? AND p.user_id = ?
            GROUP BY p.id, p.kode_pesanan, p.total, p.status_pesanan, p.created_at
            ORDER BY p.created_at DESC
            LIMIT 20
        ");
        $stmt->bind_param("ii", $addressId, $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function checkAddressOwnership($addressId, $userId) {
        $stmt = $this->db->prepare("
            SELECT id FROM alamat_pengiriman WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $addressId, $userId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    private function removeDefaultAddress($userId) {
        $stmt = $this->db->prepare("
            UPDATE alamat_pengiriman SET is_default = 0 WHERE user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }

    private function validateLocationIds($provinceId, $cityId) {
        $stmt = $this->db->prepare("
            SELECT k.city_id 
            FROM kota k 
            JOIN provinsi p ON k.province_id = p.province_id 
            WHERE p.province_id = ? AND k.city_id = ?
        ");
        $stmt->bind_param("ii", $provinceId, $cityId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
}