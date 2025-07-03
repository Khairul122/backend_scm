<?php
class BatchProduksiModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAllBatch() {
        $stmt = $this->db->prepare("
            SELECT bp.*, p.nama_petani, p.alamat_kebun
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            ORDER BY bp.created_at DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getBatchById($id) {
        $stmt = $this->db->prepare("
            SELECT bp.*, p.nama_petani, p.alamat_kebun, p.no_telepon
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            WHERE bp.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getBatchByStatus($status) {
        $stmt = $this->db->prepare("
            SELECT bp.*, p.nama_petani, p.alamat_kebun
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            WHERE bp.status = ?
            ORDER BY bp.created_at DESC
        ");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getBatchByPetani($petaniId) {
        $stmt = $this->db->prepare("
            SELECT bp.*, p.nama_petani
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            WHERE bp.petani_id = ?
            ORDER BY bp.created_at DESC
        ");
        $stmt->bind_param("i", $petaniId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getBatchByJenisKopi($jenisKopi) {
        $stmt = $this->db->prepare("
            SELECT bp.*, p.nama_petani, p.alamat_kebun
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            WHERE bp.jenis_kopi = ?
            ORDER BY bp.created_at DESC
        ");
        $stmt->bind_param("s", $jenisKopi);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function createBatch($data) {
        $stmt = $this->db->prepare("
            INSERT INTO batch_produksi (kode_batch, petani_id, jenis_kopi, jumlah_kg, tanggal_panen, harga_per_kg, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $status = $data['status'] ?? 'panen';
        
        $stmt->bind_param(
            "sissdds",
            $data['kode_batch'],
            $data['petani_id'],
            $data['jenis_kopi'],
            $data['jumlah_kg'],
            $data['tanggal_panen'],
            $data['harga_per_kg'],
            $status
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function updateBatch($id, $data) {
        $fields = [];
        $types = "";
        $values = [];

        if (isset($data['jumlah_kg'])) {
            $fields[] = "jumlah_kg = ?";
            $types .= "d";
            $values[] = $data['jumlah_kg'];
        }

        if (isset($data['harga_per_kg'])) {
            $fields[] = "harga_per_kg = ?";
            $types .= "d";
            $values[] = $data['harga_per_kg'];
        }

        if (isset($data['status'])) {
            $fields[] = "status = ?";
            $types .= "s";
            $values[] = $data['status'];
        }

        if (isset($data['tanggal_panen'])) {
            $fields[] = "tanggal_panen = ?";
            $types .= "s";
            $values[] = $data['tanggal_panen'];
        }

        if (empty($fields)) {
            return false;
        }

        $types .= "i";
        $values[] = $id;

        $sql = "UPDATE batch_produksi SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    public function updateBatchStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE batch_produksi SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    public function getBatchStats() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_batches,
                SUM(jumlah_kg) as total_kg,
                AVG(jumlah_kg) as avg_kg_per_batch,
                AVG(harga_per_kg) as avg_price_per_kg,
                SUM(jumlah_kg * harga_per_kg) as total_value,
                SUM(CASE WHEN status = 'panen' THEN 1 ELSE 0 END) as panen_count,
                SUM(CASE WHEN status = 'proses' THEN 1 ELSE 0 END) as proses_count,
                SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai_count,
                SUM(CASE WHEN status = 'terjual' THEN 1 ELSE 0 END) as terjual_count,
                SUM(CASE WHEN jenis_kopi = 'arabika' THEN jumlah_kg ELSE 0 END) as arabika_kg,
                SUM(CASE WHEN jenis_kopi = 'robusta' THEN jumlah_kg ELSE 0 END) as robusta_kg
            FROM batch_produksi
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getProcessingBatches() {
        $stmt = $this->db->prepare("
            SELECT bp.*, p.nama_petani, p.alamat_kebun,
                DATEDIFF(NOW(), bp.tanggal_panen) as days_since_harvest
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            WHERE bp.status IN ('panen', 'proses')
            ORDER BY bp.tanggal_panen ASC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getBatchByDateRange($startDate, $endDate) {
        $stmt = $this->db->prepare("
            SELECT bp.*, p.nama_petani, p.alamat_kebun
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            WHERE DATE(bp.tanggal_panen) BETWEEN ? AND ?
            ORDER BY bp.tanggal_panen DESC
        ");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProductionTrends($days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(tanggal_panen) as harvest_date,
                COUNT(*) as batch_count,
                SUM(jumlah_kg) as total_kg,
                AVG(harga_per_kg) as avg_price,
                SUM(CASE WHEN jenis_kopi = 'arabika' THEN jumlah_kg ELSE 0 END) as arabika_kg,
                SUM(CASE WHEN jenis_kopi = 'robusta' THEN jumlah_kg ELSE 0 END) as robusta_kg
            FROM batch_produksi
            WHERE tanggal_panen >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(tanggal_panen)
            ORDER BY harvest_date DESC
        ");
        $stmt->bind_param("i", $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPetaniProductivity() {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.nama_petani,
                p.luas_lahan,
                COUNT(bp.id) as total_batches,
                SUM(bp.jumlah_kg) as total_production,
                AVG(bp.jumlah_kg) as avg_batch_size,
                AVG(bp.harga_per_kg) as avg_price,
                SUM(bp.jumlah_kg * bp.harga_per_kg) as total_revenue,
                ROUND(SUM(bp.jumlah_kg) / p.luas_lahan, 2) as kg_per_hectare,
                MAX(bp.tanggal_panen) as last_harvest
            FROM petani p
            LEFT JOIN batch_produksi bp ON p.id = bp.petani_id
            GROUP BY p.id
            ORDER BY total_production DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getQualityAnalysis() {
        $stmt = $this->db->prepare("
            SELECT 
                bp.*,
                p.nama_petani,
                CASE 
                    WHEN bp.harga_per_kg >= 30000 THEN 'Premium'
                    WHEN bp.harga_per_kg >= 25000 THEN 'Good'
                    WHEN bp.harga_per_kg >= 20000 THEN 'Standard'
                    ELSE 'Below Standard'
                END as quality_grade,
                DATEDIFF(NOW(), bp.tanggal_panen) as age_days
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            ORDER BY bp.harga_per_kg DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function searchBatch($query) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT bp.*, p.nama_petani, p.alamat_kebun
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            WHERE bp.kode_batch LIKE ? OR p.nama_petani LIKE ?
            ORDER BY bp.created_at DESC
            LIMIT 50
        ");
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function generateKodeBatch() {
        $date = date('Ymd');
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM batch_produksi 
            WHERE kode_batch LIKE ?
        ");
        $pattern = "BP{$date}%";
        $stmt->bind_param("s", $pattern);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        $sequence = str_pad($result['count'] + 1, 3, '0', STR_PAD_LEFT);
        return "BP{$date}{$sequence}";
    }

    public function getRecentPickups($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT bp.*, p.nama_petani, p.alamat_kebun
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            ORDER BY bp.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getReadyForSale() {
        $stmt = $this->db->prepare("
            SELECT bp.*, p.nama_petani
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            WHERE bp.status = 'selesai'
            ORDER BY bp.tanggal_panen ASC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function bulkUpdateStatus($ids, $status) {
        if (empty($ids)) return false;
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "UPDATE batch_produksi SET status = ? WHERE id IN ($placeholders)";
        
        $stmt = $this->db->prepare($sql);
        $types = str_repeat('i', count($ids));
        $stmt->bind_param('s' . $types, $status, ...$ids);
        
        return $stmt->execute();
    }

    public function getInventoryStatus() {
        $stmt = $this->db->prepare("
            SELECT 
                status,
                jenis_kopi,
                COUNT(*) as batch_count,
                SUM(jumlah_kg) as total_kg,
                AVG(harga_per_kg) as avg_price
            FROM batch_produksi
            GROUP BY status, jenis_kopi
            ORDER BY status, jenis_kopi
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPriceAnalysis() {
        $stmt = $this->db->prepare("
            SELECT 
                jenis_kopi,
                MIN(harga_per_kg) as min_price,
                MAX(harga_per_kg) as max_price,
                AVG(harga_per_kg) as avg_price,
                COUNT(*) as batch_count,
                SUM(jumlah_kg) as total_kg
            FROM batch_produksi
            GROUP BY jenis_kopi
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getSlowMovingBatches($days = 30) {
        $stmt = $this->db->prepare("
            SELECT bp.*, p.nama_petani,
                DATEDIFF(NOW(), bp.tanggal_panen) as days_old
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            WHERE bp.status IN ('panen', 'proses') 
            AND bp.tanggal_panen <= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY bp.tanggal_panen ASC
        ");
        $stmt->bind_param("i", $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function checkKodeBatchExists($kodeBatch) {
        $stmt = $this->db->prepare("SELECT id FROM batch_produksi WHERE kode_batch = ?");
        $stmt->bind_param("s", $kodeBatch);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
}