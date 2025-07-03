<?php
class RoastUlasanController {
    private $roastUlasanModel;

    public function __construct() {
        $this->roastUlasanModel = new RoastUlasanModel();
    }

    public function getProductReviews($produkId = null) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied. Roasting/Seller role required.']);
            return;
        }

        if ($produkId && !$this->roastUlasanModel->checkProductOwnership($produkId, $currentUser['id'])) {
            response(403, ['error' => 'You can only view reviews for your own products']);
            return;
        }

        $reviews = $this->roastUlasanModel->getProductReviews($currentUser['id'], $produkId);
        
        response(200, [
            'message' => 'Product reviews retrieved successfully',
            'data' => $reviews
        ]);
    }

    public function getReviewStatistics() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $statistics = $this->roastUlasanModel->getReviewStatistics($currentUser['id']);
        
        response(200, [
            'message' => 'Review statistics retrieved successfully',
            'data' => $statistics
        ]);
    }

    public function getProductRatings() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $ratings = $this->roastUlasanModel->getProductRatings($currentUser['id']);
        
        response(200, [
            'message' => 'Product ratings retrieved successfully',
            'data' => $ratings
        ]);
    }

    public function getRecentReviews() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $limit = $_GET['limit'] ?? 10;
        $reviews = $this->roastUlasanModel->getRecentReviews($currentUser['id'], $limit);
        
        response(200, [
            'message' => 'Recent reviews retrieved successfully',
            'data' => $reviews
        ]);
    }

    public function getTopRatedProducts() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $limit = $_GET['limit'] ?? 5;
        $products = $this->roastUlasanModel->getTopRatedProducts($currentUser['id'], $limit);
        
        response(200, [
            'message' => 'Top rated products retrieved successfully',
            'data' => $products
        ]);
    }

    public function getCustomerReviews() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $customers = $this->roastUlasanModel->getCustomerReviews($currentUser['id']);
        
        response(200, [
            'message' => 'Customer review analysis retrieved successfully',
            'data' => $customers
        ]);
    }

    public function searchReviews() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            response(400, ['error' => 'Search query required']);
            return;
        }

        $reviews = $this->roastUlasanModel->searchReviews($currentUser['id'], $query);
        
        response(200, [
            'message' => 'Review search results retrieved successfully',
            'data' => $reviews
        ]);
    }

    public function getReviewsByRating($rating) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        if (!in_array($rating, [1, 2, 3, 4, 5])) {
            response(400, ['error' => 'Invalid rating. Must be between 1 and 5.']);
            return;
        }

        $reviews = $this->roastUlasanModel->getReviewsByRating($currentUser['id'], $rating);
        
        response(200, [
            'message' => "Reviews with {$rating} star rating retrieved successfully",
            'data' => $reviews
        ]);
    }

    public function getReviewTrends() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $days = $_GET['days'] ?? 30;
        $trends = $this->roastUlasanModel->getReviewTrends($currentUser['id'], $days);
        
        response(200, [
            'message' => "Review trends for last {$days} days retrieved successfully",
            'data' => $trends
        ]);
    }

    public function getReviewAnalytics() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        $analytics = $this->roastUlasanModel->getReviewAnalytics($currentUser['id'], $startDate, $endDate);
        
        response(200, [
            'message' => 'Review analytics retrieved successfully',
            'data' => $analytics
        ]);
    }

    public function getProductComparison() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $comparison = $this->roastUlasanModel->getProductComparisonByReviews($currentUser['id']);
        
        response(200, [
            'message' => 'Product comparison by reviews retrieved successfully',
            'data' => $comparison
        ]);
    }

    public function getReviewInsights() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $insights = $this->roastUlasanModel->getReviewInsights($currentUser['id']);
        
        response(200, [
            'message' => 'Review insights retrieved successfully',
            'data' => $insights
        ]);
    }

    public function getMonthlyReviewSummary() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $months = $_GET['months'] ?? 12;
        $summary = $this->roastUlasanModel->getMonthlyReviewSummary($currentUser['id'], $months);
        
        response(200, [
            'message' => "Monthly review summary for last {$months} months retrieved successfully",
            'data' => $summary
        ]);
    }

    public function getReviewOverview() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $statistics = $this->roastUlasanModel->getReviewStatistics($currentUser['id']);
        $productRatings = $this->roastUlasanModel->getProductRatings($currentUser['id']);
        $recentReviews = $this->roastUlasanModel->getRecentReviews($currentUser['id'], 5);
        $topRatedProducts = $this->roastUlasanModel->getTopRatedProducts($currentUser['id'], 3);
        $insights = $this->roastUlasanModel->getReviewInsights($currentUser['id']);
        $trends = $this->roastUlasanModel->getReviewTrends($currentUser['id'], 7);

        response(200, [
            'message' => 'Review overview retrieved successfully',
            'data' => [
                'statistics' => $statistics,
                'product_ratings' => $productRatings,
                'recent_reviews' => $recentReviews,
                'top_rated_products' => $topRatedProducts,
                'insights' => $insights,
                'weekly_trends' => $trends
            ]
        ]);
    }

    public function getReviewById($reviewId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $review = $this->roastUlasanModel->getReviewById($reviewId, $currentUser['id']);
        
        if (!$review) {
            response(404, ['error' => 'Review not found or access denied']);
            return;
        }
        
        response(200, [
            'message' => 'Review details retrieved successfully',
            'data' => $review
        ]);
    }

    private function getCurrentUser() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return null;
        }

        $token = $matches[1];
        $decoded = json_decode(base64_decode($token), true);

        if (!$decoded || $decoded['exp'] < time()) {
            return null;
        }

        return $decoded;
    }
}