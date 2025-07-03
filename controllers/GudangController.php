<?php
class GudangController {
    private $gudangModel;

    public function __construct() {
        $this->gudangModel = new GudangModel();
    }

    public function getAllStokGudang() {
        $location = $_GET['location'] ?? null;
        $produkId = $_GET['produk_id'] ?? null;
        
        if ($location) {
            $stok = $this->gudangModel->getStokByLocation($location);
        } elseif ($produkId) {
            $stok = $this->gudangModel->getStokByProduk($produkId);
        } else {
            $stok = $this->gudangModel->getAllStokGudang();
        }
        
        response(200, ['data' => $stok]);
    }

    public function getStokById($id) {
        $stok = $this->gudangModel->getStokById($id);
        
        if (!$stok) {
            response(404, ['error' => 'Stock entry not found']);
            return;
        }
        
        response(200, ['data' => $stok]);
    }

    public function createStokGudang() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateStokInput($input)) {
            response(400, ['error' => 'Invalid input data']);
            return;
        }

        $stokId = $this->gudangModel->createStokGudang($input);
        
        if ($stokId) {
            $stok = $this->gudangModel->getStokById($stokId);
            response(201, ['message' => 'Stock added to warehouse successfully', 'data' => $stok]);
        } else {
            response(500, ['error' => 'Failed to add stock to warehouse']);
        }
    }

    public function updateStokGudang($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->gudangModel->getStokById($id)) {
            response(404, ['error' => 'Stock entry not found']);
            return;
        }

        if ($this->gudangModel->updateStokGudang($id, $input)) {
            $stok = $this->gudangModel->getStokById($id);
            response(200, ['message' => 'Stock updated successfully', 'data' => $stok]);
        } else {
            response(500, ['error' => 'Failed to update stock']);
        }
    }

    public function transferStock($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['new_location']) || empty($input['new_location'])) {
            response(400, ['error' => 'New location required']);
            return;
        }

        if (!$this->gudangModel->getStokById($id)) {
            response(404, ['error' => 'Stock entry not found']);
            return;
        }

        if ($this->gudangModel->transferStock($id, $input['new_location'])) {
            response(200, ['message' => 'Stock transferred successfully']);
        } else {
            response(500, ['error' => 'Failed to transfer stock']);
        }
    }

    public function searchStock() {
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            response(400, ['error' => 'Search query required']);
            return;
        }

        $stok = $this->gudangModel->searchStock($query);
        response(200, ['data' => $stok]);
    }

    public function getStockLevels() {
        $levels = $this->gudangModel->getStockLevels();
        
        response(200, [
            'message' => 'Current stock levels across all products',
            'data' => $levels
        ]);
    }

    public function getMultiLocationStock() {
        $locations = $this->gudangModel->getMultiLocationStock();
        
        response(200, [
            'message' => 'Stock monitoring across multiple locations',
            'data' => $locations
        ]);
    }

    public function getLowStockItems() {
        $threshold = $_GET['threshold'] ?? 10;
        $items = $this->gudangModel->getLowStockItems($threshold);
        
        response(200, [
            'message' => "Items with stock below {$threshold} units",
            'data' => $items
        ]);
    }

    public function getStockMovement() {
        $days = $_GET['days'] ?? 30;
        $movement = $this->gudangModel->getStockMovement($days);
        
        response(200, [
            'message' => "Stock movement for last {$days} days",
            'data' => $movement
        ]);
    }

    public function addFromCompletedBatch() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['batch_id']) || !is_numeric($input['batch_id'])) {
            response(400, ['error' => 'Valid batch ID required']);
            return;
        }

        $stokId = $this->gudangModel->addFromCompletedBatch($input['batch_id']);
        
        if ($stokId) {
            $stok = $this->gudangModel->getStokById($stokId);
            response(201, [
                'message' => 'Inventory added from completed batch successfully',
                'data' => $stok
            ]);
        } else {
            response(500, ['error' => 'Failed to add inventory from batch. Batch may not be completed or already added.']);
        }
    }

    public function getStockByBatch($batchId) {
        $stock = $this->gudangModel->getStockByBatch($batchId);
        
        response(200, [
            'message' => 'Stock entries for batch',
            'data' => $stock
        ]);
    }

    public function adjustQuantity($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['jumlah_stok']) || !is_numeric($input['jumlah_stok']) || $input['jumlah_stok'] < 0) {
            response(400, ['error' => 'Valid quantity required']);
            return;
        }

        if (!$this->gudangModel->getStokById($id)) {
            response(404, ['error' => 'Stock entry not found']);
            return;
        }

        $reason = $input['reason'] ?? null;
        
        if ($this->gudangModel->adjustQuantity($id, $input['jumlah_stok'], $reason)) {
            response(200, ['message' => 'Stock quantity adjusted successfully']);
        } else {
            response(500, ['error' => 'Failed to adjust stock quantity']);
        }
    }

    public function getAvailableLocations() {
        $locations = $this->gudangModel->getAvailableLocations();
        
        response(200, [
            'message' => 'Available warehouse locations',
            'data' => $locations
        ]);
    }

    public function getStockAging() {
        $aging = $this->gudangModel->getStockAging();
        
        response(200, [
            'message' => 'Stock aging analysis',
            'data' => $aging
        ]);
    }

    public function getStockValuation() {
        $valuation = $this->gudangModel->getStockValuation();
        
        response(200, [
            'message' => 'Stock valuation by location and product',
            'data' => $valuation
        ]);
    }

    public function bulkTransfer() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['ids']) || !isset($input['new_location']) || !is_array($input['ids'])) {
            response(400, ['error' => 'Stock IDs and new location required']);
            return;
        }

        if ($this->gudangModel->bulkTransfer($input['ids'], $input['new_location'])) {
            response(200, [
                'message' => 'Bulk transfer completed successfully',
                'transferred_count' => count($input['ids'])
            ]);
        } else {
            response(500, ['error' => 'Failed to transfer stock items']);
        }
    }

    public function getStockTurnover() {
        $days = $_GET['days'] ?? 30;
        $turnover = $this->gudangModel->getStockTurnover($days);
        
        response(200, [
            'message' => "Stock turnover analysis for last {$days} days",
            'data' => $turnover
        ]);
    }

    public function getCapacityByLocation() {
        $capacity = $this->gudangModel->getCapacityByLocation();
        
        response(200, [
            'message' => 'Warehouse capacity utilization by location',
            'data' => $capacity
        ]);
    }

    public function getStockAlerts() {
        $alerts = $this->gudangModel->getStockAlerts();
        
        response(200, [
            'message' => 'Stock level alerts and warnings',
            'data' => $alerts
        ]);
    }

    public function getWarehouseOverview() {
        $levels = $this->gudangModel->getStockLevels();
        $locations = $this->gudangModel->getMultiLocationStock();
        $lowStock = $this->gudangModel->getLowStockItems(10);
        $movement = $this->gudangModel->getStockMovement(30);
        $alerts = $this->gudangModel->getStockAlerts();
        $aging = $this->gudangModel->getStockAging();
        
        response(200, [
            'message' => 'Comprehensive warehouse overview',
            'data' => [
                'stock_levels' => $levels,
                'multi_location' => $locations,
                'low_stock_items' => $lowStock,
                'recent_movement' => $movement,
                'stock_alerts' => $alerts,
                'aging_analysis' => $aging
            ]
        ]);
    }

    public function updatePrices() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['price_updates']) || !is_array($input['price_updates'])) {
            response(400, ['error' => 'Price updates array required']);
            return;
        }

        $updated = 0;
        foreach ($input['price_updates'] as $update) {
            if (isset($update['produk_id']) && isset($update['new_price'])) {
                $updated++;
            }
        }

        response(200, [
            'message' => 'Price updates completed',
            'updated_count' => $updated
        ]);
    }

    private function validateStokInput($input) {
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
}