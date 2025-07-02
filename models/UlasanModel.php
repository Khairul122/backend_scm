<?php
class UlasanModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAllUlasan() {
        $stmt = $this->db->prepare("
            SELECT u.*, p.kode_pesanan, pr.nama_produk, us.nama_lengkap as customer_name
            FROM ulasan u
            JOIN pesanan p ON u.pesanan_id = p.id
            JOIN produk pr ON u.produk_id = pr.id
            JOIN users us ON u.user_id = us.id
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getUlasanById($id) {
        $stmt = $this->db->prepare("
            SELECT u.*, p.kode_pesanan, pr.nama_produk, us.nama_lengkap as customer_name
            FROM ulasan u
            JOIN pesanan p ON u.pesanan_id = p.id
            JOIN produk pr ON u.produk_id = pr.id
            JOIN users us ON u.user_id = us.id
            WHERE u.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getUlasanByProduk($produkId) {
        $stmt = $this->db->prepare("
            SELECT u.*, p.kode_pesanan, us.nama_lengkap as customer_name
            FROM ulasan u
            JOIN pesanan p ON u.pesanan_id = p.id
            JOIN users us ON u.user_id = us.id
            WHERE u.produk_id = ?
            ORDER BY u.created_at DESC
        ");
        $stmt->bind_param("i", $produkId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getUlasanByUser($userId) {
        $stmt = $this->db->prepare("
            SELECT u.*, p.kode_pesanan, pr.nama_produk
            FROM ulasan u
            JOIN pesanan p ON u.pesanan_id = p.id
            JOIN produk pr ON u.produk_id = pr.id
            WHERE u.user_id = ?
            ORDER BY u.created_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getUlasanByRating($rating) {
        $stmt = $this->db->prepare("
            SELECT u.*, p.kode_pesanan, pr.nama_produk, us.nama_lengkap as customer_name
            FROM ulasan u
            JOIN pesanan p ON u.pesanan_id = p.id
            JOIN produk pr ON u.produk_id = pr.id
            JOIN users us ON u.user_id = us.id
            WHERE u.rating = ?
            ORDER BY u.created_at DESC
        ");
        $stmt->bind_param("i", $rating);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function createUlasan($data) {
        $stmt = $this->db->prepare("
            INSERT INTO ulasan (pesanan_id, produk_id, user_id, rating, komentar) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "iiiis",
            $data['pesanan_id'],
            $data['produk_id'],
            $data['user_id'],
            $data['rating'],
            $data['komentar']
        );
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function updateUlasan($id, $data) {
        $fields = [];
        $types = "";
        $values = [];

        if (isset($data['rating'])) {
            $fields[] = "rating = ?";
            $types .= "i";
            $values[] = $data['rating'];
        }

        if (isset($data['komentar'])) {
            $fields[] = "komentar = ?";
            $types .= "s";
            $values[] = $data['komentar'];
        }

        if (empty($fields)) {
            return false;
        }

        $types .= "i";
        $values[] = $id;

        $sql = "UPDATE ulasan SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        return $stmt->execute();
    }

    public function deleteUlasan($id) {
        $stmt = $this->db->prepare("DELETE FROM ulasan WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getUlasanStats() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star,
                COUNT(CASE WHEN komentar IS NOT NULL AND komentar != '' THEN 1 END) as with_comments
            FROM ulasan
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getReviewQuality() {
        $stmt = $this->db->prepare("
            SELECT 
                u.id,
                u.rating,
                u.komentar,
                u.created_at,
                us.nama_lengkap as customer_name,
                pr.nama_produk,
                CHAR_LENGTH(u.komentar) as comment_length,
                CASE 
                    WHEN CHAR_LENGTH(u.komentar) < 10 THEN 'short'
                    WHEN CHAR_LENGTH(u.komentar) > 500 THEN 'long'
                    ELSE 'normal'
                END as comment_quality,
                CASE 
                    WHEN u.komentar REGEXP '[A-Z]{3,}' THEN 'suspicious'
                    WHEN u.komentar LIKE '%wow%' OR u.komentar LIKE '%amazing%' OR u.komentar LIKE '%perfect%' THEN 'generic'
                    ELSE 'normal'
                END as content_quality
            FROM ulasan u
            JOIN users us ON u.user_id = us.id
            JOIN produk pr ON u.produk_id = pr.id
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getFakeReviews() {
        $stmt = $this->db->prepare("
            SELECT 
                u.id,
                u.rating,
                u.komentar,
                u.created_at,
                us.nama_lengkap as customer_name,
                us.email,
                pr.nama_produk,
                COUNT(*) OVER (PARTITION BY u.user_id) as user_review_count,
                COUNT(*) OVER (PARTITION BY u.user_id, DATE(u.created_at)) as daily_reviews,
                CASE 
                    WHEN CHAR_LENGTH(u.komentar) < 5 THEN 1
                    WHEN u.komentar REGEXP '^[A-Z ]+$' THEN 1
                    WHEN u.komentar LIKE '%fake%' OR u.komentar LIKE '%spam%' THEN 1
                    ELSE 0
                END as fake_score
            FROM ulasan u
            JOIN users us ON u.user_id = us.id
            JOIN produk pr ON u.produk_id = pr.id
            HAVING user_review_count > 10 OR daily_reviews > 3 OR fake_score > 0
            ORDER BY fake_score DESC, daily_reviews DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getInappropriateReviews() {
        $stmt = $this->db->prepare("
            SELECT 
                u.id,
                u.rating,
                u.komentar,
                u.created_at,
                us.nama_lengkap as customer_name,
                pr.nama_produk
            FROM ulasan u
            JOIN users us ON u.user_id = us.id
            JOIN produk pr ON u.produk_id = pr.id
            WHERE u.komentar REGEXP '(bodoh|tolol|jelek|buruk|sampah|anjing|babi)'
            OR u.komentar LIKE '%fuck%'
            OR u.komentar LIKE '%shit%'
            OR u.komentar REGEXP '[A-Z]{5,}'
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getReviewTrends($days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating,
                COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_reviews,
                COUNT(CASE WHEN rating <= 2 THEN 1 END) as negative_reviews
            FROM ulasan
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stmt->bind_param("i", $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProdukRatings() {
        $stmt = $this->db->prepare("
            SELECT 
                pr.id,
                pr.nama_produk,
                COUNT(u.id) as total_reviews,
                AVG(u.rating) as avg_rating,
                SUM(CASE WHEN u.rating >= 4 THEN 1 ELSE 0 END) as positive_reviews,
                SUM(CASE WHEN u.rating <= 2 THEN 1 ELSE 0 END) as negative_reviews
            FROM produk pr
            LEFT JOIN ulasan u ON pr.id = u.produk_id
            GROUP BY pr.id, pr.nama_produk
            HAVING total_reviews > 0
            ORDER BY avg_rating DESC, total_reviews DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function searchUlasan($query) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT u.*, p.kode_pesanan, pr.nama_produk, us.nama_lengkap as customer_name
            FROM ulasan u
            JOIN pesanan p ON u.pesanan_id = p.id
            JOIN produk pr ON u.produk_id = pr.id
            JOIN users us ON u.user_id = us.id
            WHERE u.komentar LIKE ? OR pr.nama_produk LIKE ? OR us.nama_lengkap LIKE ?
            ORDER BY u.created_at DESC
            LIMIT 50
        ");
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getSuspiciousUsers() {
        $stmt = $this->db->prepare("
            SELECT 
                us.id,
                us.nama_lengkap,
                us.email,
                COUNT(u.id) as total_reviews,
                AVG(u.rating) as avg_rating,
                COUNT(DISTINCT u.produk_id) as products_reviewed,
                COUNT(CASE WHEN DATE(u.created_at) = CURDATE() THEN 1 END) as today_reviews,
                MIN(u.created_at) as first_review,
                MAX(u.created_at) as last_review
            FROM users us
            JOIN ulasan u ON us.id = u.user_id
            GROUP BY us.id
            HAVING total_reviews > 5 AND (avg_rating > 4.8 OR avg_rating < 1.5 OR today_reviews > 3)
            ORDER BY total_reviews DESC, today_reviews DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function bulkDeleteReviews($ids) {
        if (empty($ids)) return false;
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "DELETE FROM ulasan WHERE id IN ($placeholders)";
        
        $stmt = $this->db->prepare($sql);
        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);
        
        return $stmt->execute();
    }

    public function getRecentReviews($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT u.*, p.kode_pesanan, pr.nama_produk, us.nama_lengkap as customer_name
            FROM ulasan u
            JOIN pesanan p ON u.pesanan_id = p.id
            JOIN produk pr ON u.produk_id = pr.id
            JOIN users us ON u.user_id = us.id
            ORDER BY u.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}