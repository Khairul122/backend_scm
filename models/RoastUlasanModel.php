<?php
class RoastUlasanModel {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getProductReviews($roasterId, $produkId = null) {
        $whereClause = "WHERE p.penjual_id = ?";
        $params = [$roasterId];
        $types = "i";

        if ($produkId) {
            $whereClause .= " AND p.id = ?";
            $params[] = $produkId;
            $types .= "i";
        }

        $stmt = $this->db->prepare("
            SELECT 
                u.id as ulasan_id,
                u.rating,
                u.komentar,
                u.created_at,
                p.id as produk_id,
                p.nama_produk,
                p.harga as product_price,
                p.foto as product_image,
                customer.nama_lengkap as customer_name,
                customer.email as customer_email,
                pesanan.kode_pesanan,
                pesanan.status_pesanan,
                dp.jumlah as quantity_ordered,
                dp.harga as price_paid
            FROM ulasan u
            JOIN produk p ON u.produk_id = p.id
            JOIN users customer ON u.user_id = customer.id
            JOIN pesanan pesanan ON u.pesanan_id = pesanan.id
            JOIN detail_pesanan dp ON dp.pesanan_id = pesanan.id AND dp.produk_id = p.id
            $whereClause
            ORDER BY u.created_at DESC
        ");
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getReviewStatistics($roasterId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(u.id) as total_reviews,
                AVG(u.rating) as average_rating,
                COUNT(CASE WHEN u.rating = 5 THEN 1 END) as five_star,
                COUNT(CASE WHEN u.rating = 4 THEN 1 END) as four_star,
                COUNT(CASE WHEN u.rating = 3 THEN 1 END) as three_star,
                COUNT(CASE WHEN u.rating = 2 THEN 1 END) as two_star,
                COUNT(CASE WHEN u.rating = 1 THEN 1 END) as one_star,
                COUNT(CASE WHEN u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as reviews_this_month,
                COUNT(CASE WHEN u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as reviews_this_week,
                COUNT(CASE WHEN DATE(u.created_at) = CURDATE() THEN 1 END) as reviews_today
            FROM ulasan u
            JOIN produk p ON u.produk_id = p.id
            WHERE p.penjual_id = ?
        ");
        $stmt->bind_param("i", $roasterId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getProductRatings($roasterId) {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.nama_produk,
                p.harga,
                p.foto,
                COUNT(u.id) as total_reviews,
                AVG(u.rating) as average_rating,
                COUNT(CASE WHEN u.rating >= 4 THEN 1 END) as positive_reviews,
                COUNT(CASE WHEN u.rating <= 2 THEN 1 END) as negative_reviews,
                MAX(u.created_at) as latest_review_date,
                MIN(u.created_at) as first_review_date
            FROM produk p
            LEFT JOIN ulasan u ON p.id = u.produk_id
            WHERE p.penjual_id = ?
            GROUP BY p.id, p.nama_produk, p.harga, p.foto
            ORDER BY average_rating DESC, total_reviews DESC
        ");
        $stmt->bind_param("i", $roasterId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRecentReviews($roasterId, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT 
                u.id as ulasan_id,
                u.rating,
                u.komentar,
                u.created_at,
                p.nama_produk,
                customer.nama_lengkap as customer_name,
                pesanan.kode_pesanan
            FROM ulasan u
            JOIN produk p ON u.produk_id = p.id
            JOIN users customer ON u.user_id = customer.id
            JOIN pesanan pesanan ON u.pesanan_id = pesanan.id
            WHERE p.penjual_id = ?
            ORDER BY u.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $roasterId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getTopRatedProducts($roasterId, $limit = 5) {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.nama_produk,
                p.harga,
                p.foto,
                COUNT(u.id) as review_count,
                AVG(u.rating) as average_rating,
                ROUND(AVG(u.rating), 1) as rating_display
            FROM produk p
            JOIN ulasan u ON p.id = u.produk_id
            WHERE p.penjual_id = ?
            GROUP BY p.id, p.nama_produk, p.harga, p.foto
            HAVING review_count >= 3
            ORDER BY average_rating DESC, review_count DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $roasterId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getCustomerReviews($roasterId) {
        $stmt = $this->db->prepare("
            SELECT 
                customer.id as customer_id,
                customer.nama_lengkap as customer_name,
                customer.email,
                COUNT(u.id) as total_reviews,
                AVG(u.rating) as average_rating_given,
                COUNT(DISTINCT u.produk_id) as products_reviewed,
                MAX(u.created_at) as last_review_date,
                SUM(CASE WHEN u.rating >= 4 THEN 1 ELSE 0 END) as positive_reviews,
                SUM(CASE WHEN u.rating <= 2 THEN 1 ELSE 0 END) as negative_reviews
            FROM ulasan u
            JOIN produk p ON u.produk_id = p.id
            JOIN users customer ON u.user_id = customer.id
            WHERE p.penjual_id = ?
            GROUP BY customer.id, customer.nama_lengkap, customer.email
            ORDER BY total_reviews DESC, last_review_date DESC
        ");
        $stmt->bind_param("i", $roasterId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function searchReviews($roasterId, $query) {
        $searchTerm = "%$query%";
        $stmt = $this->db->prepare("
            SELECT 
                u.id as ulasan_id,
                u.rating,
                u.komentar,
                u.created_at,
                p.nama_produk,
                customer.nama_lengkap as customer_name,
                pesanan.kode_pesanan
            FROM ulasan u
            JOIN produk p ON u.produk_id = p.id
            JOIN users customer ON u.user_id = customer.id
            JOIN pesanan pesanan ON u.pesanan_id = pesanan.id
            WHERE p.penjual_id = ? 
            AND (p.nama_produk LIKE ? OR u.komentar LIKE ? OR customer.nama_lengkap LIKE ?)
            ORDER BY u.created_at DESC
            LIMIT 50
        ");
        $stmt->bind_param("isss", $roasterId, $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getReviewsByRating($roasterId, $rating) {
        $stmt = $this->db->prepare("
            SELECT 
                u.id as ulasan_id,
                u.rating,
                u.komentar,
                u.created_at,
                p.nama_produk,
                customer.nama_lengkap as customer_name,
                pesanan.kode_pesanan
            FROM ulasan u
            JOIN produk p ON u.produk_id = p.id
            JOIN users customer ON u.user_id = customer.id
            JOIN pesanan pesanan ON u.pesanan_id = pesanan.id
            WHERE p.penjual_id = ? AND u.rating = ?
            ORDER BY u.created_at DESC
        ");
        $stmt->bind_param("ii", $roasterId, $rating);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getReviewTrends($roasterId, $days = 30) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(u.created_at) as review_date,
                COUNT(u.id) as review_count,
                AVG(u.rating) as average_rating,
                COUNT(CASE WHEN u.rating >= 4 THEN 1 END) as positive_count,
                COUNT(CASE WHEN u.rating <= 2 THEN 1 END) as negative_count
            FROM ulasan u
            JOIN produk p ON u.produk_id = p.id
            WHERE p.penjual_id = ? 
            AND u.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(u.created_at)
            ORDER BY review_date DESC
        ");
        $stmt->bind_param("ii", $roasterId, $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getReviewAnalytics($roasterId, $startDate = null, $endDate = null) {
        $whereClause = "WHERE p.penjual_id = ?";
        $params = [$roasterId];
        $types = "i";

        if ($startDate && $endDate) {
            $whereClause .= " AND u.created_at BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        }

        $stmt = $this->db->prepare("
            SELECT 
                COUNT(u.id) as total_reviews,
                AVG(u.rating) as average_rating,
                COUNT(DISTINCT u.user_id) as unique_reviewers,
                COUNT(DISTINCT u.produk_id) as products_with_reviews,
                COUNT(CASE WHEN u.rating = 5 THEN 1 END) as excellent_reviews,
                COUNT(CASE WHEN u.rating >= 4 THEN 1 END) as good_reviews,
                COUNT(CASE WHEN u.rating = 3 THEN 1 END) as average_reviews,
                COUNT(CASE WHEN u.rating <= 2 THEN 1 END) as poor_reviews,
                AVG(LENGTH(u.komentar)) as average_comment_length,
                COUNT(CASE WHEN u.komentar IS NOT NULL AND u.komentar != '' THEN 1 END) as reviews_with_comments
            FROM ulasan u
            JOIN produk p ON u.produk_id = p.id
            $whereClause
        ");
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getProductComparisonByReviews($roasterId) {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.nama_produk,
                p.harga,
                COUNT(u.id) as total_reviews,
                AVG(u.rating) as average_rating,
                COUNT(CASE WHEN u.rating >= 4 THEN 1 END) as positive_reviews,
                COUNT(CASE WHEN u.rating <= 2 THEN 1 END) as negative_reviews,
                ROUND((COUNT(CASE WHEN u.rating >= 4 THEN 1 END) / COUNT(u.id)) * 100, 1) as satisfaction_rate,
                COUNT(CASE WHEN u.komentar IS NOT NULL AND LENGTH(u.komentar) > 10 THEN 1 END) as detailed_reviews
            FROM produk p
            LEFT JOIN ulasan u ON p.id = u.produk_id
            WHERE p.penjual_id = ?
            GROUP BY p.id, p.nama_produk, p.harga
            HAVING total_reviews > 0
            ORDER BY satisfaction_rate DESC, total_reviews DESC
        ");
        $stmt->bind_param("i", $roasterId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getReviewInsights($roasterId) {
        $stmt = $this->db->prepare("
            SELECT 
                'most_reviewed_product' as insight_type,
                p.nama_produk as value,
                COUNT(u.id) as count
            FROM ulasan u
            JOIN produk p ON u.produk_id = p.id
            WHERE p.penjual_id = ?
            GROUP BY p.id, p.nama_produk
            ORDER BY count DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $roasterId);
        $stmt->execute();
        $mostReviewed = $stmt->get_result()->fetch_assoc();

        $stmt = $this->db->prepare("
            SELECT 
                'highest_rated_product' as insight_type,
                p.nama_produk as value,
                AVG(u.rating) as avg_rating
            FROM ulasan u
            JOIN produk p ON u.produk_id = p.id
            WHERE p.penjual_id = ?
            GROUP BY p.id, p.nama_produk
            HAVING COUNT(u.id) >= 3
            ORDER BY avg_rating DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $roasterId);
        $stmt->execute();
        $highestRated = $stmt->get_result()->fetch_assoc();

        $stmt = $this->db->prepare("
            SELECT 
                'most_active_reviewer' as insight_type,
                customer.nama_lengkap as value,
                COUNT(u.id) as count
            FROM ulasan u
            JOIN produk p ON u.produk_id = p.id
            JOIN users customer ON u.user_id = customer.id
            WHERE p.penjual_id = ?
            GROUP BY customer.id, customer.nama_lengkap
            ORDER BY count DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $roasterId);
        $stmt->execute();
        $mostActive = $stmt->get_result()->fetch_assoc();

        return [
            'most_reviewed_product' => $mostReviewed,
            'highest_rated_product' => $highestRated,
            'most_active_reviewer' => $mostActive
        ];
    }

    public function checkProductOwnership($produkId, $roasterId) {
        $stmt = $this->db->prepare("
            SELECT id FROM produk WHERE id = ? AND penjual_id = ?
        ");
        $stmt->bind_param("ii", $produkId, $roasterId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function getReviewById($reviewId, $roasterId) {
        $stmt = $this->db->prepare("
            SELECT 
                u.*,
                p.nama_produk,
                customer.nama_lengkap as customer_name,
                pesanan.kode_pesanan
            FROM ulasan u
            JOIN produk p ON u.produk_id = p.id
            JOIN users customer ON u.user_id = customer.id
            JOIN pesanan pesanan ON u.pesanan_id = pesanan.id
            WHERE u.id = ? AND p.penjual_id = ?
        ");
        $stmt->bind_param("ii", $reviewId, $roasterId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getMonthlyReviewSummary($roasterId, $months = 12) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(u.created_at, '%Y-%m') as month_year,
                COUNT(u.id) as review_count,
                AVG(u.rating) as average_rating,
                COUNT(CASE WHEN u.rating >= 4 THEN 1 END) as positive_reviews,
                COUNT(CASE WHEN u.rating <= 2 THEN 1 END) as negative_reviews,
                COUNT(DISTINCT u.user_id) as unique_customers,
                COUNT(DISTINCT u.produk_id) as products_reviewed
            FROM ulasan u
            JOIN produk p ON u.produk_id = p.id
            WHERE p.penjual_id = ? 
            AND u.created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(u.created_at, '%Y-%m')
            ORDER BY month_year DESC
        ");
        $stmt->bind_param("ii", $roasterId, $months);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}