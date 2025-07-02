<?php
class KurirModel {
    private $db;
    private $apiKey = 'c991b8daf3069a09ca3d0f52b7fcd3c8';
    private $baseUrl = 'https://api.rajaongkir.com/starter/';

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAllKurir() {
        $stmt = $this->db->prepare("SELECT * FROM kurir ORDER BY nama ASC");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getKurirById($id) {
        $stmt = $this->db->prepare("SELECT * FROM kurir WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getKurirByKode($kode) {
        $stmt = $this->db->prepare("SELECT * FROM kurir WHERE kode = ?");
        $stmt->bind_param("s", $kode);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getActiveKurir() {
        $stmt = $this->db->prepare("SELECT * FROM kurir WHERE status = 'aktif' ORDER BY nama ASC");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function createKurir($data) {
        $stmt = $this->db->prepare("
            INSERT INTO kurir (kode, nama, status) 
            VALUES (?, ?, ?)
        ");
        
        $status = $data['status'] ?? 'aktif';
        
        $stmt->bind_param(
            "sss",
            $data['kode'],
            $data['nama'],
            $status
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function updateKurir($id, $data) {
        $fields = [];
        $types = "";
        $values = [];

        if (isset($data['kode'])) {
            $fields[] = "kode = ?";
            $types .= "s";
            $values[] = $data['kode'];
        }

        if (isset($data['nama'])) {
            $fields[] = "nama = ?";
            $types .= "s";
            $values[] = $data['nama'];
        }

        if (isset($data['status'])) {
            $fields[] = "status = ?";
            $types .= "s";
            $values[] = $data['status'];
        }

        if (empty($fields)) {
            return false;
        }

        $types .= "i";
        $values[] = $id;

        $sql = "UPDATE kurir SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    public function deleteKurir($id) {
        $stmt = $this->db->prepare("DELETE FROM kurir WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function updateKurirStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE kurir SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    public function getKurirPerformance() {
        $stmt = $this->db->prepare("
            SELECT 
                k.id,
                k.kode,
                k.nama,
                k.status,
                COUNT(p.id) as total_orders,
                SUM(CASE WHEN p.status_pesanan = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                SUM(CASE WHEN p.status_pesanan = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                ROUND((SUM(CASE WHEN p.status_pesanan = 'delivered' THEN 1 ELSE 0 END) / COUNT(p.id)) * 100, 2) as success_rate,
                AVG(p.ongkir) as avg_shipping_cost,
                COUNT(CASE WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_orders
            FROM kurir k
            LEFT JOIN pesanan p ON k.kode = p.kurir_kode
            GROUP BY k.id, k.kode, k.nama, k.status
            ORDER BY success_rate DESC, total_orders DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getKurirStats() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_kurir,
                SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as active_kurir,
                SUM(CASE WHEN status = 'nonaktif' THEN 1 ELSE 0 END) as inactive_kurir
            FROM kurir
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getKurirDeliveryTime() {
        $stmt = $this->db->prepare("
            SELECT 
                k.kode,
                k.nama,
                COUNT(p.id) as total_delivered,
                AVG(DATEDIFF(
                    STR_TO_DATE(CONCAT(p.created_at, ' + ', SUBSTRING_INDEX(p.estimasi_sampai, ' ', 1), ' days'), '%Y-%m-%d %H:%i:%s + %d days'),
                    p.created_at
                )) as avg_delivery_days,
                p.estimasi_sampai as common_estimate
            FROM kurir k
            LEFT JOIN pesanan p ON k.kode = p.kurir_kode
            WHERE p.status_pesanan = 'delivered'
            GROUP BY k.kode, k.nama, p.estimasi_sampai
            ORDER BY avg_delivery_days ASC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getKurirCostAnalysis() {
        $stmt = $this->db->prepare("
            SELECT 
                k.kode,
                k.nama,
                COUNT(p.id) as total_shipments,
                MIN(p.ongkir) as min_cost,
                MAX(p.ongkir) as max_cost,
                AVG(p.ongkir) as avg_cost,
                SUM(p.ongkir) as total_revenue
            FROM kurir k
            LEFT JOIN pesanan p ON k.kode = p.kurir_kode
            WHERE p.ongkir > 0
            GROUP BY k.kode, k.nama
            ORDER BY avg_cost ASC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getKurirFromApi() {
        $supportedCouriers = ['jne', 'pos', 'tiki'];
        
        $couriers = [];
        foreach ($supportedCouriers as $code) {
            $couriers[] = [
                'kode' => $code,
                'nama' => strtoupper($code),
                'status' => 'aktif'
            ];
        }
        
        return $couriers;
    }

    public function importKurirFromApi() {
        $apiCouriers = $this->getKurirFromApi();
        $imported = 0;
        
        foreach ($apiCouriers as $courier) {
            $stmt = $this->db->prepare("
                INSERT INTO kurir (kode, nama, status) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                    nama = VALUES(nama),
                    status = VALUES(status)
            ");
            
            $stmt->bind_param(
                "sss",
                $courier['kode'],
                $courier['nama'],
                $courier['status']
            );
            
            if ($stmt->execute()) {
                $imported++;
            }
        }
        
        return $imported;
    }

    public function searchKurir($query) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT * FROM kurir 
            WHERE kode LIKE ? OR nama LIKE ?
            ORDER BY nama ASC
        ");
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPoorPerformingKurir($threshold = 70) {
        $stmt = $this->db->prepare("
            SELECT 
                k.id,
                k.kode,
                k.nama,
                k.status,
                COUNT(p.id) as total_orders,
                SUM(CASE WHEN p.status_pesanan = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                SUM(CASE WHEN p.status_pesanan = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                ROUND((SUM(CASE WHEN p.status_pesanan = 'delivered' THEN 1 ELSE 0 END) / COUNT(p.id)) * 100, 2) as success_rate
            FROM kurir k
            LEFT JOIN pesanan p ON k.kode = p.kurir_kode
            GROUP BY k.id, k.kode, k.nama, k.status
            HAVING total_orders > 0 AND success_rate < ?
            ORDER BY success_rate ASC
        ");
        $stmt->bind_param("d", $threshold);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getKurirTrends($days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(p.created_at) as date,
                k.kode,
                k.nama,
                COUNT(p.id) as daily_orders,
                SUM(CASE WHEN p.status_pesanan = 'delivered' THEN 1 ELSE 0 END) as daily_delivered
            FROM kurir k
            LEFT JOIN pesanan p ON k.kode = p.kurir_kode
            WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(p.created_at), k.kode, k.nama
            ORDER BY date DESC, daily_orders DESC
        ");
        $stmt->bind_param("i", $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function validateKurirCode($kode) {
        $supportedCouriers = ['jne', 'pos', 'tiki'];
        return in_array(strtolower($kode), $supportedCouriers);
    }

    public function checkKurirExists($kode, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT id FROM kurir WHERE kode = ? AND id != ?");
            $stmt->bind_param("si", $kode, $excludeId);
        } else {
            $stmt = $this->db->prepare("SELECT id FROM kurir WHERE kode = ?");
            $stmt->bind_param("s", $kode);
        }
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function getKurirUsageStats() {
        $stmt = $this->db->prepare("
            SELECT 
                k.kode,
                k.nama,
                k.status,
                COUNT(p.id) as usage_count,
                ROUND((COUNT(p.id) / (SELECT COUNT(*) FROM pesanan)) * 100, 2) as usage_percentage,
                MAX(p.created_at) as last_used
            FROM kurir k
            LEFT JOIN pesanan p ON k.kode = p.kurir_kode
            GROUP BY k.id, k.kode, k.nama, k.status
            ORDER BY usage_count DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}