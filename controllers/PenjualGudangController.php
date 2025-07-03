<?php
class PenjualGudangController {
    private $penjualGudangModel;

    public function __construct() {
        $this->penjualGudangModel = new PenjualGudangModel();
    }

    public function getAllInventory() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied. Seller role required.']);
            return;
        }

        $location = $_GET['location'] ?? null;
        $produkId = $_GET['produk_id'] ?? null;
        
        if ($location) {
            $inventory = $this->penjualGudangModel->getInventoryByLocation($location, $currentUser['id']);
        } elseif ($produkId) {
            $inventory = $this->penjualGudangModel->getInventoryByProduct($produkId, $currentUser['id']);
        } else {
            $inventory = $this->penjualGudangModel->getAllInventory($currentUser['id']);
        }
        
        response(200, [
            'message' => 'Inventory retrieved successfully',
            'data' => $inventory
        ]);
    }

    public function getInventoryById($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $inventory = $this->penjualGudangModel->getInventoryById($id, $currentUser['id']);
        
        if (!$inventory) {
            response(404, ['error' => 'Inventory entry not found or access denied']);
            return;
        }
        
        response(200, [
            'message' => 'Inventory details retrieved successfully',
            'data' => $inventory
        ]);
    }

    public function addInventory() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateInventoryInput($input)) {
            response(400, ['error' => 'Invalid input data. Required: produk_id, jumlah_stok, lokasi_gudang']);
            return;
        }

        $inventoryId = $this->penjualGudangModel->addInventory($input, $currentUser['id']);
        
        if ($inventoryId) {
            $inventory = $this->penjualGudangModel->getInventoryById($inventoryId, $currentUser['id']);
            response(201, [
                'message' => 'Inventory added successfully',
                'data' => $inventory
            ]);
        } else {
            response(500, ['error' => 'Failed to add inventory. Product may not exist or access denied.']);
        }
    }

    public function updateInventory($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        if (!$this->penjualGudangModel->checkInventoryOwnership($id, $currentUser['id'])) {
            response(404, ['error' => 'Inventory entry not found or access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($this->penjualGudangModel->updateInventory($id, $input, $currentUser['id'])) {
            $inventory = $this->penjualGudangModel->getInventoryById($id, $currentUser['id']);
            response(200, [
                'message' => 'Inventory updated successfully',
                'data' => $inventory
            ]);
        } else {
            response(500, ['error' => 'Failed to update inventory']);
        }
    }

    public function adjustStockAfterSale() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['produk_id']) || !isset($input['sold_quantity']) || 
            !is_numeric($input['produk_id']) || !is_numeric($input['sold_quantity']) || 
            $input['sold_quantity'] <= 0) {
            response(400, ['error' => 'Valid produk_id and sold_quantity required']);
            return;
        }

        $success = $this->penjualGudangModel->adjustStockAfterSale(
            $input['produk_id'], 
            $input['sold_quantity'], 
            $currentUser['id']
        );

        if ($success) {
            response(200, ['message' => 'Stock adjusted after sale successfully']);
        } else {
            response(500, ['error' => 'Failed to adjust stock. Insufficient inventory available.']);
        }
    }

    public function restockInventory() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateRestockInput($input)) {
            response(400, ['error' => 'Invalid input data. Required: produk_id, quantity, location']);
            return;
        }

        $inventoryId = $this->penjualGudangModel->restockInventory(
            $input['produk_id'],
            $input['quantity'],
            $input['location'],
            $input['batch_id'] ?? null,
            $currentUser['id']
        );

        if ($inventoryId) {
            $inventory = $this->penjualGudangModel->getInventoryById($inventoryId, $currentUser['id']);
            response(201, [
                'message' => 'Inventory restocked successfully',
                'data' => $inventory
            ]);
        } else {
            response(500, ['error' => 'Failed to restock inventory']);
        }
    }

    public function transferInventory($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        if (!$this->penjualGudangModel->checkInventoryOwnership($id, $currentUser['id'])) {
            response(404, ['error' => 'Inventory entry not found or access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['new_location']) || empty($input['new_location'])) {
            response(400, ['error' => 'New location required']);
            return;
        }

        if ($this->penjualGudangModel->transferInventory($id, $input['new_location'], $currentUser['id'])) {
            response(200, ['message' => 'Inventory transferred successfully']);
        } else {
            response(500, ['error' => 'Failed to transfer inventory']);
        }
    }

    public function searchInventory() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            response(400, ['error' => 'Search query required']);
            return;
        }

        $inventory = $this->penjualGudangModel->searchInventory($query, $currentUser['id']);
        
        response(200, [
            'message' => 'Search results retrieved successfully',
            'data' => $inventory
        ]);
    }

    public function getInventoryStatistics() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $statistics = $this->penjualGudangModel->getInventoryStatistics($currentUser['id']);
        
        response(200, [
            'message' => 'Inventory statistics retrieved successfully',
            'data' => $statistics
        ]);
    }

    public function getStockLevels() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $stockLevels = $this->penjualGudangModel->getStockLevels($currentUser['id']);
        
        response(200, [
            'message' => 'Stock levels retrieved successfully',
            'data' => $stockLevels
        ]);
    }

    public function getLocationAnalysis() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $analysis = $this->penjualGudangModel->getLocationAnalysis($currentUser['id']);
        
        response(200, [
            'message' => 'Location analysis retrieved successfully',
            'data' => $analysis
        ]);
    }

    public function getLowStockItems() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $threshold = $_GET['threshold'] ?? 5;
        $items = $this->penjualGudangModel->getLowStockItems($threshold, $currentUser['id']);
        
        response(200, [
            'message' => "Items with stock below {$threshold} units retrieved successfully",
            'data' => $items
        ]);
    }

    public function getInventoryAging() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $aging = $this->penjualGudangModel->getInventoryAging($currentUser['id']);
        
        response(200, [
            'message' => 'Inventory aging analysis retrieved successfully',
            'data' => $aging
        ]);
    }

    public function getInventoryValuation() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $valuation = $this->penjualGudangModel->getInventoryValuation($currentUser['id']);
        
        response(200, [
            'message' => 'Inventory valuation retrieved successfully',
            'data' => $valuation
        ]);
    }

    public function getCategoryStockAnalysis() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $analysis = $this->penjualGudangModel->getCategoryStockAnalysis($currentUser['id']);
        
        response(200, [
            'message' => 'Category stock analysis retrieved successfully',
            'data' => $analysis
        ]);
    }

    public function getInventoryMovements() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $days = $_GET['days'] ?? 30;
        $movements = $this->penjualGudangModel->getInventoryMovements($currentUser['id'], $days);
        
        response(200, [
            'message' => "Inventory movements for last {$days} days retrieved successfully",
            'data' => $movements
        ]);
    }

    public function getStockAlerts() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $alerts = $this->penjualGudangModel->getStockAlerts($currentUser['id']);
        
        response(200, [
            'message' => 'Stock alerts retrieved successfully',
            'data' => $alerts
        ]);
    }

    public function getInventoryOverview() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $statistics = $this->penjualGudangModel->getInventoryStatistics($currentUser['id']);
        $stockLevels = $this->penjualGudangModel->getStockLevels($currentUser['id']);
        $locationAnalysis = $this->penjualGudangModel->getLocationAnalysis($currentUser['id']);
        $lowStockItems = $this->penjualGudangModel->getLowStockItems(5, $currentUser['id']);
        $alerts = $this->penjualGudangModel->getStockAlerts($currentUser['id']);
        $categoryAnalysis = $this->penjualGudangModel->getCategoryStockAnalysis($currentUser['id']);
        $recentMovements = $this->penjualGudangModel->getInventoryMovements($currentUser['id'], 7);

        response(200, [
            'message' => 'Inventory overview retrieved successfully',
            'data' => [
                'statistics' => $statistics,
                'stock_levels' => array_slice($stockLevels, 0, 10),
                'location_analysis' => $locationAnalysis,
                'low_stock_items' => $lowStockItems,
                'alerts' => $alerts,
                'category_analysis' => $categoryAnalysis,
                'recent_movements' => $recentMovements
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

    private function validateInventoryInput($input) {
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

    private function validateRestockInput($input) {
        $required = ['produk_id', 'quantity', 'location'];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return false;
            }
        }

        if (!is_numeric($input['produk_id'])) {
            return false;
        }

        if (!is_numeric($input['quantity']) || $input['quantity'] <= 0) {
            return false;
        }

        if (isset($input['batch_id']) && !is_numeric($input['batch_id'])) {
            return false;
        }

        return true;
    }
}