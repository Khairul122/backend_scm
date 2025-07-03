<?php
class PembeliProdukModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAllProducts($filters = []) {
        $whereClause = "WHERE p.status = 'aktif'";
        $params = [];
        $types = "";

        if (isset($filters['kategori_id']) && !empty($filters['kategori_id'])) {
            $whereClause .= " AND p.kategori_id = ?";
            $params[] = $filters['kategori_id'];
            $types .= "i";
        }

        if (isset($filters['min_price']) && !empty($filters['min_price'])) {
            $whereClause .= " AND p.harga >= ?";
            $params[] = $filters['min_price'];
            $types .= "d";
        }

        if (isset($filters['max_price']) && !empty($filters['max_price'])) {
            $whereClause .= " AND p.harga <= ?";
            $params[] = $filters['max_price'];
            $types .= "d";
        }

        if (isset($filters['in_stock']) && $filters['in_stock']) {
            $whereClause .= " AND p.stok > 0";
        }

        if (isset($filters['penjual_id']) && !empty($filters['penjual_id'])) {
            $whereClause .= " AND p.penjual_id = ?";
            $params[] = $filters['penjual_id'];
            $types .= "i";
        }

        $orderBy = "ORDER BY p.created_at DESC";
        if (isset($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price_asc':
                    $orderBy = "ORDER BY p.harga ASC";
                    break;
                case 'price_desc':
                    $orderBy = "ORDER BY p.harga DESC";
                    break;
                case 'name_asc':
                    $orderBy = "ORDER BY p.nama_produk ASC";
                    break;
                case 'name_desc':
                    $orderBy = "ORDER BY p.nama_produk DESC";
                    break;
                case 'rating':
                    $orderBy = "ORDER BY average_rating DESC";
                    break;
                case 'popular':
                    $orderBy = "ORDER BY total_sold DESC";
                    break;
            }
        }

        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 20;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;

        $stmt = $this->db->prepare("
            SELECT 
                p.*,
                k.nama_kategori,
                u.nama_lengkap as seller_name,
                u.nama_toko as store_name,
                COALESCE(AVG(ul.rating), 0) as average_rating,
                COUNT(ul.id) as review_count,
                COALESCE(SUM(dp.jumlah), 0) as total_sold,
                CASE WHEN w.id IS NOT NULL THEN 1 ELSE 0 END as is_wishlisted
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            JOIN users u ON p.penjual_id = u.id
            LEFT JOIN ulasan ul ON p.id = ul.produk_id
            LEFT JOIN detail_pesanan dp ON p.id = dp.produk_id
            LEFT JOIN pesanan ps ON dp.pesanan_id = ps.id AND ps.status_pesanan = 'delivered'
            LEFT JOIN wishlist w ON p.id = w.produk_id
            $whereClause
            GROUP BY p.id, p.nama_produk, p.deskripsi, p.harga, p.stok, p.kategori_id, 
                     p.penjual_id, p.foto, p.berat, p.status, p.created_at,
                     k.nama_kategori, u.nama_lengkap, u.nama_toko, w.id
            $orderBy
            LIMIT ? OFFSET ?
        ");

        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProductById($productId) {
        $stmt = $this->db->prepare("
            SELECT 
                p.*,
                k.nama_kategori,
                u.nama_lengkap as seller_name,
                u.nama_toko as store_name,
                u.alamat as seller_address,
                COALESCE(AVG(ul.rating), 0) as average_rating,
                COUNT(ul.id) as review_count,
                COALESCE(SUM(dp.jumlah), 0) as total_sold,
                COUNT(DISTINCT ps.id) as order_count
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            JOIN users u ON p.penjual_id = u.id
            LEFT JOIN ulasan ul ON p.id = ul.produk_id
            LEFT JOIN detail_pesanan dp ON p.id = dp.produk_id
            LEFT JOIN pesanan ps ON dp.pesanan_id = ps.id AND ps.status_pesanan = 'delivered'
            WHERE p.id = ? AND p.status = 'aktif'
            GROUP BY p.id, p.nama_produk, p.deskripsi, p.harga, p.stok, p.kategori_id, 
                     p.penjual_id, p.foto, p.berat, p.status, p.created_at,
                     k.nama_kategori, u.nama_lengkap, u.nama_toko, u.alamat
        ");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function searchProducts($query, $filters = []) {
        $searchTerm = "%$query%";
        $whereClause = "WHERE p.status = 'aktif' AND (p.nama_produk LIKE ? OR p.deskripsi LIKE ? OR k.nama_kategori LIKE ?)";
        $params = [$searchTerm, $searchTerm, $searchTerm];
        $types = "sss";

        if (isset($filters['kategori_id']) && !empty($filters['kategori_id'])) {
            $whereClause .= " AND p.kategori_id = ?";
            $params[] = $filters['kategori_id'];
            $types .= "i";
        }

        if (isset($filters['min_price']) && !empty($filters['min_price'])) {
            $whereClause .= " AND p.harga >= ?";
            $params[] = $filters['min_price'];
            $types .= "d";
        }

        if (isset($filters['max_price']) && !empty($filters['max_price'])) {
            $whereClause .= " AND p.harga <= ?";
            $params[] = $filters['max_price'];
            $types .= "d";
        }

        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 20;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;

        $stmt = $this->db->prepare("
            SELECT 
                p.*,
                k.nama_kategori,
                u.nama_lengkap as seller_name,
                u.nama_toko as store_name,
                COALESCE(AVG(ul.rating), 0) as average_rating,
                COUNT(ul.id) as review_count,
                COALESCE(SUM(dp.jumlah), 0) as total_sold
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            JOIN users u ON p.penjual_id = u.id
            LEFT JOIN ulasan ul ON p.id = ul.produk_id
            LEFT JOIN detail_pesanan dp ON p.id = dp.produk_id
            LEFT JOIN pesanan ps ON dp.pesanan_id = ps.id AND ps.status_pesanan = 'delivered'
            $whereClause
            GROUP BY p.id, p.nama_produk, p.deskripsi, p.harga, p.stok, p.kategori_id, 
                     p.penjual_id, p.foto, p.berat, p.status, p.created_at,
                     k.nama_kategori, u.nama_lengkap, u.nama_toko
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");

        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProductsByCategory($categoryId, $filters = []) {
        $whereClause = "WHERE p.status = 'aktif' AND p.kategori_id = ?";
        $params = [$categoryId];
        $types = "i";

        if (isset($filters['min_price']) && !empty($filters['min_price'])) {
            $whereClause .= " AND p.harga >= ?";
            $params[] = $filters['min_price'];
            $types .= "d";
        }

        if (isset($filters['max_price']) && !empty($filters['max_price'])) {
            $whereClause .= " AND p.harga <= ?";
            $params[] = $filters['max_price'];
            $types .= "d";
        }

        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 20;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;

        $stmt = $this->db->prepare("
            SELECT 
                p.*,
                k.nama_kategori,
                u.nama_lengkap as seller_name,
                u.nama_toko as store_name,
                COALESCE(AVG(ul.rating), 0) as average_rating,
                COUNT(ul.id) as review_count,
                COALESCE(SUM(dp.jumlah), 0) as total_sold
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            JOIN users u ON p.penjual_id = u.id
            LEFT JOIN ulasan ul ON p.id = ul.produk_id
            LEFT JOIN detail_pesanan dp ON p.id = dp.produk_id
            LEFT JOIN pesanan ps ON dp.pesanan_id = ps.id AND ps.status_pesanan = 'delivered'
            $whereClause
            GROUP BY p.id, p.nama_produk, p.deskripsi, p.harga, p.stok, p.kategori_id, 
                     p.penjual_id, p.foto, p.berat, p.status, p.created_at,
                     k.nama_kategori, u.nama_lengkap, u.nama_toko
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");

        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProductsBySeller($sellerId, $filters = []) {
        $whereClause = "WHERE p.status = 'aktif' AND p.penjual_id = ?";
        $params = [$sellerId];
        $types = "i";

        if (isset($filters['kategori_id']) && !empty($filters['kategori_id'])) {
            $whereClause .= " AND p.kategori_id = ?";
            $params[] = $filters['kategori_id'];
            $types .= "i";
        }

        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 20;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;

        $stmt = $this->db->prepare("
            SELECT 
                p.*,
                k.nama_kategori,
                u.nama_lengkap as seller_name,
                u.nama_toko as store_name,
                COALESCE(AVG(ul.rating), 0) as average_rating,
                COUNT(ul.id) as review_count,
                COALESCE(SUM(dp.jumlah), 0) as total_sold
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            JOIN users u ON p.penjual_id = u.id
            LEFT JOIN ulasan ul ON p.id = ul.produk_id
            LEFT JOIN detail_pesanan dp ON p.id = dp.produk_id
            LEFT JOIN pesanan ps ON dp.pesanan_id = ps.id AND ps.status_pesanan = 'delivered'
            $whereClause
            GROUP BY p.id, p.nama_produk, p.deskripsi, p.harga, p.stok, p.kategori_id, 
                     p.penjual_id, p.foto, p.berat, p.status, p.created_at,
                     k.nama_kategori, u.nama_lengkap, u.nama_toko
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");

        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getFeaturedProducts($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT 
                p.*,
                k.nama_kategori,
                u.nama_lengkap as seller_name,
                u.nama_toko as store_name,
                COALESCE(AVG(ul.rating), 0) as average_rating,
                COUNT(ul.id) as review_count,
                COALESCE(SUM(dp.jumlah), 0) as total_sold
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            JOIN users u ON p.penjual_id = u.id
            LEFT JOIN ulasan ul ON p.id = ul.produk_id
            LEFT JOIN detail_pesanan dp ON p.id = dp.produk_id
            LEFT JOIN pesanan ps ON dp.pesanan_id = ps.id AND ps.status_pesanan = 'delivered'
            WHERE p.status = 'aktif' AND p.stok > 0
            GROUP BY p.id, p.nama_produk, p.deskripsi, p.harga, p.stok, p.kategori_id, 
                     p.penjual_id, p.foto, p.berat, p.status, p.created_at,
                     k.nama_kategori, u.nama_lengkap, u.nama_toko
            HAVING average_rating >= 4.0 OR total_sold >= 10
            ORDER BY (average_rating * 0.4 + (total_sold / 100) * 0.6) DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPopularProducts($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT 
                p.*,
                k.nama_kategori,
                u.nama_lengkap as seller_name,
                u.nama_toko as store_name,
                COALESCE(AVG(ul.rating), 0) as average_rating,
                COUNT(ul.id) as review_count,
                COALESCE(SUM(dp.jumlah), 0) as total_sold
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            JOIN users u ON p.penjual_id = u.id
            LEFT JOIN ulasan ul ON p.id = ul.produk_id
            LEFT JOIN detail_pesanan dp ON p.id = dp.produk_id
            LEFT JOIN pesanan ps ON dp.pesanan_id = ps.id AND ps.status_pesanan = 'delivered'
            WHERE p.status = 'aktif'
            GROUP BY p.id, p.nama_produk, p.deskripsi, p.harga, p.stok, p.kategori_id, 
                     p.penjual_id, p.foto, p.berat, p.status, p.created_at,
                     k.nama_kategori, u.nama_lengkap, u.nama_toko
            ORDER BY total_sold DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getNewProducts($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT 
                p.*,
                k.nama_kategori,
                u.nama_lengkap as seller_name,
                u.nama_toko as store_name,
                COALESCE(AVG(ul.rating), 0) as average_rating,
                COUNT(ul.id) as review_count
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            JOIN users u ON p.penjual_id = u.id
            LEFT JOIN ulasan ul ON p.id = ul.produk_id
            WHERE p.status = 'aktif' AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY p.id, p.nama_produk, p.deskripsi, p.harga, p.stok, p.kategori_id, 
                     p.penjual_id, p.foto, p.berat, p.status, p.created_at,
                     k.nama_kategori, u.nama_lengkap, u.nama_toko
            ORDER BY p.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRelatedProducts($productId, $limit = 5) {
        $stmt = $this->db->prepare("
            SELECT p2.*,
                k.nama_kategori,
                u.nama_lengkap as seller_name,
                u.nama_toko as store_name,
                COALESCE(AVG(ul.rating), 0) as average_rating,
                COUNT(ul.id) as review_count
            FROM produk p1
            JOIN produk p2 ON p1.kategori_id = p2.kategori_id
            JOIN kategori_produk k ON p2.kategori_id = k.id
            JOIN users u ON p2.penjual_id = u.id
            LEFT JOIN ulasan ul ON p2.id = ul.produk_id
            WHERE p1.id = ? AND p2.id != ? AND p2.status = 'aktif'
            GROUP BY p2.id, p2.nama_produk, p2.deskripsi, p2.harga, p2.stok, p2.kategori_id, 
                     p2.penjual_id, p2.foto, p2.berat, p2.status, p2.created_at,
                     k.nama_kategori, u.nama_lengkap, u.nama_toko
            ORDER BY RAND()
            LIMIT ?
        ");
        $stmt->bind_param("iii", $productId, $productId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProductReviews($productId, $limit = 10, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT 
                ul.*,
                u.nama_lengkap as customer_name,
                ps.kode_pesanan
            FROM ulasan ul
            JOIN users u ON ul.user_id = u.id
            JOIN pesanan ps ON ul.pesanan_id = ps.id
            WHERE ul.produk_id = ?
            ORDER BY ul.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("iii", $productId, $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getCategories() {
        $stmt = $this->db->prepare("
            SELECT 
                k.*,
                COUNT(p.id) as product_count
            FROM kategori_produk k
            LEFT JOIN produk p ON k.id = p.kategori_id AND p.status = 'aktif'
            GROUP BY k.id, k.nama_kategori, k.deskripsi
            ORDER BY k.nama_kategori ASC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPriceRange($categoryId = null) {
        $whereClause = "WHERE p.status = 'aktif'";
        $params = [];
        $types = "";

        if ($categoryId) {
            $whereClause .= " AND p.kategori_id = ?";
            $params[] = $categoryId;
            $types = "i";
        }

        $stmt = $this->db->prepare("
            SELECT 
                MIN(p.harga) as min_price,
                MAX(p.harga) as max_price,
                AVG(p.harga) as avg_price
            FROM produk p
            $whereClause
        ");

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getProductCount($filters = []) {
        $whereClause = "WHERE p.status = 'aktif'";
        $params = [];
        $types = "";

        if (isset($filters['kategori_id']) && !empty($filters['kategori_id'])) {
            $whereClause .= " AND p.kategori_id = ?";
            $params[] = $filters['kategori_id'];
            $types .= "i";
        }

        if (isset($filters['min_price']) && !empty($filters['min_price'])) {
            $whereClause .= " AND p.harga >= ?";
            $params[] = $filters['min_price'];
            $types .= "d";
        }

        if (isset($filters['max_price']) && !empty($filters['max_price'])) {
            $whereClause .= " AND p.harga <= ?";
            $params[] = $filters['max_price'];
            $types .= "d";
        }

        if (isset($filters['in_stock']) && $filters['in_stock']) {
            $whereClause .= " AND p.stok > 0";
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total_count
            FROM produk p
            $whereClause
        ");

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total_count'];
    }

    public function getSellerInfo($sellerId) {
        $stmt = $this->db->prepare("
            SELECT 
                u.id,
                u.nama_lengkap,
                u.nama_toko,
                u.alamat,
                u.created_at,
                COUNT(p.id) as total_products,
                COUNT(CASE WHEN p.status = 'aktif' THEN 1 END) as active_products,
                COALESCE(AVG(ul.rating), 0) as average_rating,
                COUNT(ul.id) as total_reviews
            FROM users u
            LEFT JOIN produk p ON u.id = p.penjual_id
            LEFT JOIN ulasan ul ON p.id = ul.produk_id
            WHERE u.id = ? AND u.role IN ('penjual', 'roasting')
            GROUP BY u.id, u.nama_lengkap, u.nama_toko, u.alamat, u.created_at
        ");
        $stmt->bind_param("i", $sellerId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}