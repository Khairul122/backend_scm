<?php
class PesananModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAllPesanan() {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nama_lengkap as customer_name, u.email,
                ap.nama_penerima, ap.alamat_lengkap, ap.no_telepon,
                k.nama as kurir_nama
            FROM pesanan p
            JOIN users u ON p.user_id = u.id
            JOIN alamat_pengiriman ap ON p.alamat_pengiriman_id = ap.id
            JOIN kurir k ON p.kurir_kode = k.kode
            ORDER BY p.created_at DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPesananById($id) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nama_lengkap as customer_name, u.email,
                ap.nama_penerima, ap.alamat_lengkap, ap.no_telepon,
                k.nama as kurir_nama
            FROM pesanan p
            JOIN users u ON p.user_id = u.id
            JOIN alamat_pengiriman ap ON p.alamat_pengiriman_id = ap.id
            JOIN kurir k ON p.kurir_kode = k.kode
            WHERE p.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getPesananByStatus($status) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nama_lengkap as customer_name, u.email
            FROM pesanan p
            JOIN users u ON p.user_id = u.id
            WHERE p.status_pesanan = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPesananByUser($userId) {
        $stmt = $this->db->prepare("
            SELECT p.*, ap.nama_penerima, k.nama as kurir_nama
            FROM pesanan p
            JOIN alamat_pengiriman ap ON p.alamat_pengiriman_id = ap.id
            JOIN kurir k ON p.kurir_kode = k.kode
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function createPesanan($data) {
        $stmt = $this->db->prepare("
            INSERT INTO pesanan (user_id, alamat_pengiriman_id, subtotal, ongkir, total, metode_pembayaran, kurir_kode, kurir_service, estimasi_sampai, berat_total, bukti_pembayaran, catatan, status_pesanan) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $status = $data['status_pesanan'] ?? 'pending';
        
        $stmt->bind_param(
            "iidddssssdss",
            $data['user_id'],
            $data['alamat_pengiriman_id'],
            $data['subtotal'],
            $data['ongkir'],
            $data['total'],
            $data['metode_pembayaran'],
            $data['kurir_kode'],
            $data['kurir_service'],
            $data['estimasi_sampai'],
            $data['berat_total'],
            $data['bukti_pembayaran'],
            $data['catatan'],
            $status
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function updatePesanan($id, $data) {
        $fields = [];
        $types = "";
        $values = [];

        if (isset($data['subtotal'])) {
            $fields[] = "subtotal = ?";
            $types .= "d";
            $values[] = $data['subtotal'];
        }

        if (isset($data['ongkir'])) {
            $fields[] = "ongkir = ?";
            $types .= "d";
            $values[] = $data['ongkir'];
        }

        if (isset($data['total'])) {
            $fields[] = "total = ?";
            $types .= "d";
            $values[] = $data['total'];
        }

        if (isset($data['status_pesanan'])) {
            $fields[] = "status_pesanan = ?";
            $types .= "s";
            $values[] = $data['status_pesanan'];
        }

        if (isset($data['resi_pengiriman'])) {
            $fields[] = "resi_pengiriman = ?";
            $types .= "s";
            $values[] = $data['resi_pengiriman'];
        }

        if (isset($data['bukti_pembayaran'])) {
            $fields[] = "bukti_pembayaran = ?";
            $types .= "s";
            $values[] = $data['bukti_pembayaran'];
        }

        if (isset($data['catatan'])) {
            $fields[] = "catatan = ?";
            $types .= "s";
            $values[] = $data['catatan'];
        }

        if (empty($fields)) {
            return false;
        }

        $types .= "i";
        $values[] = $id;

        $sql = "UPDATE pesanan SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    public function deletePesanan($id) {
        $stmt = $this->db->prepare("DELETE FROM pesanan WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function updatePesananStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE pesanan SET status_pesanan = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    public function getDetailPesanan($pesananId) {
        $stmt = $this->db->prepare("
            SELECT * FROM detail_pesanan 
            WHERE pesanan_id = ?
            ORDER BY id ASC
        ");
        $stmt->bind_param("i", $pesananId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function createDetailPesanan($data) {
        $stmt = $this->db->prepare("
            INSERT INTO detail_pesanan (pesanan_id, produk_id, nama_produk, harga, jumlah, subtotal) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "iisdid",
            $data['pesanan_id'],
            $data['produk_id'],
            $data['nama_produk'],
            $data['harga'],
            $data['jumlah'],
            $data['subtotal']
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function updateDetailPesanan($id, $data) {
        $fields = [];
        $types = "";
        $values = [];

        if (isset($data['jumlah'])) {
            $fields[] = "jumlah = ?";
            $types .= "i";
            $values[] = $data['jumlah'];
        }

        if (isset($data['harga'])) {
            $fields[] = "harga = ?";
            $types .= "d";
            $values[] = $data['harga'];
        }

        if (isset($data['subtotal'])) {
            $fields[] = "subtotal = ?";
            $types .= "d";
            $values[] = $data['subtotal'];
        }

        if (empty($fields)) {
            return false;
        }

        $types .= "i";
        $values[] = $id;

        $sql = "UPDATE detail_pesanan SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    public function deleteDetailPesanan($id) {
        $stmt = $this->db->prepare("DELETE FROM detail_pesanan WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getPesananStats() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status_pesanan = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status_pesanan = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
                SUM(CASE WHEN status_pesanan = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                SUM(CASE WHEN status_pesanan = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(total) as total_revenue,
                AVG(total) as avg_order_value
            FROM pesanan
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getFraudPatterns() {
        $stmt = $this->db->prepare("
            SELECT 
                p.user_id,
                u.nama_lengkap,
                u.email,
                COUNT(*) as total_orders,
                SUM(CASE WHEN p.status_pesanan = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                ROUND((SUM(CASE WHEN p.status_pesanan = 'cancelled' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as cancel_rate,
                SUM(p.total) as total_spent,
                MAX(p.created_at) as last_order
            FROM pesanan p
            JOIN users u ON p.user_id = u.id
            GROUP BY p.user_id
            HAVING total_orders > 1 AND cancel_rate > 50
            ORDER BY cancel_rate DESC, total_orders DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getSuspiciousOrders() {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nama_lengkap, u.email
            FROM pesanan p
            JOIN users u ON p.user_id = u.id
            WHERE p.total > (SELECT AVG(total) * 3 FROM pesanan)
            OR p.user_id IN (
                SELECT user_id FROM pesanan 
                GROUP BY user_id 
                HAVING COUNT(*) > 10 AND MAX(created_at) >= DATE_SUB(NOW(), INTERVAL 1 DAY)
            )
            ORDER BY p.total DESC, p.created_at DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPurchasingPatterns() {
        $stmt = $this->db->prepare("
            SELECT 
                dp.nama_produk,
                COUNT(*) as frequency,
                SUM(dp.jumlah) as total_quantity,
                AVG(dp.harga) as avg_price,
                SUM(dp.subtotal) as total_revenue
            FROM detail_pesanan dp
            JOIN pesanan p ON dp.pesanan_id = p.id
            WHERE p.status_pesanan != 'cancelled'
            GROUP BY dp.nama_produk
            ORDER BY frequency DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRevenueAnalysis($days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as orders_count,
                SUM(total) as daily_revenue,
                AVG(total) as avg_order_value
            FROM pesanan
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            AND status_pesanan != 'cancelled'
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stmt->bind_param("i", $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function searchPesanan($query) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT p.*, u.nama_lengkap as customer_name, u.email
            FROM pesanan p
            JOIN users u ON p.user_id = u.id
            WHERE p.kode_pesanan LIKE ? OR u.nama_lengkap LIKE ? OR u.email LIKE ?
            ORDER BY p.created_at DESC
            LIMIT 50
        ");
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function bulkUpdateStatus($ids, $status) {
        if (empty($ids)) return false;
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "UPDATE pesanan SET status_pesanan = ? WHERE id IN ($placeholders)";
        
        $stmt = $this->db->prepare($sql);
        $types = str_repeat('i', count($ids));
        $stmt->bind_param('s' . $types, $status, ...$ids);
        
        return $stmt->execute();
    }

    public function getDisputeOrders() {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nama_lengkap, u.email, r.alasan as dispute_reason
            FROM pesanan p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN retur r ON p.id = r.pesanan_id
            WHERE r.status_retur = 'pending'
            OR p.status_pesanan = 'cancelled'
            ORDER BY p.created_at DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getOrdersByDateRange($startDate, $endDate) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nama_lengkap as customer_name
            FROM pesanan p
            JOIN users u ON p.user_id = u.id
            WHERE DATE(p.created_at) BETWEEN ? AND ?
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getHighValueOrders($threshold = 1000000) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nama_lengkap, u.email
            FROM pesanan p
            JOIN users u ON p.user_id = u.id
            WHERE p.total >= ?
            ORDER BY p.total DESC
        ");
        $stmt->bind_param("d", $threshold);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}