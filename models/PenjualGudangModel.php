<?php
class PenjualGudangModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAllInventory($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                sg.*,
                p.nama_produk,
                p.harga as product_price,
                p.berat as product_weight,
                k.nama_kategori,
                bp.kode_batch,
                bp.jenis_kopi,
                bp.tanggal_panen,
                DATEDIFF(NOW(), sg.created_at) as days_in_storage,
                (sg.jumlah_stok * p.harga) as inventory_value
            FROM stok_gudang sg
            JOIN produk p ON sg.produk_id = p.id
            JOIN kategori_produk k ON p.kategori_id = k.id
            LEFT JOIN batch_produksi bp ON sg.batch_id = bp.id
            WHERE p.penjual_id = ?
            ORDER BY sg.created_at DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getInventoryById($id, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                sg.*,
                p.nama_produk,
                p.harga as product_price,
                p.berat as product_weight,
                k.nama_kategori,
                bp.kode_batch,
                bp.jenis_kopi,
                bp.tanggal_panen
            FROM stok_gudang sg
            JOIN produk p ON sg.produk_id = p.id
            JOIN kategori_produk k ON p.kategori_id = k.id
            LEFT JOIN batch_produksi bp ON sg.batch_id = bp.id
            WHERE sg.id = ? AND p.penjual_id = ?
        ");
        $stmt->bind_param("ii", $id, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getInventoryByLocation($location, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                sg.*,
                p.nama_produk,
                p.harga as product_price,
                k.nama_kategori,
                bp.kode_batch
            FROM stok_gudang sg
            JOIN produk p ON sg.produk_id = p.id
            JOIN kategori_produk k ON p.kategori_id = k.id
            LEFT JOIN batch_produksi bp ON sg.batch_id = bp.id
            WHERE sg.lokasi_gudang = ? AND p.penjual_id = ?
            ORDER BY sg.created_at DESC
        ");
        $stmt->bind_param("si", $location, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getInventoryByProduct($produkId, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                sg.*,
                p.nama_produk,
                p.harga as product_price,
                bp.kode_batch,
                bp.jenis_kopi
            FROM stok_gudang sg
            JOIN produk p ON sg.produk_id = p.id
            LEFT JOIN batch_produksi bp ON sg.batch_id = bp.id
            WHERE sg.produk_id = ? AND p.penjual_id = ?
            ORDER BY sg.created_at DESC
        ");
        $stmt->bind_param("ii", $produkId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function addInventory($data, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT id FROM produk 
            WHERE id = ? AND penjual_id = ?
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
            $inventoryId = $this->db->insert_id;
            $this->updateProductStock($data['produk_id'], $penjualId);
            return $inventoryId;
        }
        return false;
    }

    public function updateInventory($id, $data, $penjualId) {
        $inventory = $this->getInventoryById($id, $penjualId);
        if (!$inventory) {
            return false;
        }

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
                JOIN produk p ON sg.produk_id = p.id 
                SET " . implode(", ", array_map(function($field) {
                    return "sg." . $field;
                }, $fields)) . " 
                WHERE sg.id = ? AND p.penjual_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            $this->updateProductStock($inventory['produk_id'], $penjualId);
            return true;
        }
        return false;
    }

    public function adjustStockAfterSale($produkId, $soldQuantity, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT sg.id, sg.jumlah_stok 
            FROM stok_gudang sg
            JOIN produk p ON sg.produk_id = p.id
            WHERE sg.produk_id = ? AND p.penjual_id = ? AND sg.jumlah_stok > 0
            ORDER BY sg.created_at ASC
        ");
        $stmt->bind_param("ii", $produkId, $penjualId);
        $stmt->execute();
        $inventories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $remainingToDeduct = $soldQuantity;
        
        foreach ($inventories as $inventory) {
            if ($remainingToDeduct <= 0) break;
            
            $deductFromThis = min($inventory['jumlah_stok'], $remainingToDeduct);
            $newStock = $inventory['jumlah_stok'] - $deductFromThis;
            
            $updateStmt = $this->db->prepare("
                UPDATE stok_gudang SET jumlah_stok = ? WHERE id = ?
            ");
            $updateStmt->bind_param("di", $newStock, $inventory['id']);
            $updateStmt->execute();
            
            $remainingToDeduct -= $deductFromThis;
        }
        
        $this->updateProductStock($produkId, $penjualId);
        return $remainingToDeduct <= 0;
    }

    public function restockInventory($produkId, $quantity, $location, $batchId, $penjualId) {
        $restockData = [
            'produk_id' => $produkId,
            'batch_id' => $batchId,
            'jumlah_stok' => $quantity,
            'lokasi_gudang' => $location
        ];
        
        return $this->addInventory($restockData, $penjualId);
    }

    public function transferInventory($id, $newLocation, $penjualId) {
        $stmt = $this->db->prepare("
            UPDATE stok_gudang sg 
            JOIN produk p ON sg.produk_id = p.id 
            SET sg.lokasi_gudang = ? 
            WHERE sg.id = ? AND p.penjual_id = ?
        ");
        $stmt->bind_param("sii", $newLocation, $id, $penjualId);
        return $stmt->execute();
    }

    public function getInventoryStatistics($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT sg.id) as total_inventory_entries,
                COUNT(DISTINCT sg.produk_id) as products_in_stock,
                COUNT(DISTINCT sg.lokasi_gudang) as storage_locations,
                SUM(sg.jumlah_stok) as total_units,
                SUM(sg.jumlah_stok * p.harga) as total_inventory_value,
                AVG(sg.jumlah_stok) as average_stock_per_entry,
                COUNT(CASE WHEN sg.jumlah_stok = 0 THEN 1 END) as empty_entries,
                COUNT(CASE WHEN sg.jumlah_stok <= 5 THEN 1 END) as low_stock_entries
            FROM stok_gudang sg
            JOIN produk p ON sg.produk_id = p.id
            WHERE p.penjual_id = ?
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getStockLevels($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.nama_produk,
                p.harga,
                p.berat,
                k.nama_kategori,
                SUM(sg.jumlah_stok) as total_stock,
                COUNT(sg.id) as inventory_entries,
                COUNT(DISTINCT sg.lokasi_gudang) as locations_count,
                GROUP_CONCAT(DISTINCT sg.lokasi_gudang) as locations,
                SUM(sg.jumlah_stok * p.harga) as stock_value,
                MIN(sg.created_at) as oldest_stock_date,
                MAX(sg.created_at) as newest_stock_date
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            LEFT JOIN stok_gudang sg ON p.id = sg.produk_id
            WHERE p.penjual_id = ?
            GROUP BY p.id, p.nama_produk, p.harga, p.berat, k.nama_kategori
            ORDER BY total_stock DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getLocationAnalysis($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                sg.lokasi_gudang,
                COUNT(DISTINCT sg.produk_id) as unique_products,
                COUNT(sg.id) as inventory_entries,
                SUM(sg.jumlah_stok) as total_units,
                SUM(sg.jumlah_stok * p.harga) as total_value,
                AVG(sg.jumlah_stok) as average_stock_per_entry,
                COUNT(CASE WHEN sg.jumlah_stok <= 5 THEN 1 END) as low_stock_entries
            FROM stok_gudang sg
            JOIN produk p ON sg.produk_id = p.id
            WHERE p.penjual_id = ?
            GROUP BY sg.lokasi_gudang
            ORDER BY total_value DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getLowStockItems($threshold, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.nama_produk,
                p.harga,
                k.nama_kategori,
                SUM(sg.jumlah_stok) as total_stock,
                COUNT(DISTINCT sg.lokasi_gudang) as locations_count,
                GROUP_CONCAT(DISTINCT sg.lokasi_gudang) as locations,
                SUM(sg.jumlah_stok * p.harga) as stock_value
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            LEFT JOIN stok_gudang sg ON p.id = sg.produk_id
            WHERE p.penjual_id = ?
            GROUP BY p.id, p.nama_produk, p.harga, k.nama_kategori
            HAVING total_stock <= ?
            ORDER BY total_stock ASC
        ");
        $stmt->bind_param("id", $penjualId, $threshold);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getInventoryAging($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                sg.*,
                p.nama_produk,
                p.harga,
                bp.kode_batch,
                bp.tanggal_panen,
                DATEDIFF(NOW(), sg.created_at) as days_in_storage,
                DATEDIFF(NOW(), bp.tanggal_panen) as days_since_harvest,
                (sg.jumlah_stok * p.harga) as inventory_value,
                CASE 
                    WHEN DATEDIFF(NOW(), sg.created_at) <= 7 THEN 'Very Fresh'
                    WHEN DATEDIFF(NOW(), sg.created_at) <= 30 THEN 'Fresh'
                    WHEN DATEDIFF(NOW(), sg.created_at) <= 60 THEN 'Good'
                    WHEN DATEDIFF(NOW(), sg.created_at) <= 90 THEN 'Aging'
                    ELSE 'Old'
                END as freshness_grade
            FROM stok_gudang sg
            JOIN produk p ON sg.produk_id = p.id
            LEFT JOIN batch_produksi bp ON sg.batch_id = bp.id
            WHERE p.penjual_id = ?
            ORDER BY days_in_storage DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getInventoryValuation($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                sg.lokasi_gudang,
                p.nama_produk,
                k.nama_kategori,
                sg.jumlah_stok,
                p.harga,
                (sg.jumlah_stok * p.harga) as inventory_value,
                bp.kode_batch,
                CASE 
                    WHEN p.harga >= 100000 THEN 'Premium'
                    WHEN p.harga >= 50000 THEN 'Mid-range'
                    ELSE 'Economy'
                END as price_tier
            FROM stok_gudang sg
            JOIN produk p ON sg.produk_id = p.id
            JOIN kategori_produk k ON p.kategori_id = k.id
            LEFT JOIN batch_produksi bp ON sg.batch_id = bp.id
            WHERE p.penjual_id = ?
            ORDER BY inventory_value DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getCategoryStockAnalysis($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                k.id as kategori_id,
                k.nama_kategori,
                COUNT(DISTINCT sg.produk_id) as products_count,
                COUNT(sg.id) as inventory_entries,
                SUM(sg.jumlah_stok) as total_units,
                SUM(sg.jumlah_stok * p.harga) as total_value,
                AVG(sg.jumlah_stok) as average_stock,
                COUNT(DISTINCT sg.lokasi_gudang) as storage_locations
            FROM kategori_produk k
            LEFT JOIN produk p ON k.id = p.kategori_id AND p.penjual_id = ?
            LEFT JOIN stok_gudang sg ON p.id = sg.produk_id
            GROUP BY k.id, k.nama_kategori
            ORDER BY total_value DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function searchInventory($query, $penjualId) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT 
                sg.*,
                p.nama_produk,
                p.harga,
                k.nama_kategori,
                bp.kode_batch
            FROM stok_gudang sg
            JOIN produk p ON sg.produk_id = p.id
            JOIN kategori_produk k ON p.kategori_id = k.id
            LEFT JOIN batch_produksi bp ON sg.batch_id = bp.id
            WHERE (p.nama_produk LIKE ? OR sg.lokasi_gudang LIKE ? OR bp.kode_batch LIKE ?)
            AND p.penjual_id = ?
            ORDER BY sg.created_at DESC
            LIMIT 50
        ");
        $stmt->bind_param("sssi", $searchTerm, $searchTerm, $searchTerm, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getInventoryMovements($penjualId, $days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(sg.created_at) as movement_date,
                COUNT(sg.id) as new_entries,
                SUM(sg.jumlah_stok) as total_units_added,
                COUNT(DISTINCT sg.produk_id) as products_restocked,
                SUM(sg.jumlah_stok * p.harga) as total_value_added
            FROM stok_gudang sg
            JOIN produk p ON sg.produk_id = p.id
            WHERE p.penjual_id = ? 
            AND sg.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(sg.created_at)
            ORDER BY movement_date DESC
        ");
        $stmt->bind_param("ii", $penjualId, $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getStockAlerts($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                'LOW_STOCK' as alert_type,
                p.nama_produk,
                SUM(sg.jumlah_stok) as current_stock,
                'Stock rendah, pertimbangkan untuk restocking' as message,
                p.id as produk_id
            FROM produk p
            LEFT JOIN stok_gudang sg ON p.id = sg.produk_id
            WHERE p.penjual_id = ?
            GROUP BY p.id, p.nama_produk
            HAVING current_stock <= 5
            
            UNION ALL
            
            SELECT 
                'OLD_STOCK' as alert_type,
                p.nama_produk,
                sg.jumlah_stok as current_stock,
                CONCAT('Stock sudah ', DATEDIFF(NOW(), sg.created_at), ' hari di gudang') as message,
                p.id as produk_id
            FROM stok_gudang sg
            JOIN produk p ON sg.produk_id = p.id
            WHERE p.penjual_id = ? 
            AND DATEDIFF(NOW(), sg.created_at) > 90
            AND sg.jumlah_stok > 0
            
            ORDER BY alert_type, current_stock ASC
        ");
        $stmt->bind_param("ii", $penjualId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function checkInventoryOwnership($inventoryId, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT sg.id 
            FROM stok_gudang sg
            JOIN produk p ON sg.produk_id = p.id
            WHERE sg.id = ? AND p.penjual_id = ?
        ");
        $stmt->bind_param("ii", $inventoryId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    private function updateProductStock($produkId, $penjualId) {
        $stmt = $this->db->prepare("
            UPDATE produk p 
            SET p.stok = (
                SELECT COALESCE(SUM(sg.jumlah_stok), 0) 
                FROM stok_gudang sg 
                WHERE sg.produk_id = p.id
            )
            WHERE p.id = ? AND p.penjual_id = ?
        ");
        $stmt->bind_param("ii", $produkId, $penjualId);
        return $stmt->execute();
    }
}