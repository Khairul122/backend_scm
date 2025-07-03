<?php
class RoastChatModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getConversationsByProduct($roasterId) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT
                c.produk_id,
                c.pembeli_id,
                p.nama_produk,
                u.nama_lengkap as customer_name,
                u.email as customer_email,
                (SELECT pesan FROM chat c2 
                 WHERE c2.produk_id = c.produk_id AND c2.pembeli_id = c.pembeli_id 
                 ORDER BY c2.created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM chat c2 
                 WHERE c2.produk_id = c.produk_id AND c2.pembeli_id = c.pembeli_id 
                 ORDER BY c2.created_at DESC LIMIT 1) as last_message_time,
                (SELECT COUNT(*) FROM chat c2 
                 WHERE c2.produk_id = c.produk_id AND c2.pembeli_id = c.pembeli_id 
                 AND c2.pengirim = 'pembeli' AND c2.id > IFNULL(
                     (SELECT MAX(id) FROM chat c3 
                      WHERE c3.produk_id = c.produk_id AND c3.pembeli_id = c.pembeli_id 
                      AND c3.pengirim = 'penjual'), 0)
                ) as unread_count
            FROM chat c
            JOIN produk p ON c.produk_id = p.id
            JOIN users u ON c.pembeli_id = u.id
            WHERE c.penjual_id = ?
            ORDER BY last_message_time DESC
        ");
        $stmt->bind_param("i", $roasterId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getChatMessages($produkId, $pembeliId, $roasterId) {
        $stmt = $this->db->prepare("
            SELECT 
                c.*,
                p.nama_produk,
                sender.nama_lengkap as sender_name,
                sender.role as sender_role
            FROM chat c
            JOIN produk p ON c.produk_id = p.id
            JOIN users sender ON (
                (c.pengirim = 'pembeli' AND sender.id = c.pembeli_id) OR
                (c.pengirim = 'penjual' AND sender.id = c.penjual_id)
            )
            WHERE c.produk_id = ? AND c.pembeli_id = ? AND c.penjual_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->bind_param("iii", $produkId, $pembeliId, $roasterId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function sendMessage($produkId, $pembeliId, $roasterId, $message, $pengirim) {
        if (!$this->isValidParticipant($produkId, $pembeliId, $roasterId, $pengirim)) {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO chat (produk_id, pembeli_id, penjual_id, pesan, pengirim) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiiss", $produkId, $pembeliId, $roasterId, $message, $pengirim);
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function getConversationHistory($produkId, $pembeliId, $roasterId) {
        $stmt = $this->db->prepare("
            SELECT 
                c.*,
                p.nama_produk,
                p.harga as product_price,
                p.foto as product_image,
                buyer.nama_lengkap as buyer_name,
                seller.nama_lengkap as seller_name,
                seller.nama_toko as store_name
            FROM chat c
            JOIN produk p ON c.produk_id = p.id
            JOIN users buyer ON c.pembeli_id = buyer.id
            JOIN users seller ON c.penjual_id = seller.id
            WHERE c.produk_id = ? AND c.pembeli_id = ? AND c.penjual_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->bind_param("iii", $produkId, $pembeliId, $roasterId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProductsWithChats($roasterId) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT
                p.id,
                p.nama_produk,
                p.harga,
                p.foto,
                COUNT(DISTINCT c.pembeli_id) as total_customers,
                COUNT(c.id) as total_messages,
                MAX(c.created_at) as last_activity
            FROM produk p
            LEFT JOIN chat c ON p.id = c.produk_id AND c.penjual_id = ?
            WHERE p.penjual_id = ?
            GROUP BY p.id, p.nama_produk, p.harga, p.foto
            HAVING total_messages > 0
            ORDER BY last_activity DESC
        ");
        $stmt->bind_param("ii", $roasterId, $roasterId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getChatStatistics($roasterId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT CONCAT(c.produk_id, '-', c.pembeli_id)) as total_conversations,
                COUNT(DISTINCT c.pembeli_id) as unique_customers,
                COUNT(c.id) as total_messages,
                COUNT(CASE WHEN c.pengirim = 'pembeli' THEN 1 END) as customer_messages,
                COUNT(CASE WHEN c.pengirim = 'penjual' THEN 1 END) as roaster_messages,
                COUNT(CASE WHEN DATE(c.created_at) = CURDATE() THEN 1 END) as today_messages,
                COUNT(CASE WHEN c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_messages
            FROM chat c
            JOIN produk p ON c.produk_id = p.id
            WHERE p.penjual_id = ?
        ");
        $stmt->bind_param("i", $roasterId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function searchConversations($roasterId, $query) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT DISTINCT
                c.produk_id,
                c.pembeli_id,
                p.nama_produk,
                u.nama_lengkap as customer_name,
                u.email as customer_email,
                MAX(c.created_at) as last_message_time
            FROM chat c
            JOIN produk p ON c.produk_id = p.id
            JOIN users u ON c.pembeli_id = u.id
            WHERE c.penjual_id = ? 
            AND (p.nama_produk LIKE ? OR u.nama_lengkap LIKE ? OR c.pesan LIKE ?)
            GROUP BY c.produk_id, c.pembeli_id, p.nama_produk, u.nama_lengkap, u.email
            ORDER BY last_message_time DESC
            LIMIT 50
        ");
        $stmt->bind_param("isss", $roasterId, $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRecentCustomers($roasterId, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT
                u.id,
                u.nama_lengkap,
                u.email,
                COUNT(DISTINCT c.produk_id) as products_discussed,
                COUNT(c.id) as total_messages,
                MAX(c.created_at) as last_contact
            FROM chat c
            JOIN users u ON c.pembeli_id = u.id
            JOIN produk p ON c.produk_id = p.id
            WHERE p.penjual_id = ?
            GROUP BY u.id, u.nama_lengkap, u.email
            ORDER BY last_contact DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $roasterId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPopularProducts($roasterId) {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.nama_produk,
                p.harga,
                COUNT(DISTINCT c.pembeli_id) as unique_inquiries,
                COUNT(c.id) as total_messages,
                AVG(CASE WHEN c.pengirim = 'pembeli' THEN 1 ELSE 0 END) as customer_engagement
            FROM produk p
            LEFT JOIN chat c ON p.id = c.produk_id
            WHERE p.penjual_id = ?
            GROUP BY p.id, p.nama_produk, p.harga
            HAVING total_messages > 0
            ORDER BY unique_inquiries DESC, total_messages DESC
            LIMIT 10
        ");
        $stmt->bind_param("i", $roasterId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getUnreadCount($roasterId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT CONCAT(c.produk_id, '-', c.pembeli_id)) as unread_conversations
            FROM chat c
            JOIN produk p ON c.produk_id = p.id
            WHERE p.penjual_id = ? 
            AND c.pengirim = 'pembeli'
            AND c.id > IFNULL((
                SELECT MAX(c2.id) 
                FROM chat c2 
                WHERE c2.produk_id = c.produk_id 
                AND c2.pembeli_id = c.pembeli_id 
                AND c2.pengirim = 'penjual'
            ), 0)
        ");
        $stmt->bind_param("i", $roasterId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['unread_conversations'] ?? 0;
    }

    public function checkProductOwnership($produkId, $roasterId) {
        $stmt = $this->db->prepare("
            SELECT id FROM produk WHERE id = ? AND penjual_id = ?
        ");
        $stmt->bind_param("ii", $produkId, $roasterId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function checkChatExists($produkId, $pembeliId, $roasterId) {
        $stmt = $this->db->prepare("
            SELECT id FROM chat 
            WHERE produk_id = ? AND pembeli_id = ? AND penjual_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("iii", $produkId, $pembeliId, $roasterId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    private function isValidParticipant($produkId, $pembeliId, $roasterId, $pengirim) {
        if (!$this->checkProductOwnership($produkId, $roasterId)) {
            return false;
        }

        if ($pengirim === 'pembeli') {
            $stmt = $this->db->prepare("
                SELECT id FROM users WHERE id = ? AND role = 'pembeli'
            ");
            $stmt->bind_param("i", $pembeliId);
        } else {
            $stmt = $this->db->prepare("
                SELECT id FROM users WHERE id = ? AND role IN ('roasting', 'penjual')
            ");
            $stmt->bind_param("i", $roasterId);
        }
        
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function getMessageById($messageId, $roasterId) {
        $stmt = $this->db->prepare("
            SELECT c.*, p.nama_produk
            FROM chat c
            JOIN produk p ON c.produk_id = p.id
            WHERE c.id = ? AND c.penjual_id = ?
        ");
        $stmt->bind_param("ii", $messageId, $roasterId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getChatAnalytics($roasterId, $startDate = null, $endDate = null) {
        $whereClause = "WHERE p.penjual_id = ?";
        $params = [$roasterId];
        $types = "i";

        if ($startDate && $endDate) {
            $whereClause .= " AND c.created_at BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        }

        $stmt = $this->db->prepare("
            SELECT 
                DATE(c.created_at) as chat_date,
                COUNT(c.id) as message_count,
                COUNT(DISTINCT c.pembeli_id) as unique_customers,
                COUNT(DISTINCT c.produk_id) as products_discussed,
                COUNT(CASE WHEN c.pengirim = 'pembeli' THEN 1 END) as customer_messages,
                COUNT(CASE WHEN c.pengirim = 'penjual' THEN 1 END) as roaster_responses
            FROM chat c
            JOIN produk p ON c.produk_id = p.id
            $whereClause
            GROUP BY DATE(c.created_at)
            ORDER BY chat_date DESC
            LIMIT 30
        ");
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}