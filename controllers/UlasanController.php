<?php
class UlasanController {
    private $ulasanModel;

    public function __construct() {
        $this->ulasanModel = new UlasanModel();
    }

    public function getAllUlasan() {
        $produkId = $_GET['produk_id'] ?? null;
        $userId = $_GET['user_id'] ?? null;
        $rating = $_GET['rating'] ?? null;
        
        if ($produkId) {
            $ulasan = $this->ulasanModel->getUlasanByProduk($produkId);
        } elseif ($userId) {
            $ulasan = $this->ulasanModel->getUlasanByUser($userId);
        } elseif ($rating) {
            $ulasan = $this->ulasanModel->getUlasanByRating($rating);
        } else {
            $ulasan = $this->ulasanModel->getAllUlasan();
        }
        
        response(200, ['data' => $ulasan]);
    }

    public function getUlasanById($id) {
        $ulasan = $this->ulasanModel->getUlasanById($id);
        
        if (!$ulasan) {
            response(404, ['error' => 'Review not found']);
            return;
        }
        
        response(200, ['data' => $ulasan]);
    }

    public function createUlasan() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateUlasanInput($input)) {
            response(400, ['error' => 'Invalid input data']);
            return;
        }

        $ulasanId = $this->ulasanModel->createUlasan($input);
        
        if ($ulasanId) {
            $ulasan = $this->ulasanModel->getUlasanById($ulasanId);
            response(201, ['message' => 'Review created successfully', 'data' => $ulasan]);
        } else {
            response(500, ['error' => 'Failed to create review']);
        }
    }

    public function updateUlasan($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->ulasanModel->getUlasanById($id)) {
            response(404, ['error' => 'Review not found']);
            return;
        }

        if ($this->ulasanModel->updateUlasan($id, $input)) {
            $ulasan = $this->ulasanModel->getUlasanById($id);
            response(200, ['message' => 'Review updated successfully', 'data' => $ulasan]);
        } else {
            response(500, ['error' => 'Failed to update review']);
        }
    }

    public function deleteUlasan($id) {
        if (!$this->ulasanModel->getUlasanById($id)) {
            response(404, ['error' => 'Review not found']);
            return;
        }

        if ($this->ulasanModel->deleteUlasan($id)) {
            response(200, ['message' => 'Review deleted successfully']);
        } else {
            response(500, ['error' => 'Failed to delete review']);
        }
    }

    public function searchUlasan() {
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            response(400, ['error' => 'Search query required']);
            return;
        }

        $ulasan = $this->ulasanModel->searchUlasan($query);
        response(200, ['data' => $ulasan]);
    }

    public function getUlasanStats() {
        $stats = $this->ulasanModel->getUlasanStats();
        response(200, ['data' => $stats]);
    }

    public function getReviewQuality() {
        $quality = $this->ulasanModel->getReviewQuality();
        
        response(200, [
            'message' => 'Review quality analysis',
            'data' => $quality
        ]);
    }

    public function getFakeReviews() {
        $fake = $this->ulasanModel->getFakeReviews();
        
        response(200, [
            'message' => 'Detected fake reviews requiring removal',
            'data' => $fake
        ]);
    }

    public function getInappropriateReviews() {
        $inappropriate = $this->ulasanModel->getInappropriateReviews();
        
        response(200, [
            'message' => 'Inappropriate reviews requiring moderation',
            'data' => $inappropriate
        ]);
    }

    public function getReviewTrends() {
        $days = $_GET['days'] ?? 30;
        $trends = $this->ulasanModel->getReviewTrends($days);
        
        response(200, [
            'message' => "Review trends for last {$days} days",
            'data' => $trends
        ]);
    }

    public function getProdukRatings() {
        $ratings = $this->ulasanModel->getProdukRatings();
        
        response(200, [
            'message' => 'Product ratings analysis',
            'data' => $ratings
        ]);
    }

    public function getSuspiciousUsers() {
        $users = $this->ulasanModel->getSuspiciousUsers();
        
        response(200, [
            'message' => 'Suspicious users for review monitoring',
            'data' => $users
        ]);
    }

    public function getRecentReviews() {
        $limit = $_GET['limit'] ?? 10;
        $reviews = $this->ulasanModel->getRecentReviews($limit);
        
        response(200, [
            'message' => "Latest {$limit} reviews",
            'data' => $reviews
        ]);
    }

    public function bulkDeleteReviews() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['ids']) || !is_array($input['ids'])) {
            response(400, ['error' => 'Review IDs array required']);
            return;
        }

        if ($this->ulasanModel->bulkDeleteReviews($input['ids'])) {
            response(200, [
                'message' => 'Bulk delete completed successfully',
                'deleted_count' => count($input['ids'])
            ]);
        } else {
            response(500, ['error' => 'Failed to delete reviews']);
        }
    }

    public function getReviewModeration() {
        $stats = $this->ulasanModel->getUlasanStats();
        $quality = $this->ulasanModel->getReviewQuality();
        $fake = $this->ulasanModel->getFakeReviews();
        $inappropriate = $this->ulasanModel->getInappropriateReviews();
        $suspicious = $this->ulasanModel->getSuspiciousUsers();
        
        response(200, [
            'message' => 'Comprehensive review moderation dashboard',
            'data' => [
                'overview' => $stats,
                'quality_analysis' => $quality,
                'fake_reviews' => $fake,
                'inappropriate_reviews' => $inappropriate,
                'suspicious_users' => $suspicious
            ]
        ]);
    }

    public function cleanupFakeReviews() {
        $fake = $this->ulasanModel->getFakeReviews();
        $ids = array_column($fake, 'id');
        
        if (empty($ids)) {
            response(200, ['message' => 'No fake reviews found to cleanup']);
            return;
        }

        if ($this->ulasanModel->bulkDeleteReviews($ids)) {
            response(200, [
                'message' => 'Fake reviews cleanup completed',
                'deleted_count' => count($ids)
            ]);
        } else {
            response(500, ['error' => 'Failed to cleanup fake reviews']);
        }
    }

    public function moderateInappropriate() {
        $inappropriate = $this->ulasanModel->getInappropriateReviews();
        
        foreach ($inappropriate as $review) {
            $cleanComment = $this->cleanInappropriateContent($review['komentar']);
            $this->ulasanModel->updateUlasan($review['id'], ['komentar' => $cleanComment]);
        }
        
        response(200, [
            'message' => 'Inappropriate content moderated successfully',
            'moderated_count' => count($inappropriate)
        ]);
    }

    private function validateUlasanInput($input) {
        $required = ['pesanan_id', 'produk_id', 'user_id', 'rating'];
        
        foreach ($required as $field) {
            if (!isset($input[$field])) {
                return false;
            }
        }

        if (!is_numeric($input['pesanan_id']) || !is_numeric($input['produk_id']) || !is_numeric($input['user_id'])) {
            return false;
        }

        if (!is_numeric($input['rating']) || $input['rating'] < 1 || $input['rating'] > 5) {
            return false;
        }

        return true;
    }

    private function cleanInappropriateContent($content) {
        $badWords = ['bodoh', 'tolol', 'jelek', 'buruk', 'sampah', 'anjing', 'babi', 'fuck', 'shit'];
        
        foreach ($badWords as $word) {
            $content = str_ireplace($word, str_repeat('*', strlen($word)), $content);
        }
        
        return $content;
    }
}