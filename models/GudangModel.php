<?php
class GudangModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAllStokGudang() {
        $stmt = $this->db->prepare("
            SELECT sg.*, pr.nama_produk, pr.harga as produk_harga,
                bp.kode_batch, bp.jenis_kopi, bp.tanggal_panen
            FROM stok_gudang sg
            JOIN produk pr ON sg.produk_id = pr.id
            LEFT JOIN batch_produksi bp ON sg.batch_id = bp.id
            ORDER BY sg.created_at DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getStokById($id) {
        $stmt = $this->db->prepare("
            SELECT sg.*, pr.nama_produk, pr.harga as produk_harga,
                bp.kode_batch, bp.jenis_kopi, bp.tanggal_panen
            FROM stok_gudang sg
            JOIN produk pr ON sg.produk_id = pr.id
            LEFT JOIN batch_produksi bp ON sg.batch_id = bp.id
            WHERE sg.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getStokByLocation($location) {
        $stmt = $this->db->prepare("
            SELECT sg.*, pr.nama_produk, pr.harga as produk_harga,
                bp.kode_batch, bp.jenis_kopi
            FROM stok_gudang sg
            JOIN produk pr ON sg.produk_id = pr.id
            LEFT JOIN batch_produksi bp ON sg.batch_id = bp.id
            WHERE sg.lokasi_gudang = ?
            ORDER BY sg.created_at DESC
        ");
        $stmt->bind_param("s", $location);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getStokByProduk($produkId) {
        $stmt = $this->db->prepare("
            SELECT sg.*, pr.nama_produk,
                bp.kode_batch, bp.jenis_kopi
            FROM stok_gudang sg
            JOIN produk pr ON sg.produk_id = pr.id
            LEFT JOIN batch_produksi bp ON sg.batch_id = bp.id
            WHERE sg.produk_id = ?
            ORDER BY sg.created_at DESC
        ");
        $stmt->bind_param("i", $produkId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function createStokGudang($data) {
        $stmt = $this->db->prepare("
            INSERT INTO stok_gudang (produk_id, batch_id, jumlah_stok, lokasi_gudang) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "iids",
            $data['produk_id'],
            $data['batch_id'],
            $data['jumlah_stok'],
            $data['lokasi_gudang']
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function updateStokGudang($id, $data) {
        $fields = [];
        $types = "";
        $values = [];

        if (isset($data['jumlah_stok'])) {
            $fields[] = "jumlah_stok = ?";
            $types .= "d";
            $values[] = $data['jumlah_stok'];
        }

        if (isset($data['lokasi_gudang'])) {
            $fields[] = "lokasi_gudang = ?";
            $types .= "s";
            $values[] = $data['lokasi_gudang'];
        }

        if (empty($fields)) {
            return false;
        }

        $types .= "i";
        $values[] = $id;

        $sql = "UPDATE stok_gudang SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    public function transferStock($id, $newLocation) {
        $stmt = $this->db->prepare("UPDATE stok_gudang SET lokasi_gudang = ? WHERE id = ?");
        $stmt->bind_param("si", $newLocation, $id);
        return $stmt->execute();
    }

    public function getStockLevels() {
        $stmt = $this->db->prepare("
            SELECT 
                pr.id,
                pr.nama_produk,
                SUM(sg.jumlah_stok) as total_stok,
                COUNT(DISTINCT sg.lokasi_gudang) as locations_count,
                GROUP_CONCAT(DISTINCT sg.lokasi_gudang) as locations,
                AVG(pr.harga) as avg_price,
                MIN(sg.created_at) as oldest_stock,
                MAX(sg.created_at) as newest_stock
            FROM produk pr
            LEFT JOIN stok_gudang sg ON pr.id = sg.produk_id
            GROUP BY pr.id, pr.nama_produk
            ORDER BY total_stok DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getMultiLocationStock() {
        $stmt = $this->db->prepare("
            SELECT 
                sg.lokasi_gudang,
                COUNT(DISTINCT sg.produk_id) as unique_products,
                SUM(sg.jumlah_stok) as total_stock,
                COUNT(sg.id) as total_entries,
                AVG(pr.harga) as avg_product_price
            FROM stok_gudang sg
            JOIN produk pr ON sg.produk_id = pr.id
            GROUP BY sg.lokasi_gudang
            ORDER BY total_stock DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getLowStockItems($threshold = 10) {
        $stmt = $this->db->prepare("
            SELECT 
                pr.id,
                pr.nama_produk,
                SUM(sg.jumlah_stok) as total_stok,
                COUNT(DISTINCT sg.lokasi_gudang) as locations_count,
                GROUP_CONCAT(DISTINCT sg.lokasi_gudang) as locations
            FROM produk pr
            LEFT JOIN stok_gudang sg ON pr.id = sg.produk_id
            GROUP BY pr.id, pr.nama_produk
            HAVING total_stok <= ?
            ORDER BY total_stok ASC
        ");
        $stmt->bind_param("d", $threshold);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getStockMovement($days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(sg.created_at) as date,
                sg.lokasi_gudang,
                COUNT(sg.id) as entries_added,
                SUM(sg.jumlah_stok) as stock_added,
                COUNT(DISTINCT sg.produk_id) as unique_products
            FROM stok_gudang sg
            WHERE sg.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(sg.created_at), sg.lokasi_gudang
            ORDER BY date DESC, sg.lokasi_gudang
        ");
        $stmt->bind_param("i", $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function addFromCompletedBatch($batchId) {
        $stmt = $this->db->prepare("
            SELECT bp.*, pr.id as produk_id
            FROM batch_produksi bp
            JOIN produk pr ON (
                (bp.jenis_kopi = 'arabika' AND pr.nama_produk LIKE '%arabika%') OR
                (bp.jenis_kopi = 'robusta' AND pr.nama_produk LIKE '%robusta%')
            )
            WHERE bp.id = ? AND bp.status = 'selesai'
            LIMIT 1
        ");
        $stmt->bind_param("i", $batchId);
        $stmt->execute();
        $batch = $stmt->get_result()->fetch_assoc();

        if (!$batch) {
            return false;
        }

        $stokData = [
            'produk_id' => $batch['produk_id'],
            'batch_id' => $batchId,
            'jumlah_stok' => $batch['jumlah_kg'],
            'lokasi_gudang' => 'Gudang Utama'
        ];

        return $this->createStokGudang($stokData);
    }

    public function getStockByBatch($batchId) {
        $stmt = $this->db->prepare("
            SELECT sg.*, pr.nama_produk, bp.kode_batch
            FROM stok_gudang sg
            JOIN produk pr ON sg.produk_id = pr.id
            JOIN batch_produksi bp ON sg.batch_id = bp.id
            WHERE sg.batch_id = ?
        ");
        $stmt->bind_param("i", $batchId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function adjustQuantity($id, $newQuantity, $reason = null) {
        $stmt = $this->db->prepare("UPDATE stok_gudang SET jumlah_stok = ? WHERE id = ?");
        $stmt->bind_param("di", $newQuantity, $id);
        return $stmt->execute();
    }

    public function getAvailableLocations() {
        $stmt = $this->db->prepare("SELECT DISTINCT lokasi_gudang FROM stok_gudang ORDER BY lokasi_gudang");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $locations = [];
        while ($row = $result->fetch_assoc()) {
            $locations[] = $row['lokasi_gudang'];
        }
        return $locations;
    }

    public function searchStock($query) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT sg.*, pr.nama_produk, bp.kode_batch
            FROM stok_gudang sg
            JOIN produk pr ON sg.produk_id = pr.id
            LEFT JOIN batch_produksi bp ON sg.batch_id = bp.id
            WHERE pr.nama_produk LIKE ? OR sg.lokasi_gudang LIKE ? OR bp.kode_batch LIKE ?
            ORDER BY sg.created_at DESC
            LIMIT 50
        ");
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getStockAging() {
        $stmt = $this->db->prepare("
            SELECT 
                sg.*,
                pr.nama_produk,
                bp.kode_batch,
                bp.tanggal_panen,
                DATEDIFF(NOW(), sg.created_at) as days_in_storage,
                DATEDIFF(NOW(), bp.tanggal_panen) as days_since_harvest
            FROM stok_gudang sg
            JOIN produk pr ON sg.produk_id = pr.id
            LEFT JOIN batch_produksi bp ON sg.batch_id = bp.id
            ORDER BY days_in_storage DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getStockValuation() {
        $stmt = $this->db->prepare("
            SELECT 
                sg.lokasi_gudang,
                pr.nama_produk,
                sg.jumlah_stok,
                pr.harga,
                (sg.jumlah_stok * pr.harga) as stock_value,
                bp.kode_batch
            FROM stok_gudang sg
            JOIN produk pr ON sg.produk_id = pr.id
            LEFT JOIN batch_produksi bp ON sg.batch_id = bp.id
            ORDER BY stock_value DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function bulkTransfer($ids, $newLocation) {
        if (empty($ids)) return false;
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "UPDATE stok_gudang SET lokasi_gudang = ? WHERE id IN ($placeholders)";
        
        $stmt = $this->db->prepare($sql);
        $types = str_repeat('i', count($ids));
        $stmt->bind_param('s' . $types, $newLocation, ...$ids);
        
        return $stmt->execute();
    }

    public function getStockTurnover($days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                pr.nama_produk,
                SUM(sg.jumlah_stok) as current_stock,
                COUNT(sg.id) as stock_entries,
                AVG(DATEDIFF(NOW(), sg.created_at)) as avg_age_days
            FROM stok_gudang sg
            JOIN produk pr ON sg.produk_id = pr.id
            WHERE sg.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY pr.id, pr.nama_produk
            ORDER BY avg_age_days DESC
        ");
        $stmt->bind_param("i", $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getCapacityByLocation() {
        $stmt = $this->db->prepare("
            SELECT 
                lokasi_gudang,
                SUM(jumlah_stok) as total_stock,
                COUNT(DISTINCT produk_id) as product_variety,
                COUNT(id) as total_entries,
                MAX(created_at) as last_updated
            FROM stok_gudang
            GROUP BY lokasi_gudang
            ORDER BY total_stock DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getStockAlerts() {
        $stmt = $this->db->prepare("
            SELECT 
                pr.nama_produk,
                SUM(sg.jumlah_stok) as total_stock,
                CASE 
                    WHEN SUM(sg.jumlah_stok) = 0 THEN 'OUT_OF_STOCK'
                    WHEN SUM(sg.jumlah_stok) <= 5 THEN 'CRITICAL_LOW'
                    WHEN SUM(sg.jumlah_stok) <= 10 THEN 'LOW_STOCK'
                    ELSE 'NORMAL'
                END as alert_level,
                COUNT(DISTINCT sg.lokasi_gudang) as locations_count
            FROM produk pr
            LEFT JOIN stok_gudang sg ON pr.id = sg.produk_id
            GROUP BY pr.id, pr.nama_produk
            HAVING alert_level != 'NORMAL'
            ORDER BY 
                CASE alert_level
                    WHEN 'OUT_OF_STOCK' THEN 1
                    WHEN 'CRITICAL_LOW' THEN 2
                    WHEN 'LOW_STOCK' THEN 3
                END
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}