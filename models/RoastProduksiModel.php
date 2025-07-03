<?php
class RoastProduksiModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAvailableGreenBeanBatches() {
        $stmt = $this->db->prepare("
            SELECT bp.*, p.nama_petani, p.alamat_kebun,
                CASE 
                    WHEN bp.status = 'selesai' THEN 'Available for Roasting'
                    WHEN bp.status = 'terjual' THEN 'Sold'
                    ELSE 'Processing'
                END as availability_status,
                DATEDIFF(NOW(), bp.tanggal_panen) as days_since_harvest
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            WHERE bp.status IN ('selesai', 'terjual')
            ORDER BY bp.tanggal_panen DESC, bp.status ASC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAvailableBatchesOnly() {
        $stmt = $this->db->prepare("
            SELECT bp.*, p.nama_petani, p.alamat_kebun,
                DATEDIFF(NOW(), bp.tanggal_panen) as days_since_harvest,
                CASE 
                    WHEN DATEDIFF(NOW(), bp.tanggal_panen) <= 30 THEN 'Fresh'
                    WHEN DATEDIFF(NOW(), bp.tanggal_panen) <= 60 THEN 'Good'
                    WHEN DATEDIFF(NOW(), bp.tanggal_panen) <= 90 THEN 'Aging'
                    ELSE 'Old'
                END as freshness_grade
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            WHERE bp.status = 'selesai'
            ORDER BY bp.tanggal_panen DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getBatchById($id) {
        $stmt = $this->db->prepare("
            SELECT bp.*, p.nama_petani, p.alamat_kebun, p.no_telepon,
                DATEDIFF(NOW(), bp.tanggal_panen) as days_since_harvest
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            WHERE bp.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getBatchesByJenisKopi($jenisKopi) {
        $stmt = $this->db->prepare("
            SELECT bp.*, p.nama_petani, p.alamat_kebun,
                DATEDIFF(NOW(), bp.tanggal_panen) as days_since_harvest
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            WHERE bp.jenis_kopi = ? AND bp.status = 'selesai'
            ORDER BY bp.tanggal_panen DESC
        ");
        $stmt->bind_param("s", $jenisKopi);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function updateRoastingInformation($id, $roastingData) {
        $fields = [];
        $types = "";
        $values = [];

        if (isset($roastingData['roast_level'])) {
            $fields[] = "status = 'terjual'";
        }

        if (isset($roastingData['yield_percentage'])) {
            $yieldKg = $this->calculateYieldKg($id, $roastingData['yield_percentage']);
            if ($yieldKg !== false) {
                $fields[] = "jumlah_kg = ?";
                $types .= "d";
                $values[] = $yieldKg;
            }
        }

        if (isset($roastingData['roasted_date'])) {
            $fields[] = "status = 'terjual'";
        }

        if (empty($fields)) {
            return false;
        }

        $types .= "i";
        $values[] = $id;

        $sql = "UPDATE batch_produksi SET " . implode(", ", $fields) . " WHERE id = ? AND status = 'selesai'";
        $stmt = $this->db->prepare($sql);
        
        if (!empty($types)) {
            $stmt->bind_param($types, ...$values);
        }
        
        return $stmt->execute();
    }

    public function updateYields($id, $yieldData) {
        $originalBatch = $this->getBatchById($id);
        if (!$originalBatch || $originalBatch['status'] !== 'selesai') {
            return false;
        }

        $yieldPercentage = $yieldData['yield_percentage'] ?? 85;
        $newWeight = $originalBatch['jumlah_kg'] * ($yieldPercentage / 100);

        $stmt = $this->db->prepare("
            UPDATE batch_produksi 
            SET jumlah_kg = ?, status = 'terjual'
            WHERE id = ? AND status = 'selesai'
        ");
        $stmt->bind_param("di", $newWeight, $id);
        
        return $stmt->execute();
    }

    public function markAsRoasted($id, $roastingInfo) {
        $stmt = $this->db->prepare("
            UPDATE batch_produksi 
            SET status = 'terjual'
            WHERE id = ? AND status = 'selesai'
        ");
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }

    public function searchAvailableBatches($query) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT bp.*, p.nama_petani, p.alamat_kebun,
                DATEDIFF(NOW(), bp.tanggal_panen) as days_since_harvest
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            WHERE (bp.kode_batch LIKE ? OR p.nama_petani LIKE ?)
            AND bp.status = 'selesai'
            ORDER BY bp.tanggal_panen DESC
            LIMIT 50
        ");
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getGreenBeanStats() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_available_batches,
                SUM(jumlah_kg) as total_available_kg,
                AVG(jumlah_kg) as avg_batch_size,
                AVG(harga_per_kg) as avg_price_per_kg,
                SUM(CASE WHEN jenis_kopi = 'arabika' THEN jumlah_kg ELSE 0 END) as arabika_kg,
                SUM(CASE WHEN jenis_kopi = 'robusta' THEN jumlah_kg ELSE 0 END) as robusta_kg,
                COUNT(CASE WHEN DATEDIFF(NOW(), tanggal_panen) <= 30 THEN 1 END) as fresh_batches,
                COUNT(CASE WHEN DATEDIFF(NOW(), tanggal_panen) > 90 THEN 1 END) as aging_batches
            FROM batch_produksi
            WHERE status = 'selesai'
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getFreshnessBuckets() {
        $stmt = $this->db->prepare("
            SELECT 
                CASE 
                    WHEN DATEDIFF(NOW(), tanggal_panen) <= 30 THEN 'Fresh (≤30 days)'
                    WHEN DATEDIFF(NOW(), tanggal_panen) <= 60 THEN 'Good (31-60 days)'
                    WHEN DATEDIFF(NOW(), tanggal_panen) <= 90 THEN 'Aging (61-90 days)'
                    ELSE 'Old (>90 days)'
                END as freshness_category,
                COUNT(*) as batch_count,
                SUM(jumlah_kg) as total_kg,
                AVG(harga_per_kg) as avg_price
            FROM batch_produksi
            WHERE status = 'selesai'
            GROUP BY freshness_category
            ORDER BY 
                CASE freshness_category
                    WHEN 'Fresh (≤30 days)' THEN 1
                    WHEN 'Good (31-60 days)' THEN 2
                    WHEN 'Aging (61-90 days)' THEN 3
                    ELSE 4
                END
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPriceRangeAnalysis() {
        $stmt = $this->db->prepare("
            SELECT 
                jenis_kopi,
                MIN(harga_per_kg) as min_price,
                MAX(harga_per_kg) as max_price,
                AVG(harga_per_kg) as avg_price,
                COUNT(*) as batch_count,
                SUM(jumlah_kg) as total_kg
            FROM batch_produksi
            WHERE status = 'selesai'
            GROUP BY jenis_kopi
            ORDER BY avg_price DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRecentlyAvailable($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT bp.*, p.nama_petani,
                DATEDIFF(NOW(), bp.tanggal_panen) as days_since_harvest
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            WHERE bp.status = 'selesai'
            ORDER BY bp.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPremiumBatches($minPrice = 25000) {
        $stmt = $this->db->prepare("
            SELECT bp.*, p.nama_petani, p.alamat_kebun,
                DATEDIFF(NOW(), bp.tanggal_panen) as days_since_harvest,
                (bp.jumlah_kg * bp.harga_per_kg) as total_value
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            WHERE bp.status = 'selesai' AND bp.harga_per_kg >= ?
            ORDER BY bp.harga_per_kg DESC
        ");
        $stmt->bind_param("d", $minPrice);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getBatchesByDateRange($startDate, $endDate) {
        $stmt = $this->db->prepare("
            SELECT bp.*, p.nama_petani, p.alamat_kebun,
                DATEDIFF(NOW(), bp.tanggal_panen) as days_since_harvest
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            WHERE bp.status = 'selesai' 
            AND DATE(bp.tanggal_panen) BETWEEN ? AND ?
            ORDER BY bp.tanggal_panen DESC
        ");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRoastingSuitability() {
        $stmt = $this->db->prepare("
            SELECT 
                bp.*,
                p.nama_petani,
                DATEDIFF(NOW(), bp.tanggal_panen) as days_since_harvest,
                CASE 
                    WHEN bp.harga_per_kg >= 28000 AND DATEDIFF(NOW(), bp.tanggal_panen) <= 45 THEN 'Excellent'
                    WHEN bp.harga_per_kg >= 25000 AND DATEDIFF(NOW(), bp.tanggal_panen) <= 60 THEN 'Very Good'
                    WHEN bp.harga_per_kg >= 22000 AND DATEDIFF(NOW(), bp.tanggal_panen) <= 75 THEN 'Good'
                    WHEN DATEDIFF(NOW(), bp.tanggal_panen) <= 90 THEN 'Fair'
                    ELSE 'Poor'
                END as roasting_grade
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            WHERE bp.status = 'selesai'
            ORDER BY 
                CASE roasting_grade
                    WHEN 'Excellent' THEN 1
                    WHEN 'Very Good' THEN 2
                    WHEN 'Good' THEN 3
                    WHEN 'Fair' THEN 4
                    ELSE 5
                END,
                bp.harga_per_kg DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getOriginAnalysis() {
        $stmt = $this->db->prepare("
            SELECT 
                p.alamat_kebun as origin_area,
                COUNT(*) as batch_count,
                SUM(bp.jumlah_kg) as total_kg,
                AVG(bp.harga_per_kg) as avg_price,
                bp.jenis_kopi
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            WHERE bp.status = 'selesai'
            GROUP BY p.alamat_kebun, bp.jenis_kopi
            ORDER BY avg_price DESC, total_kg DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function calculateYieldKg($batchId, $yieldPercentage) {
        $batch = $this->getBatchById($batchId);
        if (!$batch) {
            return false;
        }
        
        return $batch['jumlah_kg'] * ($yieldPercentage / 100);
    }

    public function getInventoryTurnover($days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(bp.created_at) as available_date,
                COUNT(*) as batches_available,
                SUM(bp.jumlah_kg) as kg_available,
                AVG(bp.harga_per_kg) as avg_price
            FROM batch_produksi bp
            WHERE bp.status = 'selesai'
            AND bp.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(bp.created_at)
            ORDER BY available_date DESC
        ");
        $stmt->bind_param("i", $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getQualityGrades() {
        $stmt = $this->db->prepare("
            SELECT 
                CASE 
                    WHEN harga_per_kg >= 30000 THEN 'Premium (≥30k)'
                    WHEN harga_per_kg >= 25000 THEN 'High Grade (25k-30k)'
                    WHEN harga_per_kg >= 20000 THEN 'Standard (20k-25k)'
                    ELSE 'Commercial (<20k)'
                END as quality_grade,
                COUNT(*) as batch_count,
                SUM(jumlah_kg) as total_kg,
                AVG(harga_per_kg) as avg_price,
                jenis_kopi
            FROM batch_produksi
            WHERE status = 'selesai'
            GROUP BY quality_grade, jenis_kopi
            ORDER BY avg_price DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}