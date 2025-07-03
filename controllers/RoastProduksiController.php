<?php
class RoastProduksiController {
    private $roastProduksiModel;

    public function __construct() {
        $this->roastProduksiModel = new RoastProduksiModel();
    }

    public function getAvailableGreenBeanBatches() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied. Roasting role required.']);
            return;
        }

        $jenisKopi = $_GET['jenis_kopi'] ?? null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $availableOnly = $_GET['available_only'] ?? false;
        
        if ($jenisKopi) {
            $batches = $this->roastProduksiModel->getBatchesByJenisKopi($jenisKopi);
        } elseif ($startDate && $endDate) {
            $batches = $this->roastProduksiModel->getBatchesByDateRange($startDate, $endDate);
        } elseif ($availableOnly) {
            $batches = $this->roastProduksiModel->getAvailableBatchesOnly();
        } else {
            $batches = $this->roastProduksiModel->getAvailableGreenBeanBatches();
        }
        
        response(200, [
            'message' => 'Available green bean batches for roasting',
            'data' => $batches
        ]);
    }

    public function getBatchById($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $batch = $this->roastProduksiModel->getBatchById($id);
        
        if (!$batch) {
            response(404, ['error' => 'Batch not found']);
            return;
        }
        
        response(200, ['data' => $batch]);
    }

    public function updateRoastingInformation($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateRoastingInfo($input)) {
            response(400, ['error' => 'Invalid roasting information']);
            return;
        }

        $batch = $this->roastProduksiModel->getBatchById($id);
        if (!$batch || $batch['status'] !== 'selesai') {
            response(404, ['error' => 'Batch not found or not available for roasting']);
            return;
        }

        if ($this->roastProduksiModel->updateRoastingInformation($id, $input)) {
            response(200, ['message' => 'Roasting information updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update roasting information']);
        }
    }

    public function updateYields($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['yield_percentage']) || !is_numeric($input['yield_percentage']) || 
            $input['yield_percentage'] <= 0 || $input['yield_percentage'] > 100) {
            response(400, ['error' => 'Valid yield percentage (1-100) required']);
            return;
        }

        $batch = $this->roastProduksiModel->getBatchById($id);
        if (!$batch || $batch['status'] !== 'selesai') {
            response(404, ['error' => 'Batch not found or not available for roasting']);
            return;
        }

        if ($this->roastProduksiModel->updateYields($id, $input)) {
            response(200, ['message' => 'Batch yields updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update yields']);
        }
    }

    public function markAsRoasted($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $batch = $this->roastProduksiModel->getBatchById($id);
        if (!$batch || $batch['status'] !== 'selesai') {
            response(404, ['error' => 'Batch not found or not available for roasting']);
            return;
        }

        if ($this->roastProduksiModel->markAsRoasted($id, $input)) {
            response(200, ['message' => 'Batch marked as roasted successfully']);
        } else {
            response(500, ['error' => 'Failed to mark batch as roasted']);
        }
    }

    public function searchAvailableBatches() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            response(400, ['error' => 'Search query required']);
            return;
        }

        $batches = $this->roastProduksiModel->searchAvailableBatches($query);
        response(200, ['data' => $batches]);
    }

    public function getGreenBeanStats() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $stats = $this->roastProduksiModel->getGreenBeanStats();
        response(200, [
            'message' => 'Green bean inventory statistics',
            'data' => $stats
        ]);
    }

    public function getFreshnessBuckets() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $buckets = $this->roastProduksiModel->getFreshnessBuckets();
        
        response(200, [
            'message' => 'Green bean freshness analysis',
            'data' => $buckets
        ]);
    }

    public function getPriceRangeAnalysis() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $analysis = $this->roastProduksiModel->getPriceRangeAnalysis();
        
        response(200, [
            'message' => 'Price range analysis by coffee type',
            'data' => $analysis
        ]);
    }

    public function getRecentlyAvailable() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $limit = $_GET['limit'] ?? 10;
        $batches = $this->roastProduksiModel->getRecentlyAvailable($limit);
        
        response(200, [
            'message' => "Recently available green bean batches",
            'data' => $batches
        ]);
    }

    public function getPremiumBatches() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $minPrice = $_GET['min_price'] ?? 25000;
        $batches = $this->roastProduksiModel->getPremiumBatches($minPrice);
        
        response(200, [
            'message' => "Premium green bean batches (≥{$minPrice}/kg)",
            'data' => $batches
        ]);
    }

    public function getRoastingSuitability() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $suitability = $this->roastProduksiModel->getRoastingSuitability();
        
        response(200, [
            'message' => 'Green bean roasting suitability analysis',
            'data' => $suitability
        ]);
    }

    public function getOriginAnalysis() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $origins = $this->roastProduksiModel->getOriginAnalysis();
        
        response(200, [
            'message' => 'Green bean origin and terroir analysis',
            'data' => $origins
        ]);
    }

    public function getInventoryTurnover() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $days = $_GET['days'] ?? 30;
        $turnover = $this->roastProduksiModel->getInventoryTurnover($days);
        
        response(200, [
            'message' => "Green bean inventory turnover for last {$days} days",
            'data' => $turnover
        ]);
    }

    public function getQualityGrades() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $grades = $this->roastProduksiModel->getQualityGrades();
        
        response(200, [
            'message' => 'Green bean quality grade distribution',
            'data' => $grades
        ]);
    }

    public function getGreenBeanOverview() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $stats = $this->roastProduksiModel->getGreenBeanStats();
        $freshness = $this->roastProduksiModel->getFreshnessBuckets();
        $prices = $this->roastProduksiModel->getPriceRangeAnalysis();
        $suitability = $this->roastProduksiModel->getRoastingSuitability();
        $quality = $this->roastProduksiModel->getQualityGrades();
        $recent = $this->roastProduksiModel->getRecentlyAvailable(5);
        
        response(200, [
            'message' => 'Comprehensive green bean overview for roasting',
            'data' => [
                'inventory_stats' => $stats,
                'freshness_analysis' => $freshness,
                'price_analysis' => $prices,
                'roasting_suitability' => $suitability,
                'quality_grades' => $quality,
                'recent_availability' => $recent
            ]
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

    private function validateRoastingInfo($input) {
        if (isset($input['roast_level']) && !in_array($input['roast_level'], 
            ['light', 'medium-light', 'medium', 'medium-dark', 'dark', 'espresso'])) {
            return false;
        }

        if (isset($input['yield_percentage']) && 
            (!is_numeric($input['yield_percentage']) || 
             $input['yield_percentage'] <= 0 || 
             $input['yield_percentage'] > 100)) {
            return false;
        }

        if (isset($input['roasted_date']) && 
            !strtotime($input['roasted_date'])) {
            return false;
        }

        return true;
    }
}