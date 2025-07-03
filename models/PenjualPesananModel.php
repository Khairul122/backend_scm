<?php
class PenjualPesananModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getOrdersForSeller($penjualId, $status = null) {
        $whereClause = "WHERE EXISTS (
            SELECT 1 FROM detail_pesanan dp 
            JOIN produk p ON dp.produk_id = p.id 
            WHERE dp.pesanan_id = ps.id AND p.penjual_id = ?
        )";
        $params = [$penjualId];
        $types = "i";

        if ($status) {
            $whereClause .= " AND ps.status_pesanan = ?";
            $params[] = $status;
            $types .= "s";
        }

        $stmt = $this->db->prepare("
            SELECT DISTINCT
                ps.*,
                ap.nama_penerima,
                ap.no_telepon,
                ap.alamat_lengkap,
                ap.kode_pos,
                prov.province as province_name,
                kota.city_name,
                customer.nama_lengkap as customer_name,
                customer.email as customer_email,
                kurir.nama as kurir_name,
                COUNT(dp.id) as total_items,
                SUM(CASE WHEN p.penjual_id = ? THEN dp.jumlah ELSE 0 END) as seller_items,
                SUM(CASE WHEN p.penjual_id = ? THEN dp.subtotal ELSE 0 END) as seller_subtotal
            FROM pesanan ps
            JOIN alamat_pengiriman ap ON ps.alamat_pengiriman_id = ap.id
            JOIN provinsi prov ON ap.province_id = prov.province_id
            JOIN kota ON ap.city_id = kota.city_id
            JOIN users customer ON ps.user_id = customer.id
            JOIN kurir ON ps.kurir_kode = kurir.kode
            LEFT JOIN detail_pesanan dp ON ps.id = dp.pesanan_id
            LEFT JOIN produk p ON dp.produk_id = p.id
            $whereClause
            GROUP BY ps.id, ps.kode_pesanan, ps.user_id, ps.alamat_pengiriman_id, 
                     ps.subtotal, ps.ongkir, ps.total, ps.metode_pembayaran, ps.kurir_kode, 
                     ps.kurir_service, ps.estimasi_sampai, ps.berat_total, ps.bukti_pembayaran, 
                     ps.catatan, ps.status_pesanan, ps.resi_pengiriman, ps.created_at,
                     ap.nama_penerima, ap.no_telepon, ap.alamat_lengkap, ap.kode_pos,
                     prov.province, kota.city_name, customer.nama_lengkap, customer.email, kurir.nama
            ORDER BY ps.created_at DESC
        ");
        
        $allParams = array_merge($params, [$penjualId, $penjualId]);
        $allTypes = $types . "ii";
        $stmt->bind_param($allTypes, ...$allParams);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getOrderById($orderId, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                ps.*,
                ap.nama_penerima,
                ap.no_telepon,
                ap.alamat_lengkap,
                ap.kode_pos,
                prov.province as province_name,
                kota.city_name,
                customer.nama_lengkap as customer_name,
                customer.email as customer_email,
                kurir.nama as kurir_name
            FROM pesanan ps
            JOIN alamat_pengiriman ap ON ps.alamat_pengiriman_id = ap.id
            JOIN provinsi prov ON ap.province_id = prov.province_id
            JOIN kota ON ap.city_id = kota.city_id
            JOIN users customer ON ps.user_id = customer.id
            JOIN kurir ON ps.kurir_kode = kurir.kode
            WHERE ps.id = ? 
            AND EXISTS (
                SELECT 1 FROM detail_pesanan dp 
                JOIN produk p ON dp.produk_id = p.id 
                WHERE dp.pesanan_id = ps.id AND p.penjual_id = ?
            )
        ");
        $stmt->bind_param("ii", $orderId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getOrderDetails($orderId, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                dp.*,
                p.nama_produk,
                p.foto as product_image,
                p.berat as product_weight,
                k.nama_kategori
            FROM detail_pesanan dp
            JOIN produk p ON dp.produk_id = p.id
            JOIN kategori_produk k ON p.kategori_id = k.id
            WHERE dp.pesanan_id = ? AND p.penjual_id = ?
            ORDER BY dp.id
        ");
        $stmt->bind_param("ii", $orderId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function updateOrderStatus($orderId, $newStatus, $penjualId) {
        $allowedStatuses = ['confirmed', 'processed', 'shipped', 'delivered', 'cancelled'];
        
        if (!in_array($newStatus, $allowedStatuses)) {
            return false;
        }

        if (!$this->checkOrderOwnership($orderId, $penjualId)) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE pesanan 
            SET status_pesanan = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("si", $newStatus, $orderId);
        return $stmt->execute();
    }

    public function updateShippingInfo($orderId, $resiPengiriman, $penjualId) {
        if (!$this->checkOrderOwnership($orderId, $penjualId)) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE pesanan 
            SET resi_pengiriman = ?, status_pesanan = 'shipped' 
            WHERE id = ?
        ");
        $stmt->bind_param("si", $resiPengiriman, $orderId);
        return $stmt->execute();
    }

    public function getOrderStatistics($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT ps.id) as total_orders,
                COUNT(CASE WHEN ps.status_pesanan = 'pending' THEN 1 END) as pending_orders,
                COUNT(CASE WHEN ps.status_pesanan = 'confirmed' THEN 1 END) as confirmed_orders,
                COUNT(CASE WHEN ps.status_pesanan = 'processed' THEN 1 END) as processed_orders,
                COUNT(CASE WHEN ps.status_pesanan = 'shipped' THEN 1 END) as shipped_orders,
                COUNT(CASE WHEN ps.status_pesanan = 'delivered' THEN 1 END) as delivered_orders,
                COUNT(CASE WHEN ps.status_pesanan = 'cancelled' THEN 1 END) as cancelled_orders,
                SUM(CASE WHEN p.penjual_id = ? THEN dp.subtotal ELSE 0 END) as total_revenue,
                SUM(CASE WHEN p.penjual_id = ? AND ps.status_pesanan = 'delivered' THEN dp.subtotal ELSE 0 END) as delivered_revenue,
                COUNT(CASE WHEN DATE(ps.created_at) = CURDATE() THEN 1 END) as today_orders,
                COUNT(CASE WHEN ps.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_orders,
                COUNT(CASE WHEN ps.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as month_orders
            FROM pesanan ps
            JOIN detail_pesanan dp ON ps.id = dp.pesanan_id
            JOIN produk p ON dp.produk_id = p.id
            WHERE p.penjual_id = ?
        ");
        $stmt->bind_param("iii", $penjualId, $penjualId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getRecentOrders($penjualId, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT
                ps.id,
                ps.kode_pesanan,
                ps.status_pesanan,
                ps.total,
                ps.created_at,
                customer.nama_lengkap as customer_name,
                COUNT(dp.id) as total_items,
                SUM(CASE WHEN p.penjual_id = ? THEN dp.subtotal ELSE 0 END) as seller_total
            FROM pesanan ps
            JOIN detail_pesanan dp ON ps.id = dp.pesanan_id
            JOIN produk p ON dp.produk_id = p.id
            JOIN users customer ON ps.user_id = customer.id
            WHERE p.penjual_id = ?
            GROUP BY ps.id, ps.kode_pesanan, ps.status_pesanan, ps.total, ps.created_at, customer.nama_lengkap
            ORDER BY ps.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("iii", $penjualId, $penjualId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getTopCustomers($penjualId, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT 
                customer.id,
                customer.nama_lengkap,
                customer.email,
                COUNT(DISTINCT ps.id) as total_orders,
                SUM(CASE WHEN p.penjual_id = ? THEN dp.subtotal ELSE 0 END) as total_spent,
                AVG(CASE WHEN p.penjual_id = ? THEN dp.subtotal ELSE 0 END) as avg_order_value,
                MAX(ps.created_at) as last_order_date,
                COUNT(CASE WHEN ps.status_pesanan = 'delivered' THEN 1 END) as completed_orders
            FROM users customer
            JOIN pesanan ps ON customer.id = ps.user_id
            JOIN detail_pesanan dp ON ps.id = dp.pesanan_id
            JOIN produk p ON dp.produk_id = p.id
            WHERE p.penjual_id = ?
            GROUP BY customer.id, customer.nama_lengkap, customer.email
            ORDER BY total_spent DESC
            LIMIT ?
        ");
        $stmt->bind_param("iiii", $penjualId, $penjualId, $penjualId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getTopSellingProducts($penjualId, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.nama_produk,
                p.harga,
                p.foto,
                k.nama_kategori,
                SUM(dp.jumlah) as total_sold,
                SUM(dp.subtotal) as total_revenue,
                COUNT(DISTINCT ps.id) as order_count,
                AVG(dp.harga) as avg_selling_price
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            JOIN detail_pesanan dp ON p.id = dp.produk_id
            JOIN pesanan ps ON dp.pesanan_id = ps.id
            WHERE p.penjual_id = ? AND ps.status_pesanan = 'delivered'
            GROUP BY p.id, p.nama_produk, p.harga, p.foto, k.nama_kategori
            ORDER BY total_sold DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $penjualId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getOrdersByDateRange($penjualId, $startDate, $endDate) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT
                ps.*,
                customer.nama_lengkap as customer_name,
                SUM(CASE WHEN p.penjual_id = ? THEN dp.subtotal ELSE 0 END) as seller_total
            FROM pesanan ps
            JOIN detail_pesanan dp ON ps.id = dp.pesanan_id
            JOIN produk p ON dp.produk_id = p.id
            JOIN users customer ON ps.user_id = customer.id
            WHERE p.penjual_id = ? 
            AND DATE(ps.created_at) BETWEEN ? AND ?
            GROUP BY ps.id, ps.kode_pesanan, ps.user_id, ps.alamat_pengiriman_id, 
                     ps.subtotal, ps.ongkir, ps.total, ps.metode_pembayaran, ps.kurir_kode, 
                     ps.kurir_service, ps.estimasi_sampai, ps.berat_total, ps.bukti_pembayaran, 
                     ps.catatan, ps.status_pesanan, ps.resi_pengiriman, ps.created_at, customer.nama_lengkap
            ORDER BY ps.created_at DESC
        ");
        $stmt->bind_param("iiss", $penjualId, $penjualId, $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRevenueAnalysis($penjualId, $period = 'month') {
        $dateFormat = $period === 'week' ? '%Y-%u' : '%Y-%m';
        $interval = $period === 'week' ? '12 WEEK' : '12 MONTH';
        
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(ps.created_at, '$dateFormat') as period,
                COUNT(DISTINCT ps.id) as order_count,
                SUM(CASE WHEN p.penjual_id = ? THEN dp.subtotal ELSE 0 END) as revenue,
                SUM(CASE WHEN p.penjual_id = ? THEN dp.jumlah ELSE 0 END) as units_sold,
                COUNT(DISTINCT ps.user_id) as unique_customers,
                AVG(CASE WHEN p.penjual_id = ? THEN dp.subtotal ELSE 0 END) as avg_order_value
            FROM pesanan ps
            JOIN detail_pesanan dp ON ps.id = dp.pesanan_id
            JOIN produk p ON dp.produk_id = p.id
            WHERE p.penjual_id = ? 
            AND ps.created_at >= DATE_SUB(NOW(), INTERVAL $interval)
            AND ps.status_pesanan = 'delivered'
            GROUP BY DATE_FORMAT(ps.created_at, '$dateFormat')
            ORDER BY period DESC
        ");
        $stmt->bind_param("iiii", $penjualId, $penjualId, $penjualId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getOrderTrends($penjualId, $days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(ps.created_at) as order_date,
                COUNT(DISTINCT ps.id) as daily_orders,
                SUM(CASE WHEN p.penjual_id = ? THEN dp.subtotal ELSE 0 END) as daily_revenue,
                COUNT(DISTINCT ps.user_id) as unique_customers,
                AVG(CASE WHEN p.penjual_id = ? THEN dp.subtotal ELSE 0 END) as avg_order_value
            FROM pesanan ps
            JOIN detail_pesanan dp ON ps.id = dp.pesanan_id
            JOIN produk p ON dp.produk_id = p.id
            WHERE p.penjual_id = ? 
            AND ps.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(ps.created_at)
            ORDER BY order_date DESC
        ");
        $stmt->bind_param("iiii", $penjualId, $penjualId, $penjualId, $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPaymentMethodAnalysis($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                ps.metode_pembayaran,
                COUNT(DISTINCT ps.id) as order_count,
                SUM(CASE WHEN p.penjual_id = ? THEN dp.subtotal ELSE 0 END) as total_revenue,
                AVG(CASE WHEN p.penjual_id = ? THEN dp.subtotal ELSE 0 END) as avg_order_value,
                COUNT(CASE WHEN ps.status_pesanan = 'delivered' THEN 1 END) as successful_orders
            FROM pesanan ps
            JOIN detail_pesanan dp ON ps.id = dp.pesanan_id
            JOIN produk p ON dp.produk_id = p.id
            WHERE p.penjual_id = ?
            GROUP BY ps.metode_pembayaran
            ORDER BY total_revenue DESC
        ");
        $stmt->bind_param("iii", $penjualId, $penjualId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getShippingAnalysis($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                ps.kurir_kode,
                kurir.nama as kurir_name,
                ps.kurir_service,
                COUNT(DISTINCT ps.id) as order_count,
                SUM(ps.ongkir) as total_shipping_cost,
                AVG(ps.ongkir) as avg_shipping_cost,
                COUNT(CASE WHEN ps.status_pesanan = 'delivered' THEN 1 END) as delivered_count
            FROM pesanan ps
            JOIN kurir ON ps.kurir_kode = kurir.kode
            JOIN detail_pesanan dp ON ps.id = dp.pesanan_id
            JOIN produk p ON dp.produk_id = p.id
            WHERE p.penjual_id = ?
            GROUP BY ps.kurir_kode, kurir.nama, ps.kurir_service
            ORDER BY order_count DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function searchOrders($query, $penjualId) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT DISTINCT
                ps.id,
                ps.kode_pesanan,
                ps.status_pesanan,
                ps.total,
                ps.created_at,
                customer.nama_lengkap as customer_name,
                customer.email as customer_email
            FROM pesanan ps
            JOIN detail_pesanan dp ON ps.id = dp.pesanan_id
            JOIN produk p ON dp.produk_id = p.id
            JOIN users customer ON ps.user_id = customer.id
            WHERE p.penjual_id = ? 
            AND (ps.kode_pesanan LIKE ? OR customer.nama_lengkap LIKE ? OR customer.email LIKE ?)
            ORDER BY ps.created_at DESC
            LIMIT 50
        ");
        $stmt->bind_param("isss", $penjualId, $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getOrderAlerts($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                'PENDING_CONFIRMATION' as alert_type,
                ps.kode_pesanan,
                ps.id as order_id,
                CONCAT('Pesanan ', ps.kode_pesanan, ' menunggu konfirmasi sejak ', DATEDIFF(NOW(), ps.created_at), ' hari') as message,
                ps.created_at
            FROM pesanan ps
            JOIN detail_pesanan dp ON ps.id = dp.pesanan_id
            JOIN produk p ON dp.produk_id = p.id
            WHERE p.penjual_id = ? 
            AND ps.status_pesanan = 'pending'
            AND DATEDIFF(NOW(), ps.created_at) >= 1
            
            UNION ALL
            
            SELECT 
                'PROCESSING_DELAY' as alert_type,
                ps.kode_pesanan,
                ps.id as order_id,
                CONCAT('Pesanan ', ps.kode_pesanan, ' dalam proses sejak ', DATEDIFF(NOW(), ps.created_at), ' hari') as message,
                ps.created_at
            FROM pesanan ps
            JOIN detail_pesanan dp ON ps.id = dp.pesanan_id
            JOIN produk p ON dp.produk_id = p.id
            WHERE p.penjual_id = ? 
            AND ps.status_pesanan IN ('confirmed', 'processed')
            AND DATEDIFF(NOW(), ps.created_at) >= 3
            
            ORDER BY created_at ASC
        ");
        $stmt->bind_param("ii", $penjualId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function checkOrderOwnership($orderId, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT 1 
            FROM pesanan ps
            JOIN detail_pesanan dp ON ps.id = dp.pesanan_id
            JOIN produk p ON dp.produk_id = p.id
            WHERE ps.id = ? AND p.penjual_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $orderId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
}