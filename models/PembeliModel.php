<?php
class PembeliModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function register($data) {
        $stmt = $this->db->prepare("
            SELECT id FROM users WHERE email = ? OR no_telepon = ?
        ");
        $stmt->bind_param("ss", $data['email'], $data['no_telepon']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            return false;
        }

        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        
        $stmt = $this->db->prepare("
            INSERT INTO users (nama_lengkap, email, no_telepon, alamat, password, role, status) 
            VALUES (?, ?, ?, ?, ?, 'pembeli', 'aktif')
        ");
        
        $stmt->bind_param(
            "sssss",
            $data['nama_lengkap'],
            $data['email'],
            $data['no_telepon'],
            $data['alamat'],
            $hashedPassword
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function getProfile($userId) {
        $stmt = $this->db->prepare("
            SELECT 
                id,
                nama_lengkap,
                email,
                no_telepon,
                alamat,
                role,
                status,
                created_at
            FROM users 
            WHERE id = ? AND role = 'pembeli'
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function updateProfile($userId, $data) {
        $fields = [];
        $types = "";
        $values = [];

        $allowedFields = ['nama_lengkap', 'email', 'no_telepon', 'alamat'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                if ($field === 'email' || $field === 'no_telepon') {
                    if ($this->checkUniqueField($field, $data[$field], $userId)) {
                        $fields[] = "$field = ?";
                        $types .= "s";
                        $values[] = $data[$field];
                    } else {
                        return false;
                    }
                } else {
                    $fields[] = "$field = ?";
                    $types .= "s";
                    $values[] = $data[$field];
                }
            }
        }

        if (empty($fields)) {
            return false;
        }

        $types .= "i";
        $values[] = $userId;

        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ? AND role = 'pembeli'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    public function changePassword($userId, $currentPassword, $newPassword) {
        $stmt = $this->db->prepare("
            SELECT password FROM users WHERE id = ? AND role = 'pembeli'
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!$result || !password_verify($currentPassword, $result['password'])) {
            return false;
        }

        $hashedNewPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        
        $stmt = $this->db->prepare("
            UPDATE users SET password = ? WHERE id = ? AND role = 'pembeli'
        ");
        $stmt->bind_param("si", $hashedNewPassword, $userId);
        
        return $stmt->execute();
    }

    public function requestAccountDeactivation($userId, $reason) {
        $this->db->begin_transaction();
        
        try {
            $stmt = $this->db->prepare("
                UPDATE users SET status = 'nonaktif' WHERE id = ? AND role = 'pembeli'
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            
            $stmt = $this->db->prepare("
                INSERT INTO account_deactivation_requests (user_id, reason, status, created_at) 
                VALUES (?, ?, 'pending', NOW())
            ");
            $stmt->bind_param("is", $userId, $reason);
            $stmt->execute();
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function getOrderHistory($userId, $limit = 10, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.kode_pesanan,
                p.subtotal,
                p.ongkir,
                p.total,
                p.metode_pembayaran,
                p.status_pesanan,
                p.created_at,
                ap.nama_penerima,
                ap.alamat_lengkap,
                kurir.nama as kurir_name,
                p.kurir_service,
                p.resi_pengiriman,
                COUNT(dp.id) as total_items
            FROM pesanan p
            LEFT JOIN alamat_pengiriman ap ON p.alamat_pengiriman_id = ap.id
            LEFT JOIN kurir ON p.kurir_kode = kurir.kode
            LEFT JOIN detail_pesanan dp ON p.id = dp.pesanan_id
            WHERE p.user_id = ?
            GROUP BY p.id, p.kode_pesanan, p.subtotal, p.ongkir, p.total, p.metode_pembayaran, 
                     p.status_pesanan, p.created_at, ap.nama_penerima, ap.alamat_lengkap, 
                     kurir.nama, p.kurir_service, p.resi_pengiriman
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("iii", $userId, $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getShippingAddresses($userId) {
        $stmt = $this->db->prepare("
            SELECT 
                ap.*,
                prov.province as province_name,
                kota.city_name
            FROM alamat_pengiriman ap
            JOIN provinsi prov ON ap.province_id = prov.province_id
            JOIN kota ON ap.city_id = kota.city_id
            WHERE ap.user_id = ?
            ORDER BY ap.is_default DESC, ap.created_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function addShippingAddress($userId, $data) {
        if (isset($data['is_default']) && $data['is_default']) {
            $this->removeDefaultAddress($userId);
        }

        $stmt = $this->db->prepare("
            INSERT INTO alamat_pengiriman 
            (user_id, label, nama_penerima, no_telepon, province_id, city_id, alamat_lengkap, kode_pos, is_default) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $isDefault = isset($data['is_default']) ? $data['is_default'] : 0;
        
        $stmt->bind_param(
            "isssiissi",
            $userId,
            $data['label'],
            $data['nama_penerima'],
            $data['no_telepon'],
            $data['province_id'],
            $data['city_id'],
            $data['alamat_lengkap'],
            $data['kode_pos'],
            $isDefault
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function updateShippingAddress($userId, $addressId, $data) {
        if (!$this->checkAddressOwnership($addressId, $userId)) {
            return false;
        }

        if (isset($data['is_default']) && $data['is_default']) {
            $this->removeDefaultAddress($userId);
        }

        $fields = [];
        $types = "";
        $values = [];

        $allowedFields = ['label', 'nama_penerima', 'no_telepon', 'province_id', 'city_id', 'alamat_lengkap', 'kode_pos', 'is_default'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                if (in_array($field, ['province_id', 'city_id', 'is_default'])) {
                    $types .= "i";
                } else {
                    $types .= "s";
                }
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $types .= "ii";
        $values[] = $addressId;
        $values[] = $userId;

        $sql = "UPDATE alamat_pengiriman SET " . implode(", ", $fields) . " WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    public function deleteShippingAddress($userId, $addressId) {
        if (!$this->checkAddressOwnership($addressId, $userId)) {
            return false;
        }

        $stmt = $this->db->prepare("
            DELETE FROM alamat_pengiriman WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $addressId, $userId);
        return $stmt->execute();
    }

    public function setDefaultAddress($userId, $addressId) {
        if (!$this->checkAddressOwnership($addressId, $userId)) {
            return false;
        }

        $this->db->begin_transaction();
        
        try {
            $this->removeDefaultAddress($userId);
            
            $stmt = $this->db->prepare("
                UPDATE alamat_pengiriman SET is_default = 1 WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("ii", $addressId, $userId);
            $stmt->execute();
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function getWishlist($userId, $limit = 20, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.nama_produk,
                p.harga,
                p.foto,
                p.stok,
                p.status,
                k.nama_kategori,
                u.nama_toko as seller_name,
                AVG(ul.rating) as average_rating,
                COUNT(ul.id) as review_count
            FROM wishlist w
            JOIN produk p ON w.produk_id = p.id
            JOIN kategori_produk k ON p.kategori_id = k.id
            JOIN users u ON p.penjual_id = u.id
            LEFT JOIN ulasan ul ON p.id = ul.produk_id
            WHERE w.user_id = ?
            GROUP BY p.id, p.nama_produk, p.harga, p.foto, p.stok, p.status, 
                     k.nama_kategori, u.nama_toko
            ORDER BY w.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("iii", $userId, $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAccountSummary($userId) {
        $stmt = $this->db->prepare("
            SELECT 
                (SELECT COUNT(*) FROM pesanan WHERE user_id = ?) as total_orders,
                (SELECT COUNT(*) FROM pesanan WHERE user_id = ? AND status_pesanan = 'delivered') as completed_orders,
                (SELECT COALESCE(SUM(total), 0) FROM pesanan WHERE user_id = ? AND status_pesanan = 'delivered') as total_spent,
                (SELECT COUNT(*) FROM alamat_pengiriman WHERE user_id = ?) as shipping_addresses,
                (SELECT COUNT(*) FROM wishlist WHERE user_id = ?) as wishlist_items,
                (SELECT COUNT(*) FROM ulasan u JOIN pesanan p ON u.pesanan_id = p.id WHERE p.user_id = ?) as reviews_written
        ");
        $stmt->bind_param("iiiiii", $userId, $userId, $userId, $userId, $userId, $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getRecentActivity($userId, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT 
                'order' as activity_type,
                p.kode_pesanan as reference,
                p.status_pesanan as status,
                p.total as amount,
                p.created_at
            FROM pesanan p
            WHERE p.user_id = ?
            
            UNION ALL
            
            SELECT 
                'review' as activity_type,
                CONCAT('Review untuk ', pr.nama_produk) as reference,
                CONCAT(u.rating, ' stars') as status,
                0 as amount,
                u.created_at
            FROM ulasan u
            JOIN pesanan p ON u.pesanan_id = p.id
            JOIN produk pr ON u.produk_id = pr.id
            WHERE p.user_id = ?
            
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("iii", $userId, $userId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getOrderStatistics($userId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_orders,
                COUNT(CASE WHEN status_pesanan = 'pending' THEN 1 END) as pending_orders,
                COUNT(CASE WHEN status_pesanan = 'confirmed' THEN 1 END) as confirmed_orders,
                COUNT(CASE WHEN status_pesanan = 'processed' THEN 1 END) as processed_orders,
                COUNT(CASE WHEN status_pesanan = 'shipped' THEN 1 END) as shipped_orders,
                COUNT(CASE WHEN status_pesanan = 'delivered' THEN 1 END) as delivered_orders,
                COUNT(CASE WHEN status_pesanan = 'cancelled' THEN 1 END) as cancelled_orders,
                COALESCE(SUM(total), 0) as total_amount,
                COALESCE(AVG(total), 0) as average_order_value,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as orders_last_30_days
            FROM pesanan 
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function checkUniqueField($field, $value, $excludeUserId) {
        $stmt = $this->db->prepare("
            SELECT id FROM users WHERE $field = ? AND id != ?
        ");
        $stmt->bind_param("si", $value, $excludeUserId);
        $stmt->execute();
        return $stmt->get_result()->num_rows === 0;
    }

    private function checkAddressOwnership($addressId, $userId) {
        $stmt = $this->db->prepare("
            SELECT id FROM alamat_pengiriman WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $addressId, $userId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    private function removeDefaultAddress($userId) {
        $stmt = $this->db->prepare("
            UPDATE alamat_pengiriman SET is_default = 0 WHERE user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }
}