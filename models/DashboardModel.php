<?php
class DashboardModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAdminStats() {
        $stats = [];
        
        $stmt = $this->db->prepare("
            SELECT 
                role,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif
            FROM users 
            GROUP BY role
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats['users'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['users'][$row['role']] = [
                'total' => $row['total'],
                'aktif' => $row['aktif']
            ];
        }

        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status_pesanan = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status_pesanan = 'delivered' THEN total ELSE 0 END) as total_revenue
            FROM pesanan
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $orderStats = $result->fetch_assoc();
        
        $stats['orders'] = $orderStats;

        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total_products,
            SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif_products
            FROM produk
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $productStats = $result->fetch_assoc();
        
        $stats['products'] = $productStats;

        return $stats;
    }

    public function getPengepulStats($userId) {
        $stats = [];

        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total_petani 
            FROM petani 
            WHERE created_by = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['petani'] = $result->fetch_assoc();

        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_batch,
                SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as batch_selesai,
                SUM(jumlah_kg) as total_kg
            FROM batch_produksi bp
            JOIN petani p ON bp.petani_id = p.id
            WHERE p.created_by = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['batch'] = $result->fetch_assoc();

        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total_products,
            SUM(stok) as total_stok
            FROM produk 
            WHERE penjual_id = ? AND kategori_id = 1
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['products'] = $result->fetch_assoc();

        return $stats;
    }

    public function getRoastingStats($userId) {
        $stats = [];

        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total_products,
            SUM(stok) as total_stok
            FROM produk 
            WHERE penjual_id = ? AND kategori_id = 2
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['products'] = $result->fetch_assoc();

        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT p.id) as total_orders,
                SUM(dp.subtotal) as total_revenue
            FROM pesanan p
            JOIN detail_pesanan dp ON p.id = dp.pesanan_id
            JOIN produk pr ON dp.produk_id = pr.id
            WHERE pr.penjual_id = ? AND p.status_pesanan = 'delivered'
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['sales'] = $result->fetch_assoc();

        return $stats;
    }

    public function getPenjualStats($userId) {
        $stats = [];

        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total_products,
            SUM(stok) as total_stok
            FROM produk 
            WHERE penjual_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['products'] = $result->fetch_assoc();

        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT p.id) as total_orders,
                SUM(dp.subtotal) as total_revenue,
                COUNT(DISTINCT p.user_id) as total_customers
            FROM pesanan p
            JOIN detail_pesanan dp ON p.id = dp.pesanan_id
            JOIN produk pr ON dp.produk_id = pr.id
            WHERE pr.penjual_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['sales'] = $result->fetch_assoc();

        $stmt = $this->db->prepare("
            SELECT 
                pr.nama_produk,
                SUM(dp.jumlah) as total_sold
            FROM detail_pesanan dp
            JOIN produk pr ON dp.produk_id = pr.id
            JOIN pesanan p ON dp.pesanan_id = p.id
            WHERE pr.penjual_id = ? AND p.status_pesanan = 'delivered'
            GROUP BY pr.id
            ORDER BY total_sold DESC
            LIMIT 5
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats['top_products'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['top_products'][] = $row;
        }

        return $stats;
    }

    public function getPembeliStats($userId) {
        $stats = [];

        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(total) as total_spent,
                COUNT(CASE WHEN status_pesanan = 'pending' THEN 1 END) as pending_orders
            FROM pesanan 
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['orders'] = $result->fetch_assoc();

        $stmt = $this->db->prepare("
            SELECT COUNT(*) as items_in_cart
            FROM keranjang 
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['cart'] = $result->fetch_assoc();

        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total_reviews
            FROM ulasan 
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['reviews'] = $result->fetch_assoc();

        return $stats;
    }

    public function getRecentOrders($userId, $role, $limit = 5) {
        if ($role === 'admin') {
            $stmt = $this->db->prepare("
                SELECT p.*, u.nama_lengkap as customer_name
                FROM pesanan p
                JOIN users u ON p.user_id = u.id
                ORDER BY p.created_at DESC
                LIMIT ?
            ");
            $stmt->bind_param("i", $limit);
        } elseif ($role === 'pembeli') {
            $stmt = $this->db->prepare("
                SELECT * FROM pesanan 
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->bind_param("ii", $userId, $limit);
        } else {
            $stmt = $this->db->prepare("
                SELECT p.*, u.nama_lengkap as customer_name
                FROM pesanan p
                JOIN users u ON p.user_id = u.id
                JOIN detail_pesanan dp ON p.id = dp.pesanan_id
                JOIN produk pr ON dp.produk_id = pr.id
                WHERE pr.penjual_id = ?
                GROUP BY p.id
                ORDER BY p.created_at DESC
                LIMIT ?
            ");
            $stmt->bind_param("ii", $userId, $limit);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        
        return $orders;
    }
}