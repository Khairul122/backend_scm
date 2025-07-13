<?php
class AlamatPengirimanModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAllAlamat() {
        $stmt = $this->db->prepare("
            SELECT ap.*, u.nama_lengkap, u.email, p.province, k.city_name, k.type
            FROM alamat_pengiriman ap
            JOIN users u ON ap.user_id = u.id
            JOIN provinsi p ON ap.province_id = p.province_id
            JOIN kota k ON ap.city_id = k.city_id
            ORDER BY ap.created_at DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAlamatByUser($userId) {
        $stmt = $this->db->prepare("
            SELECT ap.*, p.province, k.city_name, k.type
            FROM alamat_pengiriman ap
            JOIN provinsi p ON ap.province_id = p.province_id
            JOIN kota k ON ap.city_id = k.city_id
            WHERE ap.user_id = ?
            ORDER BY ap.is_default DESC, ap.created_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAlamatById($id) {
        $stmt = $this->db->prepare("
            SELECT ap.*, u.nama_lengkap, u.email, p.province, k.city_name, k.type
            FROM alamat_pengiriman ap
            JOIN users u ON ap.user_id = u.id
            JOIN provinsi p ON ap.province_id = p.province_id
            JOIN kota k ON ap.city_id = k.city_id
            WHERE ap.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function createAlamat($data) {
        if ($data['is_default']) {
            $this->unsetDefaultAlamat($data['user_id']);
        }

        $stmt = $this->db->prepare("
            INSERT INTO alamat_pengiriman (user_id, label, nama_penerima, no_telepon, province_id, city_id, alamat_lengkap, kode_pos, is_default) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "isssiissi",
            $data['user_id'],
            $data['label'],
            $data['nama_penerima'],
            $data['no_telepon'],
            $data['province_id'],
            $data['city_id'],
            $data['alamat_lengkap'],
            $data['kode_pos'],
            $data['is_default']
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function updateAlamat($id, $data) {
        if (isset($data['is_default']) && $data['is_default']) {
            $alamat = $this->getAlamatById($id);
            if ($alamat) {
                $this->unsetDefaultAlamat($alamat['user_id']);
            }
        }

        $fields = [];
        $types = "";
        $values = [];

        if (isset($data['label'])) {
            $fields[] = "label = ?";
            $types .= "s";
            $values[] = $data['label'];
        }

        if (isset($data['nama_penerima'])) {
            $fields[] = "nama_penerima = ?";
            $types .= "s";
            $values[] = $data['nama_penerima'];
        }

        if (isset($data['no_telepon'])) {
            $fields[] = "no_telepon = ?";
            $types .= "s";
            $values[] = $data['no_telepon'];
        }

        if (isset($data['province_id'])) {
            $fields[] = "province_id = ?";
            $types .= "i";
            $values[] = $data['province_id'];
        }

        if (isset($data['city_id'])) {
            $fields[] = "city_id = ?";
            $types .= "i";
            $values[] = $data['city_id'];
        }

        if (isset($data['alamat_lengkap'])) {
            $fields[] = "alamat_lengkap = ?";
            $types .= "s";
            $values[] = $data['alamat_lengkap'];
        }

        if (isset($data['kode_pos'])) {
            $fields[] = "kode_pos = ?";
            $types .= "s";
            $values[] = $data['kode_pos'];
        }

        if (isset($data['is_default'])) {
            $fields[] = "is_default = ?";
            $types .= "i";
            $values[] = $data['is_default'];
        }

        if (empty($fields)) {
            return false;
        }

        $types .= "i";
        $values[] = $id;

        $sql = "UPDATE alamat_pengiriman SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    public function deleteAlamat($id) {
        $stmt = $this->db->prepare("DELETE FROM alamat_pengiriman WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function searchAlamat($query) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT ap.*, u.nama_lengkap, u.email, p.province, k.city_name, k.type
            FROM alamat_pengiriman ap
            JOIN users u ON ap.user_id = u.id
            JOIN provinsi p ON ap.province_id = p.province_id
            JOIN kota k ON ap.city_id = k.city_id
            WHERE ap.nama_penerima LIKE ? OR ap.no_telepon LIKE ? OR ap.alamat_lengkap LIKE ? 
                OR u.nama_lengkap LIKE ? OR u.email LIKE ? OR p.province LIKE ? OR k.city_name LIKE ?
            ORDER BY ap.created_at DESC
            LIMIT 50
        ");
        $stmt->bind_param("sssssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAlamatStats() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_alamat,
                COUNT(DISTINCT user_id) as unique_users,
                SUM(is_default) as default_alamat,
                AVG(CHAR_LENGTH(alamat_lengkap)) as avg_address_length
            FROM alamat_pengiriman
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getSuspiciousAlamat() {
        $stmt = $this->db->prepare("
            SELECT ap.*, u.nama_lengkap, u.email, p.province, k.city_name, k.type,
                COUNT(*) as duplicate_count
            FROM alamat_pengiriman ap
            JOIN users u ON ap.user_id = u.id
            JOIN provinsi p ON ap.province_id = p.province_id
            JOIN kota k ON ap.city_id = k.city_id
            WHERE ap.alamat_lengkap IN (
                SELECT alamat_lengkap 
                FROM alamat_pengiriman 
                GROUP BY alamat_lengkap 
                HAVING COUNT(*) > 1
            )
            OR ap.no_telepon IN (
                SELECT no_telepon 
                FROM alamat_pengiriman 
                GROUP BY no_telepon 
                HAVING COUNT(*) > 3
            )
            GROUP BY ap.id
            ORDER BY duplicate_count DESC, ap.created_at DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAlamatByProvince($provinceId) {
        $stmt = $this->db->prepare("
            SELECT ap.*, u.nama_lengkap, u.email, p.province, k.city_name, k.type
            FROM alamat_pengiriman ap
            JOIN users u ON ap.user_id = u.id
            JOIN provinsi p ON ap.province_id = p.province_id
            JOIN kota k ON ap.city_id = k.city_id
            WHERE ap.province_id = ?
            ORDER BY ap.created_at DESC
        ");
        $stmt->bind_param("i", $provinceId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAlamatByCity($cityId) {
        $stmt = $this->db->prepare("
            SELECT ap.*, u.nama_lengkap, u.email, p.province, k.city_name, k.type
            FROM alamat_pengiriman ap
            JOIN users u ON ap.user_id = u.id
            JOIN provinsi p ON ap.province_id = p.province_id
            JOIN kota k ON ap.city_id = k.city_id
            WHERE ap.city_id = ?
            ORDER BY ap.created_at DESC
        ");
        $stmt->bind_param("i", $cityId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function unsetDefaultAlamat($userId) {
        $stmt = $this->db->prepare("UPDATE alamat_pengiriman SET is_default = 0 WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }

    public function validateAlamatData($data) {
        $stmt = $this->db->prepare("SELECT province_id FROM provinsi WHERE province_id = ?");
        $stmt->bind_param("i", $data['province_id']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            return false;
        }

        $stmt = $this->db->prepare("SELECT city_id FROM kota WHERE city_id = ? AND province_id = ?");
        $stmt->bind_param("ii", $data['city_id'], $data['province_id']);
        $stmt->execute();
        
        return $stmt->get_result()->num_rows > 0;
    }
}