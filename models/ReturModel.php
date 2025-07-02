<?php
class ReturModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAllRetur() {
        $stmt = $this->db->prepare("
            SELECT r.*, p.kode_pesanan, u.nama_lengkap as customer_name, u.email
            FROM retur r
            JOIN pesanan p ON r.pesanan_id = p.id
            JOIN users u ON r.user_id = u.id
            ORDER BY r.created_at DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getReturById($id) {
        $stmt = $this->db->prepare("
            SELECT r.*, p.kode_pesanan, u.nama_lengkap as customer_name, u.email
            FROM retur r
            JOIN pesanan p ON r.pesanan_id = p.id
            JOIN users u ON r.user_id = u.id
            WHERE r.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getReturByStatus($status) {
        $stmt = $this->db->prepare("
            SELECT r.*, p.kode_pesanan, u.nama_lengkap as customer_name, u.email
            FROM retur r
            JOIN pesanan p ON r.pesanan_id = p.id
            JOIN users u ON r.user_id = u.id
            WHERE r.status_retur = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getReturByUser($userId) {
        $stmt = $this->db->prepare("
            SELECT r.*, p.kode_pesanan
            FROM retur r
            JOIN pesanan p ON r.pesanan_id = p.id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function createRetur($data) {
        $stmt = $this->db->prepare("
            INSERT INTO retur (pesanan_id, user_id, alasan, foto_bukti, status_retur) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $status = $data['status_retur'] ?? 'pending';
        
        $stmt->bind_param(
            "iisss",
            $data['pesanan_id'],
            $data['user_id'],
            $data['alasan'],
            $data['foto_bukti'],
            $status
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function updateRetur($id, $data) {
        $fields = [];
        $types = "";
        $values = [];

        if (isset($data['alasan'])) {
            $fields[] = "alasan = ?";
            $types .= "s";
            $values[] = $data['alasan'];
        }

        if (isset($data['foto_bukti'])) {
            $fields[] = "foto_bukti = ?";
            $types .= "s";
            $values[] = $data['foto_bukti'];
        }

        if (isset($data['status_retur'])) {
            $fields[] = "status_retur = ?";
            $types .= "s";
            $values[] = $data['status_retur'];
        }

        if (empty($fields)) {
            return false;
        }

        $types .= "i";
        $values[] = $id;

        $sql = "UPDATE retur SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    public function deleteRetur($id) {
        $stmt = $this->db->prepare("DELETE FROM retur WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getReturStats() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_retur,
                SUM(CASE WHEN status_retur = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status_retur = 'approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status_retur = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                SUM(CASE WHEN status_retur = 'completed' THEN 1 ELSE 0 END) as completed_count,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_retur
            FROM retur
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getReturByProduk() {
        $stmt = $this->db->prepare("
            SELECT 
                dp.nama_produk,
                COUNT(r.id) as retur_count,
                SUM(CASE WHEN r.status_retur = 'approved' THEN 1 ELSE 0 END) as approved_count,
                GROUP_CONCAT(DISTINCT r.alasan SEPARATOR '; ') as common_reasons
            FROM retur r
            JOIN pesanan p ON r.pesanan_id = p.id
            JOIN detail_pesanan dp ON p.id = dp.pesanan_id
            GROUP BY dp.nama_produk
            ORDER BY retur_count DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getReturCompliance() {
        $stmt = $this->db->prepare("
            SELECT 
                MONTH(r.created_at) as month,
                YEAR(r.created_at) as year,
                COUNT(*) as total_requests,
                SUM(CASE WHEN r.status_retur IN ('approved', 'completed') THEN 1 ELSE 0 END) as compliant_requests,
                ROUND((SUM(CASE WHEN r.status_retur IN ('approved', 'completed') THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as compliance_rate
            FROM retur r
            WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY YEAR(r.created_at), MONTH(r.created_at)
            ORDER BY year DESC, month DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getReturPolicy() {
        $stmt = $this->db->prepare("
            SELECT 
                'general' as policy_type,
                '7 hari setelah pembelian' as batas_waktu,
                'Barang dalam kondisi baik dan kemasan asli' as syarat_kondisi,
                'Semua kategori produk' as applicable_products,
                'aktif' as status
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function searchRetur($query) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT r.*, p.kode_pesanan, u.nama_lengkap as customer_name, u.email
            FROM retur r
            JOIN pesanan p ON r.pesanan_id = p.id
            JOIN users u ON r.user_id = u.id
            WHERE r.alasan LIKE ? OR p.kode_pesanan LIKE ? OR u.nama_lengkap LIKE ?
            ORDER BY r.created_at DESC
            LIMIT 50
        ");
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getReturTrends($days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_requests,
                SUM(CASE WHEN status_retur = 'approved' THEN 1 ELSE 0 END) as approved_requests
            FROM retur
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stmt->bind_param("i", $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getReturReasons() {
        $stmt = $this->db->prepare("
            SELECT 
                alasan,
                COUNT(*) as frequency,
                ROUND((COUNT(*) / (SELECT COUNT(*) FROM retur)) * 100, 2) as percentage
            FROM retur
            GROUP BY alasan
            ORDER BY frequency DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function validateReturRequest($pesananId, $userId) {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.status_pesanan,
                p.created_at,
                DATEDIFF(NOW(), p.created_at) as days_since_order,
                COUNT(r.id) as existing_retur
            FROM pesanan p
            LEFT JOIN retur r ON p.id = r.pesanan_id AND r.status_retur IN ('pending', 'approved')
            WHERE p.id = ? AND p.user_id = ?
            GROUP BY p.id
        ");
        $stmt->bind_param("ii", $pesananId, $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getOutdatedRetur($days = 30) {
        $stmt = $this->db->prepare("
            SELECT r.*, p.kode_pesanan, u.nama_lengkap as customer_name
            FROM retur r
            JOIN pesanan p ON r.pesanan_id = p.id
            JOIN users u ON r.user_id = u.id
            WHERE r.status_retur = 'pending' 
            AND r.created_at <= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY r.created_at ASC
        ");
        $stmt->bind_param("i", $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function bulkUpdateStatus($ids, $status) {
        if (empty($ids)) return false;
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "UPDATE retur SET status_retur = ? WHERE id IN ($placeholders)";
        
        $stmt = $this->db->prepare($sql);
        $types = str_repeat('i', count($ids));
        $stmt->bind_param('s' . $types, $status, ...$ids);
        
        return $stmt->execute();
    }
}