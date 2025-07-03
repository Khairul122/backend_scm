<?php
class RoastGudangModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAllRoastedStock($penjualId) {
        $stmt = $this->db->prepare("
            SELECT sg.*, pr.nama_produk, pr.harga as produk_harga,
                bp.kode_batch, bp.jenis_kopi, bp.tanggal_panen,
                DATEDIFF(NOW(), sg.created_at) as days_in_storage
            FROM stok_gudang sg
            JOIN produk pr ON sg.produk_id = pr.id
            LEFT JOIN batch_produksi bp ON sg.batch_id = bp.id
            WHERE pr.penjual_id = ? AND pr.kategori_id = 2
            ORDER BY sg.created_at DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRoastedStockById($id, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT sg.*, pr.nama_produk, pr.harga as produk_harga,
                bp.kode_batch, bp.jenis_kopi, bp.tanggal_panen
            FROM stok_gudang sg
            JOIN produk pr ON sg.produk_id = pr.id
            LEFT JOIN batch_produksi bp ON sg.batch_id = bp.id
            WHERE sg.id = ? AND pr.penjual_id = ? AND pr.kategori_id = 2
        ");
        $stmt->bind_param("ii", $id, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getRoastedStockByLocation($location, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT sg.*, pr.nama_produk, pr.harga as produk_harga,
                bp.kode_batch, bp.jenis_kopi
            FROM stok_gudang sg
            JOIN produk pr ON sg.produk_id = pr.id
            LEFT JOIN batch_produksi bp ON sg.batch_id = bp.id
            WHERE sg.lokasi_gudang = ? AND pr.penjual_id = ? AND pr.kategori_id = 2
            ORDER BY sg.created_at DESC
        ");
        $stmt->bind_param("si", $location, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRoastedStockByProduk($produkId, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT sg.*, pr.nama_produk,
                bp.kode_batch, bp.jenis_kopi
            FROM stok_gudang sg
            JOIN produk pr ON sg.produk_id = pr.id
            LEFT JOIN batch_produksi bp ON sg.batch_id = bp.id
            WHERE sg.produk_id = ? AND pr.penjual_id = ? AND pr.kategori_id = 2
            ORDER BY sg.created_at DESC
        ");
        $stmt->bind_param("ii", $produkId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function createRoastedStock($data, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT id FROM produk 
            WHERE id = ? AND penjual_id = ? AND kategori_id = 2
        ");
        $stmt->bind_param("ii", $data['produk_id'], $penjualId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            return false;
        }

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

    public function updateRoastedStock($id, $data, $penjualId) {
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

        $types .= "ii";
        $values[] = $id;
        $values[] = $penjualId;

        $sql = "UPDATE stok_gudang sg 
                JOIN produk pr ON sg.produk_id = pr.id 
                SET " . implode(", ", array_map(function($field) {
                    return "sg." . $field;
                }, $fields)) . " 
                WHERE sg.id = ? AND pr.penjual_id = ? AND pr.kategori_id = 2";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    public function transferRoastedStock($id, $newLocation, $penjualId) {
        $stmt = $this->db->prepare("
            UPDATE stok_gudang sg 
            JOIN produk pr ON sg.produk_id = pr.id 
            SET sg.lokasi_gudang = ? 
            WHERE sg.id = ? AND pr.penjual_id = ? AND pr.kategori_id = 2
        ");
        $stmt->bind_param("sii", $newLocation, $id, $penjualId);
        return $stmt->execute();
    }

    public function getRoastedStockLevels($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                pr.id,
                pr.nama_produk,
                pr.berat as package_size,
                SUM(sg.jumlah_stok) as total_stok,
                COUNT(DISTINCT sg.lokasi_gudang) as locations_count,
                GROUP_CONCAT(DISTINCT sg.lokasi_gudang) as locations,
                AVG(pr.harga) as avg_price,
                MIN(sg.created_at) as oldest_stock,
                MAX(sg.created_at) as newest_stock,
                CASE 
                    WHEN pr.nama_produk LIKE '%light%' THEN 'Light Roast'
                    WHEN pr.nama_produk LIKE '%medium%' THEN 'Medium Roast'
                    WHEN pr.nama_produk LIKE '%dark%' THEN 'Dark Roast'
                    WHEN pr.nama_produk LIKE '%espresso%' THEN 'Espresso'
                    ELSE 'Unspecified'
                END as roast_level
            FROM produk pr
            LEFT JOIN stok_gudang sg ON pr.id = sg.produk_id
            WHERE pr.penjual_id = ? AND pr.kategori_id = 2
            GROUP BY pr.id, pr.nama_produk, pr.berat
            ORDER BY total_stok DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRoastedStockAnalysisByLocation($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                sg.lokasi_gudang,
                COUNT(DISTINCT sg.produk_id) as unique_products,
                SUM(sg.jumlah_stok) as total_stock,
                COUNT(sg.id) as total_entries,
                AVG(pr.harga) as avg_product_price,
                SUM(sg.jumlah_stok * pr.harga) as total_value
            FROM stok_gudang sg
            JOIN produk pr ON sg.produk_id = pr.id
            WHERE pr.penjual_id = ? AND pr.kategori_id = 2
            GROUP BY sg.lokasi_gudang
            ORDER BY total_value DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getLowRoastedStock($threshold, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                pr.id,
                pr.nama_produk,
                pr.berat,
                SUM(sg.jumlah_stok) as total_stok,
                COUNT(DISTINCT sg.lokasi_gudang) as locations_count,
                GROUP_CONCAT(DISTINCT sg.lokasi_gudang) as locations
            FROM produk pr
            LEFT JOIN stok_gudang sg ON pr.id = sg.produk_id
            WHERE pr.penjual_id = ? AND pr.kategori_id = 2
            GROUP BY pr.id, pr.nama_produk, pr.berat
            HAVING total_stok <= ?
            ORDER BY total_stok ASC
        ");
        $stmt->bind_param("id", $penjualId, $threshold);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function addRoastedInventoryFromBatch($batchId, $produkId, $roastedWeight, $location, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT id FROM produk 
            WHERE id = ? AND penjual_id = ? AND kategori_id = 2
        ");
        $stmt->bind_param("ii", $produkId, $penjualId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            return false;
        }

        $stokData = [
            'produk_id' => $produkId,
            'batch_id' => $batchId,
            'jumlah_stok' => $roastedWeight,
            'lokasi_gudang' => $location
        ];

        return $this->createRoastedStock($stokData, $penjualId);
    }

    public function updateQuantityAfterSale($id, $soldQuantity, $penjualId) {
        $currentStock = $this->getRoastedStockById($id, $penjualId);
        if (!$currentStock) {
            return false;
        }

        $newQuantity = $currentStock['jumlah_stok'] - $soldQuantity;
        if ($newQuantity < 0) {
            return false;
        }

        return $this->updateRoastedStock($id, ['jumlah_stok' => $newQuantity], $penjualId);
    }

    public function updateQuantityAfterRoasting($id, $roastedQuantity, $penjualId) {
        $currentStock = $this->getRoastedStockById($id, $penjualId);
        if (!$currentStock) {
            return false;
        }

        $newQuantity = $currentStock['jumlah_stok'] + $roastedQuantity;
        
        return $this->updateRoastedStock($id, ['jumlah_stok' => $newQuantity], $penjualId);
    }

    public function searchRoastedStock($query, $penjualId) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT sg.*, pr.nama_produk, bp.kode_batch
            FROM stok_gudang sg
            JOIN produk pr ON sg.produk_id = pr.id
            LEFT JOIN batch_produksi bp ON sg.batch_id = bp.id
            WHERE (pr.nama_produk LIKE ? OR sg.lokasi_gudang LIKE ? OR bp.kode_batch LIKE ?)
            AND pr.penjual_id = ? AND pr.kategori_id = 2
            ORDER BY sg.created_at DESC
            LIMIT 50
        ");
        $stmt->bind_param("sssi", $searchTerm, $searchTerm, $searchTerm, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRoastedStockAging($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                sg.*,
                pr.nama_produk,
                bp.kode_batch,
                bp.tanggal_panen,
                DATEDIFF(NOW(), sg.created_at) as days_in_storage,
                DATEDIFF(NOW(), bp.tanggal_panen) as days_since_harvest,
                CASE 
                    WHEN DATEDIFF(NOW(), sg.created_at) <= 7 THEN 'Very Fresh'
                    WHEN DATEDIFF(NOW(), sg.created_at) <= 14 THEN 'Fresh'
                    WHEN DATEDIFF(NOW(), sg.created_at) <= 30 THEN 'Good'
                    WHEN DATEDIFF(NOW(), sg.created_at) <= 60 THEN 'Aging'
                    ELSE 'Old'
                END as freshness_grade
            FROM stok_gudang sg
            JOIN produk pr ON sg.produk_id = pr.id
            LEFT JOIN batch_produksi bp ON sg.batch_id = bp.id
            WHERE pr.penjual_id = ? AND pr.kategori_id = 2
            ORDER BY days_in_storage DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRoastedStockValuation($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                sg.lokasi_gudang,
                pr.nama_produk,
                sg.jumlah_stok,
                pr.harga,
                (sg.jumlah_stok * pr.harga) as stock_value,
                bp.kode_batch,
                CASE 
                    WHEN pr.nama_produk LIKE '%premium%' OR pr.harga >= 100000 THEN 'Premium'
                    WHEN pr.harga >= 50000 THEN 'Mid-range'
                    ELSE 'Standard'
                END as product_tier
            FROM stok_gudang sg
            JOIN produk pr ON sg.produk_id = pr.id
            LEFT JOIN batch_produksi bp ON sg.batch_id = bp.id
            WHERE pr.penjual_id = ? AND pr.kategori_id = 2
            ORDER BY stock_value DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRoastTypeAnalysis($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                CASE 
                    WHEN pr.nama_produk LIKE '%light%' THEN 'Light Roast'
                    WHEN pr.nama_produk LIKE '%medium%' THEN 'Medium Roast'
                    WHEN pr.nama_produk LIKE '%dark%' THEN 'Dark Roast'
                    WHEN pr.nama_produk LIKE '%espresso%' THEN 'Espresso'
                    ELSE 'Unspecified'
                END as roast_type,
                COUNT(DISTINCT sg.id) as stock_entries,
                SUM(sg.jumlah_stok) as total_stock,
                AVG(pr.harga) as avg_price,
                COUNT(DISTINCT sg.lokasi_gudang) as locations
            FROM stok_gudang sg
            JOIN produk pr ON sg.produk_id = pr.id
            WHERE pr.penjual_id = ? AND pr.kategori_id = 2
            GROUP BY roast_type
            ORDER BY total_stock DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPackagingSizeDistribution($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                CASE 
                    WHEN pr.berat <= 100 THEN 'Small (≤100g)'
                    WHEN pr.berat <= 250 THEN 'Medium (101-250g)'
                    WHEN pr.berat <= 500 THEN 'Large (251-500g)'
                    WHEN pr.berat <= 1000 THEN 'XL (501-1000g)'
                    ELSE 'Bulk (>1000g)'
                END as package_size,
                COUNT(DISTINCT sg.id) as stock_entries,
                SUM(sg.jumlah_stok) as total_units,
                AVG(pr.harga) as avg_price_per_unit,
                SUM(sg.jumlah_stok * pr.harga) as total_value
            FROM stok_gudang sg
            JOIN produk pr ON sg.produk_id = pr.id
            WHERE pr.penjual_id = ? AND pr.kategori_id = 2
            GROUP BY package_size
            ORDER BY total_value DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function bulkTransferRoastedStock($ids, $newLocation, $penjualId) {
        if (empty($ids)) return false;
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "UPDATE stok_gudang sg 
                JOIN produk pr ON sg.produk_id = pr.id 
                SET sg.lokasi_gudang = ? 
                WHERE sg.id IN ($placeholders) AND pr.penjual_id = ? AND pr.kategori_id = 2";
        
        $stmt = $this->db->prepare($sql);
        $types = str_repeat('i', count($ids));
        $stmt->bind_param('s' . $types . 'i', $newLocation, ...array_merge($ids, [$penjualId]));
        
        return $stmt->execute();
    }

    public function getRoastedStockAlerts($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                pr.nama_produk,
                SUM(sg.jumlah_stok) as total_stock,
                CASE 
                    WHEN SUM(sg.jumlah_stok) = 0 THEN 'OUT_OF_STOCK'
                    WHEN SUM(sg.jumlah_stok) <= 5 THEN 'CRITICAL_LOW'
                    WHEN SUM(sg.jumlah_stok) <= 10 THEN 'LOW_STOCK'
                    WHEN DATEDIFF(NOW(), MAX(sg.created_at)) > 30 THEN 'AGING_STOCK'
                    ELSE 'NORMAL'
                END as alert_level,
                COUNT(DISTINCT sg.lokasi_gudang) as locations_count,
                MAX(sg.created_at) as last_restocked
            FROM produk pr
            LEFT JOIN stok_gudang sg ON pr.id = sg.produk_id
            WHERE pr.penjual_id = ? AND pr.kategori_id = 2
            GROUP BY pr.id, pr.nama_produk
            HAVING alert_level != 'NORMAL'
            ORDER BY 
                CASE alert_level
                    WHEN 'OUT_OF_STOCK' THEN 1
                    WHEN 'CRITICAL_LOW' THEN 2
                    WHEN 'LOW_STOCK' THEN 3
                    WHEN 'AGING_STOCK' THEN 4
                END
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function checkRoastedStockOwnership($stockId, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT sg.id 
            FROM stok_gudang sg
            JOIN produk pr ON sg.produk_id = pr.id
            WHERE sg.id = ? AND pr.penjual_id = ? AND pr.kategori_id = 2
        ");
        $stmt->bind_param("ii", $stockId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
}