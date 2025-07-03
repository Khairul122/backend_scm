<?php
class PetaniModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAllPetani() {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nama_lengkap as created_by_name
            FROM petani p
            JOIN users u ON p.created_by = u.id
            ORDER BY p.created_at DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPetaniById($id) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nama_lengkap as created_by_name
            FROM petani p
            JOIN users u ON p.created_by = u.id
            WHERE p.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getPetaniByTerritory($createdBy) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nama_lengkap as created_by_name
            FROM petani p
            JOIN users u ON p.created_by = u.id
            WHERE p.created_by = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("i", $createdBy);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPetaniByJenisKopi($jenisKopi) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nama_lengkap as created_by_name
            FROM petani p
            JOIN users u ON p.created_by = u.id
            WHERE p.jenis_kopi = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("s", $jenisKopi);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function createPetani($data) {
        $stmt = $this->db->prepare("
            INSERT INTO petani (nama_petani, no_telepon, alamat_kebun, luas_lahan, jenis_kopi, created_by) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "sssdsi",
            $data['nama_petani'],
            $data['no_telepon'],
            $data['alamat_kebun'],
            $data['luas_lahan'],
            $data['jenis_kopi'],
            $data['created_by']
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function updatePetani($id, $data) {
        $fields = [];
        $types = "";
        $values = [];

        if (isset($data['nama_petani'])) {
            $fields[] = "nama_petani = ?";
            $types .= "s";
            $values[] = $data['nama_petani'];
        }

        if (isset($data['no_telepon'])) {
            $fields[] = "no_telepon = ?";
            $types .= "s";
            $values[] = $data['no_telepon'];
        }

        if (isset($data['alamat_kebun'])) {
            $fields[] = "alamat_kebun = ?";
            $types .= "s";
            $values[] = $data['alamat_kebun'];
        }

        if (isset($data['luas_lahan'])) {
            $fields[] = "luas_lahan = ?";
            $types .= "d";
            $values[] = $data['luas_lahan'];
        }

        if (isset($data['jenis_kopi'])) {
            $fields[] = "jenis_kopi = ?";
            $types .= "s";
            $values[] = $data['jenis_kopi'];
        }

        if (empty($fields)) {
            return false;
        }

        $types .= "i";
        $values[] = $id;

        $sql = "UPDATE petani SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    public function deletePetani($id) {
        $stmt = $this->db->prepare("DELETE FROM petani WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getPetaniStats() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_petani,
                SUM(luas_lahan) as total_luas_lahan,
                AVG(luas_lahan) as avg_luas_lahan,
                SUM(CASE WHEN jenis_kopi = 'arabika' THEN 1 ELSE 0 END) as arabika_count,
                SUM(CASE WHEN jenis_kopi = 'robusta' THEN 1 ELSE 0 END) as robusta_count,
                COUNT(DISTINCT created_by) as territory_count
            FROM petani
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getPetaniByLuasLahan($minLuas = null, $maxLuas = null) {
        $sql = "
            SELECT p.*, u.nama_lengkap as created_by_name
            FROM petani p
            JOIN users u ON p.created_by = u.id
        ";
        
        $conditions = [];
        $params = [];
        $types = "";

        if ($minLuas !== null) {
            $conditions[] = "p.luas_lahan >= ?";
            $params[] = $minLuas;
            $types .= "d";
        }

        if ($maxLuas !== null) {
            $conditions[] = "p.luas_lahan <= ?";
            $params[] = $maxLuas;
            $types .= "d";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY p.luas_lahan DESC";

        $stmt = $this->db->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function searchPetani($query) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT p.*, u.nama_lengkap as created_by_name
            FROM petani p
            JOIN users u ON p.created_by = u.id
            WHERE p.nama_petani LIKE ? OR p.no_telepon LIKE ? OR p.alamat_kebun LIKE ?
            ORDER BY p.created_at DESC
            LIMIT 50
        ");
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPetaniCapacity() {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.nama_petani,
                p.luas_lahan,
                p.jenis_kopi,
                u.nama_lengkap as territory_manager,
                ROUND(p.luas_lahan * 800, 2) as estimated_capacity_kg,
                COUNT(bp.id) as total_batches,
                COALESCE(SUM(bp.jumlah_kg), 0) as total_production,
                ROUND((COALESCE(SUM(bp.jumlah_kg), 0) / (p.luas_lahan * 800)) * 100, 2) as capacity_utilization
            FROM petani p
            JOIN users u ON p.created_by = u.id
            LEFT JOIN batch_produksi bp ON p.id = bp.petani_id
            GROUP BY p.id
            ORDER BY capacity_utilization DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getTerritoryStats() {
        $stmt = $this->db->prepare("
            SELECT 
                u.id as territory_id,
                u.nama_lengkap as territory_manager,
                COUNT(p.id) as total_petani,
                SUM(p.luas_lahan) as total_luas_lahan,
                AVG(p.luas_lahan) as avg_luas_lahan,
                SUM(CASE WHEN p.jenis_kopi = 'arabika' THEN 1 ELSE 0 END) as arabika_farmers,
                SUM(CASE WHEN p.jenis_kopi = 'robusta' THEN 1 ELSE 0 END) as robusta_farmers
            FROM users u
            LEFT JOIN petani p ON u.id = p.created_by
            WHERE u.role IN ('admin', 'pengepul')
            GROUP BY u.id, u.nama_lengkap
            ORDER BY total_petani DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPetaniProduction() {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.nama_petani,
                p.luas_lahan,
                p.jenis_kopi,
                COUNT(bp.id) as total_batches,
                COALESCE(SUM(bp.jumlah_kg), 0) as total_production,
                COALESCE(AVG(bp.harga_per_kg), 0) as avg_price_per_kg,
                COALESCE(SUM(bp.jumlah_kg * bp.harga_per_kg), 0) as total_revenue,
                MAX(bp.tanggal_panen) as last_harvest
            FROM petani p
            LEFT JOIN batch_produksi bp ON p.id = bp.petani_id
            GROUP BY p.id
            ORDER BY total_production DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getActivePetani() {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nama_lengkap as created_by_name
            FROM petani p
            JOIN users u ON p.created_by = u.id
            WHERE p.id IN (
                SELECT DISTINCT petani_id 
                FROM batch_produksi 
                WHERE tanggal_panen >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            )
            ORDER BY p.created_at DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getInactivePetani() {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nama_lengkap as created_by_name,
                COALESCE(MAX(bp.tanggal_panen), p.created_at) as last_activity
            FROM petani p
            JOIN users u ON p.created_by = u.id
            LEFT JOIN batch_produksi bp ON p.id = bp.petani_id
            GROUP BY p.id
            HAVING last_activity < DATE_SUB(NOW(), INTERVAL 6 MONTH)
            ORDER BY last_activity ASC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function checkPhoneExists($phone, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT id FROM petani WHERE no_telepon = ? AND id != ?");
            $stmt->bind_param("si", $phone, $excludeId);
        } else {
            $stmt = $this->db->prepare("SELECT id FROM petani WHERE no_telepon = ?");
            $stmt->bind_param("s", $phone);
        }
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function getPetaniWithBatches($petaniId) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nama_lengkap as created_by_name,
                COUNT(bp.id) as total_batches,
                COALESCE(SUM(bp.jumlah_kg), 0) as total_production
            FROM petani p
            JOIN users u ON p.created_by = u.id
            LEFT JOIN batch_produksi bp ON p.id = bp.petani_id
            WHERE p.id = ?
            GROUP BY p.id
        ");
        $stmt->bind_param("i", $petaniId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function bulkUpdateTerritory($petaniIds, $newTerritoryManager) {
        if (empty($petaniIds)) return false;
        
        $placeholders = str_repeat('?,', count($petaniIds) - 1) . '?';
        $sql = "UPDATE petani SET created_by = ? WHERE id IN ($placeholders)";
        
        $stmt = $this->db->prepare($sql);
        $types = str_repeat('i', count($petaniIds));
        $stmt->bind_param('i' . $types, $newTerritoryManager, ...$petaniIds);
        
        return $stmt->execute();
    }

    public function getTopProducers($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT 
                p.nama_petani,
                p.luas_lahan,
                p.jenis_kopi,
                SUM(bp.jumlah_kg) as total_production,
                AVG(bp.harga_per_kg) as avg_price,
                COUNT(bp.id) as total_batches
            FROM petani p
            JOIN batch_produksi bp ON p.id = bp.petani_id
            GROUP BY p.id
            ORDER BY total_production DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}