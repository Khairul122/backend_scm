<?php
class RoastGudangController {
    private $roastGudangModel;

    public function __construct() {
        $this->roastGudangModel = new RoastGudangModel();
    }

    public function getAllRoastedStock() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied. Roasting role required.']);
            return;
        }

        $location = $_GET['location'] ?? null;
        $produkId = $_GET['produk_id'] ?? null;
        $penjualId = $currentUser['id'];
        
        if ($location) {
            $stock = $this->roastGudangModel->getRoastedStockByLocation($location, $penjualId);
        } elseif ($produkId) {
            $stock = $this->roastGudangModel->getRoastedStockByProduk($produkId, $penjualId);
        } else {
            $stock = $this->roastGudangModel->getAllRoastedStock($penjualId);
        }
        
        response(200, ['data' => $stock]);
    }

    public function getRoastedStockById($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $stock = $this->roastGudangModel->getRoastedStockById($id, $currentUser['id']);
        
        if (!$stock) {
            response(404, ['error' => 'Roasted stock entry not found or access denied']);
            return;
        }
        
        response(200, ['data' => $stock]);
    }

    public function createRoastedStock() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateRoastedStockInput($input)) {
            response(400, ['error' => 'Invalid input data']);
            return;
        }

        $stockId = $this->roastGudangModel->createRoastedStock($input, $currentUser['id']);
        
        if ($stockId) {
            $stock = $this->roastGudangModel->getRoastedStockById($stockId, $currentUser['id']);
            response(201, ['message' => 'Roasted coffee inventory added successfully', 'data' => $stock]);
        } else {
            response(500, ['error' => 'Failed to add roasted coffee inventory']);
        }
    }

    public function updateRoastedStock($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->roastGudangModel->checkRoastedStockOwnership($id, $currentUser['id'])) {
            response(404, ['error' => 'Stock entry not found or access denied']);
            return;
        }

        if ($this->roastGudangModel->updateRoastedStock($id, $input, $currentUser['id'])) {
            $stock = $this->roastGudangModel->getRoastedStockById($id, $currentUser['id']);
            response(200, ['message' => 'Roasted stock updated successfully', 'data' => $stock]);
        } else {
            response(500, ['error' => 'Failed to update roasted stock']);
        }
    }

    public function transferRoastedStock($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['new_location']) || empty($input['new_location'])) {
            response(400, ['error' => 'New location required']);
            return;
        }

        if (!$this->roastGudangModel->checkRoastedStockOwnership($id, $currentUser['id'])) {
            response(404, ['error' => 'Stock entry not found or access denied']);
            return;
        }

        if ($this->roastGudangModel->transferRoastedStock($id, $input['new_location'], $currentUser['id'])) {
            response(200, ['message' => 'Roasted stock transferred successfully']);
        } else {
            response(500, ['error' => 'Failed to transfer roasted stock']);
        }
    }

    public function searchRoastedStock() {
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

        $stock = $this->roastGudangModel->searchRoastedStock($query, $currentUser['id']);
        response(200, ['data' => $stock]);
    }

    public function getRoastedStockLevels() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $levels = $this->roastGudangModel->getRoastedStockLevels($currentUser['id']);
        
        response(200, [
            'message' => 'Current roasted coffee stock levels',
            'data' => $levels
        ]);
    }

    public function getRoastedStockByLocation() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $locations = $this->roastGudangModel->getRoastedStockAnalysisByLocation($currentUser['id']);
        
        response(200, [
            'message' => 'Roasted stock monitoring by location',
            'data' => $locations
        ]);
    }

    public function getLowRoastedStock() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $threshold = $_GET['threshold'] ?? 5;
        $items = $this->roastGudangModel->getLowRoastedStock($threshold, $currentUser['id']);
        
        response(200, [
            'message' => "Roasted products with stock below {$threshold} units",
            'data' => $items
        ]);
    }

    public function addRoastedInventoryFromBatch() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateBatchInventoryInput($input)) {
            response(400, ['error' => 'Invalid input data']);
            return;
        }

        $stockId = $this->roastGudangModel->addRoastedInventoryFromBatch(
            $input['batch_id'],
            $input['produk_id'],
            $input['roasted_weight'],
            $input['location'] ?? 'Roasting Storage',
            $currentUser['id']
        );
        
        if ($stockId) {
            $stock = $this->roastGudangModel->getRoastedStockById($stockId, $currentUser['id']);
            response(201, [
                'message' => 'Roasted inventory added from batch successfully',
                'data' => $stock
            ]);
        } else {
            response(500, ['error' => 'Failed to add roasted inventory from batch']);
        }
    }

    public function updateQuantityAfterSale($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['sold_quantity']) || !is_numeric($input['sold_quantity']) || $input['sold_quantity'] <= 0) {
            response(400, ['error' => 'Valid sold quantity required']);
            return;
        }

        if (!$this->roastGudangModel->checkRoastedStockOwnership($id, $currentUser['id'])) {
            response(404, ['error' => 'Stock entry not found or access denied']);
            return;
        }

        if ($this->roastGudangModel->updateQuantityAfterSale($id, $input['sold_quantity'], $currentUser['id'])) {
            response(200, ['message' => 'Stock quantity updated after sale successfully']);
        } else {
            response(500, ['error' => 'Failed to update stock quantity. Insufficient stock or invalid operation.']);
        }
    }

    public function updateQuantityAfterRoasting($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['roasted_quantity']) || !is_numeric($input['roasted_quantity']) || $input['roasted_quantity'] <= 0) {
            response(400, ['error' => 'Valid roasted quantity required']);
            return;
        }

        if (!$this->roastGudangModel->checkRoastedStockOwnership($id, $currentUser['id'])) {
            response(404, ['error' => 'Stock entry not found or access denied']);
            return;
        }

        if ($this->roastGudangModel->updateQuantityAfterRoasting($id, $input['roasted_quantity'], $currentUser['id'])) {
            response(200, ['message' => 'Stock quantity updated after roasting successfully']);
        } else {
            response(500, ['error' => 'Failed to update stock quantity after roasting']);
        }
    }

    public function getRoastedStockAging() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $aging = $this->roastGudangModel->getRoastedStockAging($currentUser['id']);
        
        response(200, [
            'message' => 'Roasted coffee stock aging analysis',
            'data' => $aging
        ]);
    }

    public function getRoastedStockValuation() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $valuation = $this->roastGudangModel->getRoastedStockValuation($currentUser['id']);
        
        response(200, [
            'message' => 'Roasted stock valuation by location and product',
            'data' => $valuation
        ]);
    }

    public function getRoastTypeAnalysis() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $analysis = $this->roastGudangModel->getRoastTypeAnalysis($currentUser['id']);
        
        response(200, [
            'message' => 'Roast type distribution analysis',
            'data' => $analysis
        ]);
    }

    public function getPackagingSizeDistribution() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $distribution = $this->roastGudangModel->getPackagingSizeDistribution($currentUser['id']);
        
        response(200, [
            'message' => 'Packaging size distribution analysis',
            'data' => $distribution
        ]);
    }

    public function getRoastedStockAlerts() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $alerts = $this->roastGudangModel->getRoastedStockAlerts($currentUser['id']);
        
        response(200, [
            'message' => 'Roasted stock alerts and warnings',
            'data' => $alerts
        ]);
    }

    public function bulkTransferRoastedStock() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['ids']) || !isset($input['new_location']) || !is_array($input['ids'])) {
            response(400, ['error' => 'Stock IDs and new location required']);
            return;
        }

        if ($this->roastGudangModel->bulkTransferRoastedStock($input['ids'], $input['new_location'], $currentUser['id'])) {
            response(200, [
                'message' => 'Bulk transfer completed successfully',
                'transferred_count' => count($input['ids'])
            ]);
        } else {
            response(500, ['error' => 'Failed to transfer roasted stock items']);
        }
    }

    public function getRoastedWarehouseOverview() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $levels = $this->roastGudangModel->getRoastedStockLevels($currentUser['id']);
        $locations = $this->roastGudangModel->getRoastedStockAnalysisByLocation($currentUser['id']);
        $lowStock = $this->roastGudangModel->getLowRoastedStock(5, $currentUser['id']);
        $alerts = $this->roastGudangModel->getRoastedStockAlerts($currentUser['id']);
        $aging = $this->roastGudangModel->getRoastedStockAging($currentUser['id']);
        $roastTypes = $this->roastGudangModel->getRoastTypeAnalysis($currentUser['id']);
        $packaging = $this->roastGudangModel->getPackagingSizeDistribution($currentUser['id']);
        
        response(200, [
            'message' => 'Comprehensive roasted coffee warehouse overview',
            'data' => [
                'stock_levels' => $levels,
                'location_analysis' => $locations,
                'low_stock_items' => $lowStock,
                'stock_alerts' => $alerts,
                'aging_analysis' => $aging,
                'roast_type_distribution' => $roastTypes,
                'packaging_distribution' => $packaging
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

    private function validateRoastedStockInput($input) {
        $required = ['produk_id', 'jumlah_stok', 'lokasi_gudang'];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return false;
            }
        }

        if (!is_numeric($input['produk_id'])) {
            return false;
        }

        if (!is_numeric($input['jumlah_stok']) || $input['jumlah_stok'] <= 0) {
            return false;
        }

        if (isset($input['batch_id']) && !is_numeric($input['batch_id'])) {
            return false;
        }

        return true;
    }

    private function validateBatchInventoryInput($input) {
        $required = ['batch_id', 'produk_id', 'roasted_weight'];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return false;
            }
        }

        if (!is_numeric($input['batch_id']) || !is_numeric($input['produk_id'])) {
            return false;
        }

        if (!is_numeric($input['roasted_weight']) || $input['roasted_weight'] <= 0) {
            return false;
        }

        return true;
    }
}