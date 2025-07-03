<?php
class LimitProdukModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAllProduk($penjualId) {
        $stmt = $this->db->prepare("
            SELECT p.*, k.nama_kategori, u.nama_lengkap as penjual_name
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            JOIN users u ON p.penjual_id = u.id
            WHERE p.penjual_id = ? AND p.kategori_id = 1
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProdukById($id, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT p.*, k.nama_kategori, u.nama_lengkap as penjual_name
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            JOIN users u ON p.penjual_id = u.id
            WHERE p.id = ? AND p.penjual_id = ? AND p.kategori_id = 1
        ");
        $stmt->bind_param("ii", $id, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getProdukByStatus($status, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT p.*, k.nama_kategori
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            WHERE p.status = ? AND p.penjual_id = ? AND p.kategori_id = 1
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("si", $status, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function createProduk($data) {
        $data['kategori_id'] = 1;
        
        $stmt = $this->db->prepare("
            INSERT INTO produk (nama_produk, deskripsi, harga, stok, kategori_id, penjual_id, foto, berat, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $status = $data['status'] ?? 'aktif';
        
        $stmt->bind_param(
            "ssdiiisds",
            $data['nama_produk'],
            $data['deskripsi'],
            $data['harga'],
            $data['stok'],
            $data['kategori_id'],
            $data['penjual_id'],
            $data['foto'],
            $data['berat'],
            $status
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function updateProduk($id, $data, $penjualId) {
        $fields = [];
        $types = "";
        $values = [];

        if (isset($data['nama_produk'])) {
            $fields[] = "nama_produk = ?";
            $types .= "s";
            $values[] = $data['nama_produk'];
        }

        if (isset($data['deskripsi'])) {
            $fields[] = "deskripsi = ?";
            $types .= "s";
            $values[] = $data['deskripsi'];
        }

        if (isset($data['harga'])) {
            $fields[] = "harga = ?";
            $types .= "d";
            $values[] = $data['harga'];
        }

        if (isset($data['stok'])) {
            $fields[] = "stok = ?";
            $types .= "i";
            $values[] = $data['stok'];
        }

        if (isset($data['foto'])) {
            $fields[] = "foto = ?";
            $types .= "s";
            $values[] = $data['foto'];
        }

        if (isset($data['berat'])) {
            $fields[] = "berat = ?";
            $types .= "d";
            $values[] = $data['berat'];
        }

        if (isset($data['status'])) {
            $fields[] = "status = ?";
            $types .= "s";
            $values[] = $data['status'];
        }

        if (empty($fields)) {
            return false;
        }

        $types .= "ii";
        $values[] = $id;
        $values[] = $penjualId;

        $sql = "UPDATE produk SET " . implode(", ", $fields) . " WHERE id = ? AND penjual_id = ? AND kategori_id = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    public function deleteProduk($id, $penjualId) {
        $stmt = $this->db->prepare("UPDATE produk SET status = 'nonaktif' WHERE id = ? AND penjual_id = ? AND kategori_id = 1");
        $stmt->bind_param("ii", $id, $penjualId);
        return $stmt->execute();
    }

    public function updateHarga($id, $harga, $penjualId) {
        $stmt = $this->db->prepare("UPDATE produk SET harga = ? WHERE id = ? AND penjual_id = ? AND kategori_id = 1");
        $stmt->bind_param("dii", $harga, $id, $penjualId);
        return $stmt->execute();
    }

    public function updateStok($id, $stok, $penjualId) {
        $stmt = $this->db->prepare("UPDATE produk SET stok = ? WHERE id = ? AND penjual_id = ? AND kategori_id = 1");
        $stmt->bind_param("iii", $stok, $id, $penjualId);
        return $stmt->execute();
    }

    public function updateStatus($id, $status, $penjualId) {
        $stmt = $this->db->prepare("UPDATE produk SET status = ? WHERE id = ? AND penjual_id = ? AND kategori_id = 1");
        $stmt->bind_param("sii", $status, $id, $penjualId);
        return $stmt->execute();
    }

    public function getProdukStats($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_produk,
                SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif_count,
                SUM(CASE WHEN status = 'nonaktif' THEN 1 ELSE 0 END) as nonaktif_count,
                SUM(stok) as total_stok,
                AVG(harga) as avg_harga,
                MIN(harga) as min_harga,
                MAX(harga) as max_harga,
                SUM(stok * harga) as total_nilai_stok
            FROM produk
            WHERE penjual_id = ? AND kategori_id = 1
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function searchProduk($query, $penjualId) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT p.*, k.nama_kategori
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            WHERE (p.nama_produk LIKE ? OR p.deskripsi LIKE ?) 
            AND p.penjual_id = ? AND p.kategori_id = 1
            ORDER BY p.created_at DESC
            LIMIT 50
        ");
        $stmt->bind_param("ssi", $searchTerm, $searchTerm, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getLowStockProduk($threshold, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT p.*, k.nama_kategori
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            WHERE p.stok <= ? AND p.penjual_id = ? AND p.kategori_id = 1 AND p.status = 'aktif'
            ORDER BY p.stok ASC
        ");
        $stmt->bind_param("ii", $threshold, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProdukForWholesale($penjualId) {
        $stmt = $this->db->prepare("
            SELECT p.*, k.nama_kategori,
                CASE 
                    WHEN p.stok >= 100 THEN 'High Volume'
                    WHEN p.stok >= 50 THEN 'Medium Volume'
                    WHEN p.stok >= 10 THEN 'Low Volume'
                    ELSE 'Out of Stock'
                END as volume_category
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            WHERE p.penjual_id = ? AND p.kategori_id = 1 AND p.status = 'aktif'
            ORDER BY p.stok DESC, p.harga ASC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPriceHistory($produkId, $penjualId, $days = 30) {
        $stmt = $this->db->prepare("
            SELECT p.harga, p.created_at as date_recorded
            FROM produk p
            WHERE p.id = ? AND p.penjual_id = ? AND p.kategori_id = 1
            AND p.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("iii", $produkId, $penjualId, $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getStockMovement($produkId, $penjualId, $days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                p.stok as current_stock,
                p.created_at,
                p.nama_produk
            FROM produk p
            WHERE p.id = ? AND p.penjual_id = ? AND p.kategori_id = 1
            AND p.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("iii", $produkId, $penjualId, $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function bulkUpdatePrices($updates, $penjualId) {
        $success = 0;
        
        foreach ($updates as $update) {
            if (isset($update['id']) && isset($update['harga'])) {
                if ($this->updateHarga($update['id'], $update['harga'], $penjualId)) {
                    $success++;
                }
            }
        }
        
        return $success;
    }

    public function bulkUpdateStatus($ids, $status, $penjualId) {
        if (empty($ids)) return false;
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "UPDATE produk SET status = ? WHERE id IN ($placeholders) AND penjual_id = ? AND kategori_id = 1";
        
        $stmt = $this->db->prepare($sql);
        $types = str_repeat('i', count($ids));
        $stmt->bind_param('s' . $types . 'i', $status, ...array_merge($ids, [$penjualId]));
        
        return $stmt->execute();
    }

    public function getGreenBeanVarieties($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                CASE 
                    WHEN p.nama_produk LIKE '%arabika%' THEN 'Arabika'
                    WHEN p.nama_produk LIKE '%robusta%' THEN 'Robusta'
                    ELSE 'Mixed'
                END as variety,
                COUNT(*) as product_count,
                SUM(p.stok) as total_stock,
                AVG(p.harga) as avg_price,
                MIN(p.harga) as min_price,
                MAX(p.harga) as max_price
            FROM produk p
            WHERE p.penjual_id = ? AND p.kategori_id = 1 AND p.status = 'aktif'
            GROUP BY variety
            ORDER BY total_stock DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function checkProdukOwnership($produkId, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT id FROM produk 
            WHERE id = ? AND penjual_id = ? AND kategori_id = 1
        ");
        $stmt->bind_param("ii", $produkId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function validateGreenBeanProduct($data) {
        if (!isset($data['nama_produk']) || !strpos(strtolower($data['nama_produk']), 'green') && !strpos(strtolower($data['nama_produk']), 'bean')) {
            return false;
        }
        
        return true;
    }

    public function getRecentlyUpdated($penjualId, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT p.*, k.nama_kategori
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            WHERE p.penjual_id = ? AND p.kategori_id = 1
            ORDER BY p.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $penjualId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getHighValueProducts($penjualId, $minPrice = 100000) {
        $stmt = $this->db->prepare("
            SELECT p.*, k.nama_kategori,
                (p.stok * p.harga) as total_value
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            WHERE p.penjual_id = ? AND p.kategori_id = 1 
            AND p.harga >= ? AND p.status = 'aktif'
            ORDER BY total_value DESC
        ");
        $stmt->bind_param("id", $penjualId, $minPrice);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}