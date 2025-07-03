<?php
class PenjualProdukModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAllProducts($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                p.*,
                k.nama_kategori,
                COALESCE(AVG(u.rating), 0) as average_rating,
                COUNT(u.id) as review_count,
                COALESCE(SUM(dp.jumlah), 0) as total_sold
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            LEFT JOIN ulasan u ON p.id = u.produk_id
            LEFT JOIN detail_pesanan dp ON p.id = dp.produk_id
            LEFT JOIN pesanan ps ON dp.pesanan_id = ps.id AND ps.status_pesanan = 'delivered'
            WHERE p.penjual_id = ?
            GROUP BY p.id, p.nama_produk, p.deskripsi, p.harga, p.stok, p.kategori_id, 
                     p.penjual_id, p.foto, p.berat, p.status, p.created_at, k.nama_kategori
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProductById($id, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                p.*,
                k.nama_kategori,
                COALESCE(AVG(u.rating), 0) as average_rating,
                COUNT(u.id) as review_count,
                COALESCE(SUM(dp.jumlah), 0) as total_sold
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            LEFT JOIN ulasan u ON p.id = u.produk_id
            LEFT JOIN detail_pesanan dp ON p.id = dp.produk_id
            LEFT JOIN pesanan ps ON dp.pesanan_id = ps.id AND ps.status_pesanan = 'delivered'
            WHERE p.id = ? AND p.penjual_id = ?
            GROUP BY p.id, p.nama_produk, p.deskripsi, p.harga, p.stok, p.kategori_id, 
                     p.penjual_id, p.foto, p.berat, p.status, p.created_at, k.nama_kategori
        ");
        $stmt->bind_param("ii", $id, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function createProduct($data, $penjualId) {
        $stmt = $this->db->prepare("
            INSERT INTO produk (nama_produk, deskripsi, harga, stok, kategori_id, penjual_id, foto, berat, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "ssdiiisds",
            $data['nama_produk'],
            $data['deskripsi'],
            $data['harga'],
            $data['stok'],
            $data['kategori_id'],
            $penjualId,
            $data['foto'],
            $data['berat'],
            $data['status']
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function updateProduct($id, $data, $penjualId) {
        $fields = [];
        $types = "";
        $values = [];

        $allowedFields = ['nama_produk', 'deskripsi', 'harga', 'stok', 'kategori_id', 'foto', 'berat', 'status'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
                
                switch ($field) {
                    case 'harga':
                    case 'berat':
                        $types .= "d";
                        break;
                    case 'stok':
                    case 'kategori_id':
                        $types .= "i";
                        break;
                    default:
                        $types .= "s";
                        break;
                }
            }
        }

        if (empty($fields)) {
            return false;
        }

        $types .= "ii";
        $values[] = $id;
        $values[] = $penjualId;

        $sql = "UPDATE produk SET " . implode(", ", $fields) . " WHERE id = ? AND penjual_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    public function deleteProduct($id, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as order_count 
            FROM detail_pesanan dp 
            JOIN pesanan p ON dp.pesanan_id = p.id 
            WHERE dp.produk_id = ? AND p.status_pesanan NOT IN ('cancelled')
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['order_count'] > 0) {
            return $this->updateProduct($id, ['status' => 'nonaktif'], $penjualId);
        }

        $stmt = $this->db->prepare("DELETE FROM produk WHERE id = ? AND penjual_id = ?");
        $stmt->bind_param("ii", $id, $penjualId);
        return $stmt->execute();
    }

    public function getProductsByCategory($kategoriId, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                p.*,
                k.nama_kategori,
                COALESCE(AVG(u.rating), 0) as average_rating,
                COUNT(u.id) as review_count
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            LEFT JOIN ulasan u ON p.id = u.produk_id
            WHERE p.kategori_id = ? AND p.penjual_id = ?
            GROUP BY p.id, p.nama_produk, p.deskripsi, p.harga, p.stok, p.kategori_id, 
                     p.penjual_id, p.foto, p.berat, p.status, p.created_at, k.nama_kategori
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("ii", $kategoriId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function searchProducts($query, $penjualId) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT 
                p.*,
                k.nama_kategori,
                COALESCE(AVG(u.rating), 0) as average_rating,
                COUNT(u.id) as review_count
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            LEFT JOIN ulasan u ON p.id = u.produk_id
            WHERE (p.nama_produk LIKE ? OR p.deskripsi LIKE ?) AND p.penjual_id = ?
            GROUP BY p.id, p.nama_produk, p.deskripsi, p.harga, p.stok, p.kategori_id, 
                     p.penjual_id, p.foto, p.berat, p.status, p.created_at, k.nama_kategori
            ORDER BY p.created_at DESC
            LIMIT 50
        ");
        $stmt->bind_param("ssi", $searchTerm, $searchTerm, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProductStatistics($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(p.id) as total_products,
                COUNT(CASE WHEN p.status = 'aktif' THEN 1 END) as active_products,
                COUNT(CASE WHEN p.status = 'nonaktif' THEN 1 END) as inactive_products,
                COUNT(CASE WHEN p.stok = 0 THEN 1 END) as out_of_stock,
                COUNT(CASE WHEN p.stok <= 5 THEN 1 END) as low_stock,
                AVG(p.harga) as average_price,
                SUM(p.stok) as total_stock_units,
                COUNT(CASE WHEN p.kategori_id = 1 THEN 1 END) as green_bean_products,
                COUNT(CASE WHEN p.kategori_id = 2 THEN 1 END) as roasted_products
            FROM produk p
            WHERE p.penjual_id = ?
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getTopSellingProducts($penjualId, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.nama_produk,
                p.harga,
                p.foto,
                SUM(dp.jumlah) as total_sold,
                SUM(dp.subtotal) as total_revenue,
                COUNT(DISTINCT dp.pesanan_id) as order_count,
                AVG(u.rating) as average_rating
            FROM produk p
            JOIN detail_pesanan dp ON p.id = dp.produk_id
            JOIN pesanan ps ON dp.pesanan_id = ps.id
            LEFT JOIN ulasan u ON p.id = u.produk_id
            WHERE p.penjual_id = ? AND ps.status_pesanan = 'delivered'
            GROUP BY p.id, p.nama_produk, p.harga, p.foto
            ORDER BY total_sold DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $penjualId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getLowStockProducts($penjualId, $threshold = 5) {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.nama_produk,
                p.harga,
                p.stok,
                p.foto,
                k.nama_kategori
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            WHERE p.penjual_id = ? AND p.stok <= ? AND p.status = 'aktif'
            ORDER BY p.stok ASC
        ");
        $stmt->bind_param("ii", $penjualId, $threshold);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProductPerformance($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.nama_produk,
                p.harga,
                p.stok,
                COUNT(dp.id) as order_frequency,
                SUM(dp.jumlah) as total_sold,
                SUM(dp.subtotal) as total_revenue,
                AVG(u.rating) as average_rating,
                COUNT(u.id) as review_count,
                MAX(ps.created_at) as last_order_date,
                DATEDIFF(NOW(), MAX(ps.created_at)) as days_since_last_order
            FROM produk p
            LEFT JOIN detail_pesanan dp ON p.id = dp.produk_id
            LEFT JOIN pesanan ps ON dp.pesanan_id = ps.id AND ps.status_pesanan = 'delivered'
            LEFT JOIN ulasan u ON p.id = u.produk_id
            WHERE p.penjual_id = ?
            GROUP BY p.id, p.nama_produk, p.harga, p.stok
            ORDER BY total_revenue DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRevenueByProduct($penjualId, $startDate = null, $endDate = null) {
        $whereClause = "WHERE p.penjual_id = ? AND ps.status_pesanan = 'delivered'";
        $params = [$penjualId];
        $types = "i";

        if ($startDate && $endDate) {
            $whereClause .= " AND ps.created_at BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        }

        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.nama_produk,
                p.harga,
                SUM(dp.jumlah) as units_sold,
                SUM(dp.subtotal) as total_revenue,
                COUNT(DISTINCT ps.id) as order_count,
                AVG(dp.harga) as average_selling_price
            FROM produk p
            JOIN detail_pesanan dp ON p.id = dp.produk_id
            JOIN pesanan ps ON dp.pesanan_id = ps.id
            $whereClause
            GROUP BY p.id, p.nama_produk, p.harga
            ORDER BY total_revenue DESC
        ");
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getCategoryAnalysis($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                k.id as kategori_id,
                k.nama_kategori,
                COUNT(p.id) as product_count,
                SUM(p.stok) as total_stock,
                AVG(p.harga) as average_price,
                COALESCE(SUM(dp.jumlah), 0) as total_sold,
                COALESCE(SUM(dp.subtotal), 0) as total_revenue,
                COALESCE(AVG(u.rating), 0) as average_rating
            FROM kategori_produk k
            LEFT JOIN produk p ON k.id = p.kategori_id AND p.penjual_id = ?
            LEFT JOIN detail_pesanan dp ON p.id = dp.produk_id
            LEFT JOIN pesanan ps ON dp.pesanan_id = ps.id AND ps.status_pesanan = 'delivered'
            LEFT JOIN ulasan u ON p.id = u.produk_id
            GROUP BY k.id, k.nama_kategori
            ORDER BY total_revenue DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPackagingSizeAnalysis($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                CASE 
                    WHEN p.berat <= 100 THEN '100g atau kurang'
                    WHEN p.berat <= 250 THEN '101-250g'
                    WHEN p.berat <= 500 THEN '251-500g'
                    WHEN p.berat <= 1000 THEN '501-1000g'
                    ELSE 'Lebih dari 1kg'
                END as package_size,
                COUNT(p.id) as product_count,
                AVG(p.harga) as average_price,
                SUM(p.stok) as total_stock,
                COALESCE(SUM(dp.jumlah), 0) as total_sold,
                COALESCE(SUM(dp.subtotal), 0) as total_revenue
            FROM produk p
            LEFT JOIN detail_pesanan dp ON p.id = dp.produk_id
            LEFT JOIN pesanan ps ON dp.pesanan_id = ps.id AND ps.status_pesanan = 'delivered'
            WHERE p.penjual_id = ?
            GROUP BY package_size
            ORDER BY total_revenue DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getCompetitivePricing($penjualId, $produkId) {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.nama_produk,
                p.harga,
                p.berat,
                u.nama_toko,
                AVG(ul.rating) as average_rating,
                COUNT(ul.id) as review_count
            FROM produk p
            JOIN users u ON p.penjual_id = u.id
            LEFT JOIN ulasan ul ON p.id = ul.produk_id
            WHERE p.id != ? AND p.nama_produk LIKE (
                SELECT CONCAT('%', SUBSTRING_INDEX(nama_produk, ' ', 2), '%') 
                FROM produk WHERE id = ?
            )
            GROUP BY p.id, p.nama_produk, p.harga, p.berat, u.nama_toko
            ORDER BY p.harga ASC
            LIMIT 10
        ");
        $stmt->bind_param("ii", $produkId, $produkId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function updateStock($id, $newStock, $penjualId) {
        $stmt = $this->db->prepare("
            UPDATE produk SET stok = ? WHERE id = ? AND penjual_id = ?
        ");
        $stmt->bind_param("iii", $newStock, $id, $penjualId);
        return $stmt->execute();
    }

    public function bulkUpdatePrices($products, $penjualId) {
        $this->db->begin_transaction();
        
        try {
            $stmt = $this->db->prepare("
                UPDATE produk SET harga = ? WHERE id = ? AND penjual_id = ?
            ");
            
            foreach ($products as $product) {
                $stmt->bind_param("dii", $product['harga'], $product['id'], $penjualId);
                $stmt->execute();
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function checkProductOwnership($produkId, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT id FROM produk WHERE id = ? AND penjual_id = ?
        ");
        $stmt->bind_param("ii", $produkId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function getProductRecommendations($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                'low_stock' as recommendation_type,
                'Stok Rendah' as title,
                CONCAT('Produk ', p.nama_produk, ' memiliki stok ', p.stok, ' unit. Pertimbangkan untuk restocking.') as message,
                p.id as produk_id,
                p.nama_produk
            FROM produk p
            WHERE p.penjual_id = ? AND p.stok <= 5 AND p.status = 'aktif'
            
            UNION ALL
            
            SELECT 
                'no_recent_orders' as recommendation_type,
                'Tidak Ada Pesanan' as title,
                CONCAT('Produk ', p.nama_produk, ' tidak ada pesanan dalam 30 hari terakhir. Pertimbangkan promosi atau review harga.') as message,
                p.id as produk_id,
                p.nama_produk
            FROM produk p
            WHERE p.penjual_id = ? 
            AND p.id NOT IN (
                SELECT DISTINCT dp.produk_id 
                FROM detail_pesanan dp 
                JOIN pesanan ps ON dp.pesanan_id = ps.id 
                WHERE ps.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            )
            AND p.status = 'aktif'
            
            LIMIT 10
        ");
        $stmt->bind_param("ii", $penjualId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}