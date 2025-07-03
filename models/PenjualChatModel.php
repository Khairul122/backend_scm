<?php
class PenjualChatModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getConversationsByProduct($penjualId) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT
                c.produk_id,
                c.pembeli_id,
                p.nama_produk,
                p.harga as product_price,
                p.foto as product_image,
                k.nama_kategori,
                u.nama_lengkap as customer_name,
                u.email as customer_email,
                (SELECT pesan FROM chat c2 
                 WHERE c2.produk_id = c.produk_id AND c2.pembeli_id = c.pembeli_id 
                 ORDER BY c2.created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM chat c2 
                 WHERE c2.produk_id = c.produk_id AND c2.pembeli_id = c.pembeli_id 
                 ORDER BY c2.created_at DESC LIMIT 1) as last_message_time,
                (SELECT pengirim FROM chat c2 
                 WHERE c2.produk_id = c.produk_id AND c2.pembeli_id = c.pembeli_id 
                 ORDER BY c2.created_at DESC LIMIT 1) as last_sender,
                (SELECT COUNT(*) FROM chat c2 
                 WHERE c2.produk_id = c.produk_id AND c2.pembeli_id = c.pembeli_id 
                 AND c2.pengirim = 'pembeli' AND c2.id > IFNULL(
                     (SELECT MAX(id) FROM chat c3 
                      WHERE c3.produk_id = c.produk_id AND c3.pembeli_id = c.pembeli_id 
                      AND c3.pengirim = 'penjual'), 0)
                ) as unread_count,
                (SELECT COUNT(*) FROM chat c2 
                 WHERE c2.produk_id = c.produk_id AND c2.pembeli_id = c.pembeli_id) as total_messages
            FROM chat c
            JOIN produk p ON c.produk_id = p.id
            JOIN kategori_produk k ON p.kategori_id = k.id
            JOIN users u ON c.pembeli_id = u.id
            WHERE c.penjual_id = ?
            ORDER BY last_message_time DESC
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getChatMessages($produkId, $pembeliId, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                c.*,
                p.nama_produk,
                p.harga as product_price,
                p.foto as product_image,
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
        $stmt->bind_param("iii", $produkId, $pembeliId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function sendMessage($produkId, $pembeliId, $penjualId, $message) {
        if (!$this->checkProductOwnership($produkId, $penjualId)) {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO chat (produk_id, pembeli_id, penjual_id, pesan, pengirim) 
            VALUES (?, ?, ?, ?, 'penjual')
        ");
        $stmt->bind_param("iiis", $produkId, $pembeliId, $penjualId, $message);
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function getConversationHistory($produkId, $pembeliId, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                c.*,
                p.nama_produk,
                p.harga as product_price,
                p.foto as product_image,
                p.deskripsi as product_description,
                buyer.nama_lengkap as buyer_name,
                buyer.email as buyer_email,
                seller.nama_lengkap as seller_name,
                seller.nama_toko as store_name
            FROM chat c
            JOIN produk p ON c.produk_id = p.id
            JOIN users buyer ON c.pembeli_id = buyer.id
            JOIN users seller ON c.penjual_id = seller.id
            WHERE c.produk_id = ? AND c.pembeli_id = ? AND c.penjual_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->bind_param("iii", $produkId, $pembeliId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProductsWithChats($penjualId) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT
                p.id,
                p.nama_produk,
                p.harga,
                p.foto,
                k.nama_kategori,
                COUNT(DISTINCT c.pembeli_id) as total_customers,
                COUNT(c.id) as total_messages,
                MAX(c.created_at) as last_activity,
                COUNT(CASE WHEN c.pengirim = 'pembeli' THEN 1 END) as customer_messages,
                COUNT(CASE WHEN c.pengirim = 'penjual' THEN 1 END) as seller_responses
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            LEFT JOIN chat c ON p.id = c.produk_id AND c.penjual_id = ?
            WHERE p.penjual_id = ?
            GROUP BY p.id, p.nama_produk, p.harga, p.foto, k.nama_kategori
            HAVING total_messages > 0
            ORDER BY last_activity DESC
        ");
        $stmt->bind_param("ii", $penjualId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getChatStatistics($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT CONCAT(c.produk_id, '-', c.pembeli_id)) as total_conversations,
                COUNT(DISTINCT c.pembeli_id) as unique_customers,
                COUNT(c.id) as total_messages,
                COUNT(CASE WHEN c.pengirim = 'pembeli' THEN 1 END) as customer_messages,
                COUNT(CASE WHEN c.pengirim = 'penjual' THEN 1 END) as seller_messages,
                COUNT(CASE WHEN DATE(c.created_at) = CURDATE() THEN 1 END) as today_messages,
                COUNT(CASE WHEN c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_messages,
                COUNT(CASE WHEN c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as month_messages,
                ROUND(AVG(LENGTH(c.pesan)), 1) as avg_message_length
            FROM chat c
            JOIN produk p ON c.produk_id = p.id
            WHERE p.penjual_id = ?
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function searchConversations($penjualId, $query) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT DISTINCT
                c.produk_id,
                c.pembeli_id,
                p.nama_produk,
                u.nama_lengkap as customer_name,
                u.email as customer_email,
                MAX(c.created_at) as last_message_time,
                COUNT(c.id) as message_count
            FROM chat c
            JOIN produk p ON c.produk_id = p.id
            JOIN users u ON c.pembeli_id = u.id
            WHERE c.penjual_id = ? 
            AND (p.nama_produk LIKE ? OR u.nama_lengkap LIKE ? OR c.pesan LIKE ?)
            GROUP BY c.produk_id, c.pembeli_id, p.nama_produk, u.nama_lengkap, u.email
            ORDER BY last_message_time DESC
            LIMIT 50
        ");
        $stmt->bind_param("isss", $penjualId, $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getActiveCustomers($penjualId, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT 
                u.id,
                u.nama_lengkap,
                u.email,
                COUNT(DISTINCT c.produk_id) as products_discussed,
                COUNT(c.id) as total_messages,
                MAX(c.created_at) as last_contact,
                COUNT(CASE WHEN c.pengirim = 'pembeli' THEN 1 END) as customer_messages,
                COUNT(CASE WHEN c.pengirim = 'penjual' THEN 1 END) as seller_responses,
                ROUND(COUNT(CASE WHEN c.pengirim = 'penjual' THEN 1 END) / 
                      COUNT(CASE WHEN c.pengirim = 'pembeli' THEN 1 END) * 100, 1) as response_rate
            FROM chat c
            JOIN users u ON c.pembeli_id = u.id
            JOIN produk p ON c.produk_id = p.id
            WHERE p.penjual_id = ?
            GROUP BY u.id, u.nama_lengkap, u.email
            ORDER BY last_contact DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $penjualId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPopularInquiryProducts($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.nama_produk,
                p.harga,
                p.foto,
                k.nama_kategori,
                COUNT(DISTINCT c.pembeli_id) as unique_inquiries,
                COUNT(c.id) as total_messages,
                COUNT(CASE WHEN c.pengirim = 'pembeli' THEN 1 END) as customer_inquiries,
                ROUND(AVG(CASE WHEN c.pengirim = 'pembeli' THEN 1 ELSE 0 END) * 100, 1) as inquiry_rate
            FROM produk p
            JOIN kategori_produk k ON p.kategori_id = k.id
            LEFT JOIN chat c ON p.id = c.produk_id
            WHERE p.penjual_id = ?
            GROUP BY p.id, p.nama_produk, p.harga, p.foto, k.nama_kategori
            HAVING total_messages > 0
            ORDER BY unique_inquiries DESC, total_messages DESC
            LIMIT 10
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getUnreadConversationsCount($penjualId) {
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
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['unread_conversations'] ?? 0;
    }

    public function getResponseTimeAnalysis($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                AVG(response_time_minutes) as avg_response_time,
                MIN(response_time_minutes) as fastest_response,
                MAX(response_time_minutes) as slowest_response,
                COUNT(*) as total_responses,
                COUNT(CASE WHEN response_time_minutes <= 60 THEN 1 END) as responses_within_hour,
                COUNT(CASE WHEN response_time_minutes <= 1440 THEN 1 END) as responses_within_day
            FROM (
                SELECT 
                    TIMESTAMPDIFF(MINUTE, c1.created_at, c2.created_at) as response_time_minutes
                FROM chat c1
                JOIN chat c2 ON c1.produk_id = c2.produk_id 
                    AND c1.pembeli_id = c2.pembeli_id
                    AND c2.id = (
                        SELECT MIN(c3.id) 
                        FROM chat c3 
                        WHERE c3.produk_id = c1.produk_id 
                        AND c3.pembeli_id = c1.pembeli_id
                        AND c3.pengirim = 'penjual'
                        AND c3.created_at > c1.created_at
                    )
                JOIN produk p ON c1.produk_id = p.id
                WHERE c1.pengirim = 'pembeli' 
                AND c2.pengirim = 'penjual'
                AND p.penjual_id = ?
                AND TIMESTAMPDIFF(MINUTE, c1.created_at, c2.created_at) > 0
            ) response_times
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getChatTrends($penjualId, $days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(c.created_at) as chat_date,
                COUNT(c.id) as message_count,
                COUNT(DISTINCT c.pembeli_id) as unique_customers,
                COUNT(DISTINCT c.produk_id) as products_discussed,
                COUNT(CASE WHEN c.pengirim = 'pembeli' THEN 1 END) as customer_messages,
                COUNT(CASE WHEN c.pengirim = 'penjual' THEN 1 END) as seller_responses,
                COUNT(DISTINCT CONCAT(c.produk_id, '-', c.pembeli_id)) as active_conversations
            FROM chat c
            JOIN produk p ON c.produk_id = p.id
            WHERE p.penjual_id = ? 
            AND c.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(c.created_at)
            ORDER BY chat_date DESC
        ");
        $stmt->bind_param("ii", $penjualId, $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getFrequentQuestions($penjualId, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT 
                SUBSTRING(c.pesan, 1, 100) as question_sample,
                COUNT(*) as frequency,
                COUNT(DISTINCT c.pembeli_id) as unique_askers,
                MAX(c.created_at) as last_asked
            FROM chat c
            JOIN produk p ON c.produk_id = p.id
            WHERE p.penjual_id = ? 
            AND c.pengirim = 'pembeli'
            AND c.pesan LIKE '%?%'
            AND LENGTH(c.pesan) > 10
            GROUP BY SUBSTRING(c.pesan, 1, 50)
            HAVING frequency > 1
            ORDER BY frequency DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $penjualId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getChatConversionAnalysis($penjualId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT CONCAT(c.produk_id, '-', c.pembeli_id)) as total_conversations,
                COUNT(DISTINCT CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM pesanan ps 
                        JOIN detail_pesanan dp ON ps.id = dp.pesanan_id 
                        WHERE ps.user_id = c.pembeli_id 
                        AND dp.produk_id = c.produk_id
                        AND ps.created_at >= c.created_at
                    ) THEN CONCAT(c.produk_id, '-', c.pembeli_id) 
                END) as converted_conversations,
                ROUND(COUNT(DISTINCT CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM pesanan ps 
                        JOIN detail_pesanan dp ON ps.id = dp.pesanan_id 
                        WHERE ps.user_id = c.pembeli_id 
                        AND dp.produk_id = c.produk_id
                        AND ps.created_at >= c.created_at
                    ) THEN CONCAT(c.produk_id, '-', c.pembeli_id) 
                END) / COUNT(DISTINCT CONCAT(c.produk_id, '-', c.pembeli_id)) * 100, 2) as conversion_rate
            FROM chat c
            JOIN produk p ON c.produk_id = p.id
            WHERE p.penjual_id = ?
        ");
        $stmt->bind_param("i", $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function markConversationAsRead($produkId, $pembeliId, $penjualId) {
        return $this->checkProductOwnership($produkId, $penjualId);
    }

    public function getMessageById($messageId, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT c.*, p.nama_produk
            FROM chat c
            JOIN produk p ON c.produk_id = p.id
            WHERE c.id = ? AND c.penjual_id = ?
        ");
        $stmt->bind_param("ii", $messageId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function checkProductOwnership($produkId, $penjualId) {
        $stmt = $this->db->prepare("
            SELECT id FROM produk WHERE id = ? AND penjual_id = ?
        ");
        $stmt->bind_param("ii", $produkId, $penjualId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
}