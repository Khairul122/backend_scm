<?php
class OngkirCacheController {
    private $cacheModel;

    public function __construct() {
        $this->cacheModel = new OngkirCacheModel();
    }

    public function getAllCache() {
        $status = $_GET['status'] ?? null;
        $courier = $_GET['courier'] ?? null;
        
        if ($status === 'expired') {
            $cache = $this->cacheModel->getExpiredCache();
        } elseif ($courier) {
            $cache = $this->cacheModel->getCacheByCourier();
        } else {
            $cache = $this->cacheModel->getAllCache();
        }
        
        response(200, ['data' => $cache]);
    }

    public function getCacheById($id) {
        $cache = $this->cacheModel->getCacheById($id);
        
        if (!$cache) {
            response(404, ['error' => 'Cache not found']);
            return;
        }
        
        response(200, ['data' => $cache]);
    }

    public function getCacheByParams() {
        $origin = $_GET['origin'] ?? null;
        $destination = $_GET['destination'] ?? null;
        $weight = $_GET['weight'] ?? null;
        $courier = $_GET['courier'] ?? null;
        
        if (!$origin || !$destination || !$weight || !$courier) {
            response(400, ['error' => 'Origin, destination, weight, and courier parameters required']);
            return;
        }

        $cache = $this->cacheModel->getCacheByParams($origin, $destination, $weight, $courier);
        response(200, ['data' => $cache]);
    }

    public function getActiveCache() {
        $origin = $_GET['origin'] ?? null;
        $destination = $_GET['destination'] ?? null;
        $weight = $_GET['weight'] ?? null;
        $courier = $_GET['courier'] ?? null;
        
        if (!$origin || !$destination || !$weight || !$courier) {
            response(400, ['error' => 'Origin, destination, weight, and courier parameters required']);
            return;
        }

        $cache = $this->cacheModel->getActiveCache($origin, $destination, $weight, $courier);
        
        if (empty($cache)) {
            response(404, ['error' => 'No active cache found for these parameters']);
            return;
        }
        
        response(200, ['data' => $cache]);
    }

    public function generateCache() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateCacheInput($input)) {
            response(400, ['error' => 'Invalid input data']);
            return;
        }

        $existingCache = $this->cacheModel->getActiveCache(
            $input['origin'],
            $input['destination'],
            $input['weight'],
            $input['courier']
        );

        if (!empty($existingCache)) {
            response(200, [
                'message' => 'Cache already exists and is active',
                'data' => $existingCache
            ]);
            return;
        }

        $cached = $this->cacheModel->generateCacheFromApi(
            $input['origin'],
            $input['destination'],
            $input['weight'],
            $input['courier']
        );
        
        if ($cached !== false) {
            response(201, [
                'message' => 'Cache generated successfully from API',
                'cached_count' => $cached
            ]);
        } else {
            response(500, ['error' => 'Failed to generate cache from API']);
        }
    }

    public function updateCache($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->cacheModel->getCacheById($id)) {
            response(404, ['error' => 'Cache not found']);
            return;
        }

        if ($this->cacheModel->updateCache($id, $input)) {
            $cache = $this->cacheModel->getCacheById($id);
            response(200, ['message' => 'Cache updated successfully', 'data' => $cache]);
        } else {
            response(500, ['error' => 'Failed to update cache']);
        }
    }

    public function refreshCache($id) {
        $cache = $this->cacheModel->getCacheById($id);
        
        if (!$cache) {
            response(404, ['error' => 'Cache not found']);
            return;
        }

        if ($this->cacheModel->updateCache($id, ['refresh' => true])) {
            response(200, ['message' => 'Cache refreshed successfully']);
        } else {
            response(500, ['error' => 'Failed to refresh cache']);
        }
    }

    public function deleteCache($id) {
        if (!$this->cacheModel->getCacheById($id)) {
            response(404, ['error' => 'Cache not found']);
            return;
        }

        if ($this->cacheModel->deleteCache($id)) {
            response(200, ['message' => 'Cache deleted successfully']);
        } else {
            response(500, ['error' => 'Failed to delete cache']);
        }
    }

    public function clearExpiredCache() {
        $cleared = $this->cacheModel->clearExpiredCache();
        
        if ($cleared !== false) {
            response(200, [
                'message' => 'Expired cache cleared successfully',
                'cleared_count' => $cleared
            ]);
        } else {
            response(500, ['error' => 'Failed to clear expired cache']);
        }
    }

    public function clearAllCache() {
        $cleared = $this->cacheModel->clearAllCache();
        
        if ($cleared !== false) {
            response(200, [
                'message' => 'All cache cleared successfully',
                'cleared_count' => $cleared
            ]);
        } else {
            response(500, ['error' => 'Failed to clear all cache']);
        }
    }

    public function clearOldCache() {
        $input = json_decode(file_get_contents('php://input'), true);
        $days = $input['days'] ?? 30;
        
        $cleared = $this->cacheModel->clearOldCache($days);
        
        if ($cleared !== false) {
            response(200, [
                'message' => "Cache older than {$days} days cleared successfully",
                'cleared_count' => $cleared
            ]);
        } else {
            response(500, ['error' => 'Failed to clear old cache']);
        }
    }

    public function getCacheStats() {
        $stats = $this->cacheModel->getCacheStats();
        response(200, ['data' => $stats]);
    }

    public function getCacheHitRate() {
        $days = $_GET['days'] ?? 30;
        $hitRate = $this->cacheModel->getCacheHitRate($days);
        
        response(200, [
            'message' => "Cache hit rate for last {$days} days",
            'data' => $hitRate
        ]);
    }

    public function getApiUsageStats() {
        $days = $_GET['days'] ?? 30;
        $usage = $this->cacheModel->getApiUsageStats($days);
        
        response(200, [
            'message' => "API usage statistics for last {$days} days",
            'data' => $usage
        ]);
    }

    public function getPopularRoutes() {
        $limit = $_GET['limit'] ?? 10;
        $routes = $this->cacheModel->getPopularRoutes($limit);
        
        response(200, [
            'message' => "Top {$limit} popular shipping routes",
            'data' => $routes
        ]);
    }

    public function getCacheByCourier() {
        $courier = $this->cacheModel->getCacheByCourier();
        
        response(200, [
            'message' => 'Cache statistics by courier',
            'data' => $courier
        ]);
    }

    public function getExpiredCache() {
        $expired = $this->cacheModel->getExpiredCache();
        
        response(200, [
            'message' => 'Expired cache entries',
            'data' => $expired
        ]);
    }

    public function refreshExpiredCache() {
        $refreshed = $this->cacheModel->refreshExpiredCache();
        
        response(200, [
            'message' => 'Expired cache refreshed from API',
            'refreshed_count' => $refreshed
        ]);
    }

    public function searchCache() {
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            response(400, ['error' => 'Search query required']);
            return;
        }

        $cache = $this->cacheModel->searchCache($query);
        response(200, ['data' => $cache]);
    }

    public function getCacheAnalytics() {
        $stats = $this->cacheModel->getCacheStats();
        $hitRate = $this->cacheModel->getCacheHitRate(30);
        $usage = $this->cacheModel->getApiUsageStats(30);
        $popular = $this->cacheModel->getPopularRoutes(10);
        $courier = $this->cacheModel->getCacheByCourier();
        $size = $this->cacheModel->getCacheSize();
        
        response(200, [
            'message' => 'Comprehensive cache analytics',
            'data' => [
                'overview' => $stats,
                'hit_rate' => $hitRate,
                'api_usage' => $usage,
                'popular_routes' => $popular,
                'courier_analysis' => $courier,
                'cache_size' => $size
            ]
        ]);
    }

    public function optimizeCache() {
        if ($this->cacheModel->optimizeCache()) {
            response(200, ['message' => 'Cache table optimized successfully']);
        } else {
            response(500, ['error' => 'Failed to optimize cache table']);
        }
    }

    public function getCacheSize() {
        $size = $this->cacheModel->getCacheSize();
        
        response(200, [
            'message' => 'Cache storage information',
            'data' => $size
        ]);
    }

    public function bulkRefresh() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['ids']) || !is_array($input['ids'])) {
            response(400, ['error' => 'Cache IDs array required']);
            return;
        }

        $refreshed = 0;
        foreach ($input['ids'] as $id) {
            if ($this->cacheModel->updateCache($id, ['refresh' => true])) {
                $refreshed++;
            }
        }

        response(200, [
            'message' => 'Bulk refresh completed',
            'refreshed_count' => $refreshed,
            'total_requested' => count($input['ids'])
        ]);
    }

    public function bulkDelete() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['ids']) || !is_array($input['ids'])) {
            response(400, ['error' => 'Cache IDs array required']);
            return;
        }

        $deleted = 0;
        foreach ($input['ids'] as $id) {
            if ($this->cacheModel->deleteCache($id)) {
                $deleted++;
            }
        }

        response(200, [
            'message' => 'Bulk delete completed',
            'deleted_count' => $deleted,
            'total_requested' => count($input['ids'])
        ]);
    }

    private function validateCacheInput($input) {
        $required = ['origin', 'destination', 'weight', 'courier'];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return false;
            }
        }

        if (!is_numeric($input['origin']) || !is_numeric($input['destination']) || !is_numeric($input['weight'])) {
            return false;
        }

        if (!in_array(strtolower($input['courier']), ['jne', 'pos', 'tiki'])) {
            return false;
        }

        return true;
    }
}