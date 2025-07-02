<?php
class KurirController {
    private $kurirModel;

    public function __construct() {
        $this->kurirModel = new KurirModel();
    }

    public function getAllKurir() {
        $status = $_GET['status'] ?? null;
        
        if ($status === 'active') {
            $kurir = $this->kurirModel->getActiveKurir();
        } else {
            $kurir = $this->kurirModel->getAllKurir();
        }
        
        response(200, ['data' => $kurir]);
    }

    public function getKurirById($id) {
        $kurir = $this->kurirModel->getKurirById($id);
        
        if (!$kurir) {
            response(404, ['error' => 'Courier not found']);
            return;
        }
        
        response(200, ['data' => $kurir]);
    }

    public function createKurir() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateKurirInput($input)) {
            response(400, ['error' => 'Invalid input data']);
            return;
        }

        if (!$this->kurirModel->validateKurirCode($input['kode'])) {
            response(400, ['error' => 'Courier code not supported by Raja Ongkir API']);
            return;
        }

        if ($this->kurirModel->checkKurirExists($input['kode'])) {
            response(400, ['error' => 'Courier code already exists']);
            return;
        }

        $kurirId = $this->kurirModel->createKurir($input);
        
        if ($kurirId) {
            $kurir = $this->kurirModel->getKurirById($kurirId);
            response(201, ['message' => 'Courier added successfully', 'data' => $kurir]);
        } else {
            response(500, ['error' => 'Failed to add courier']);
        }
    }

    public function updateKurir($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->kurirModel->getKurirById($id)) {
            response(404, ['error' => 'Courier not found']);
            return;
        }

        if (isset($input['kode'])) {
            if (!$this->kurirModel->validateKurirCode($input['kode'])) {
                response(400, ['error' => 'Courier code not supported by Raja Ongkir API']);
                return;
            }

            if ($this->kurirModel->checkKurirExists($input['kode'], $id)) {
                response(400, ['error' => 'Courier code already exists']);
                return;
            }
        }

        if ($this->kurirModel->updateKurir($id, $input)) {
            $kurir = $this->kurirModel->getKurirById($id);
            response(200, ['message' => 'Courier updated successfully', 'data' => $kurir]);
        } else {
            response(500, ['error' => 'Failed to update courier']);
        }
    }

    public function deleteKurir($id) {
        if (!$this->kurirModel->getKurirById($id)) {
            response(404, ['error' => 'Courier not found']);
            return;
        }

        if ($this->kurirModel->deleteKurir($id)) {
            response(200, ['message' => 'Courier deleted successfully']);
        } else {
            response(500, ['error' => 'Failed to delete courier']);
        }
    }

    public function updateKurirStatus($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['status']) || !in_array($input['status'], ['aktif', 'nonaktif'])) {
            response(400, ['error' => 'Invalid status']);
            return;
        }

        if (!$this->kurirModel->getKurirById($id)) {
            response(404, ['error' => 'Courier not found']);
            return;
        }

        if ($this->kurirModel->updateKurirStatus($id, $input['status'])) {
            response(200, ['message' => 'Courier status updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update courier status']);
        }
    }

    public function searchKurir() {
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            response(400, ['error' => 'Search query required']);
            return;
        }

        $kurir = $this->kurirModel->searchKurir($query);
        response(200, ['data' => $kurir]);
    }

    public function getKurirStats() {
        $stats = $this->kurirModel->getKurirStats();
        response(200, ['data' => $stats]);
    }

    public function getKurirPerformance() {
        $performance = $this->kurirModel->getKurirPerformance();
        
        response(200, [
            'message' => 'Courier performance monitoring',
            'data' => $performance
        ]);
    }

    public function getKurirDeliveryTime() {
        $deliveryTime = $this->kurirModel->getKurirDeliveryTime();
        
        response(200, [
            'message' => 'Courier delivery time analysis',
            'data' => $deliveryTime
        ]);
    }

    public function getKurirCostAnalysis() {
        $costAnalysis = $this->kurirModel->getKurirCostAnalysis();
        
        response(200, [
            'message' => 'Courier cost analysis',
            'data' => $costAnalysis
        ]);
    }

    public function importKurirFromApi() {
        $imported = $this->kurirModel->importKurirFromApi();
        
        response(200, [
            'message' => 'Couriers imported from Raja Ongkir API',
            'imported_count' => $imported
        ]);
    }

    public function getPoorPerformingKurir() {
        $threshold = $_GET['threshold'] ?? 70;
        $kurir = $this->kurirModel->getPoorPerformingKurir($threshold);
        
        response(200, [
            'message' => "Couriers with success rate below {$threshold}%",
            'data' => $kurir
        ]);
    }

    public function getKurirTrends() {
        $days = $_GET['days'] ?? 30;
        $trends = $this->kurirModel->getKurirTrends($days);
        
        response(200, [
            'message' => "Courier usage trends for last {$days} days",
            'data' => $trends
        ]);
    }

    public function getKurirAnalytics() {
        $stats = $this->kurirModel->getKurirStats();
        $performance = $this->kurirModel->getKurirPerformance();
        $deliveryTime = $this->kurirModel->getKurirDeliveryTime();
        $costAnalysis = $this->kurirModel->getKurirCostAnalysis();
        $usageStats = $this->kurirModel->getKurirUsageStats();
        
        response(200, [
            'message' => 'Comprehensive courier analytics',
            'data' => [
                'overview' => $stats,
                'performance' => $performance,
                'delivery_time' => $deliveryTime,
                'cost_analysis' => $costAnalysis,
                'usage_statistics' => $usageStats
            ]
        ]);
    }

    public function getKurirUsageStats() {
        $usageStats = $this->kurirModel->getKurirUsageStats();
        
        response(200, [
            'message' => 'Courier usage statistics',
            'data' => $usageStats
        ]);
    }

    public function getAvailableKurirCodes() {
        $supportedCouriers = [
            ['kode' => 'jne', 'nama' => 'JNE'],
            ['kode' => 'pos', 'nama' => 'POS Indonesia'],
            ['kode' => 'tiki', 'nama' => 'TIKI']
        ];
        
        response(200, [
            'message' => 'Available courier codes supported by Raja Ongkir API',
            'data' => $supportedCouriers
        ]);
    }

    public function bulkUpdateStatus() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['ids']) || !isset($input['status']) || !is_array($input['ids'])) {
            response(400, ['error' => 'Invalid input data']);
            return;
        }

        if (!in_array($input['status'], ['aktif', 'nonaktif'])) {
            response(400, ['error' => 'Invalid status']);
            return;
        }

        $updated = 0;
        foreach ($input['ids'] as $id) {
            if ($this->kurirModel->updateKurirStatus($id, $input['status'])) {
                $updated++;
            }
        }

        response(200, [
            'message' => 'Bulk status update completed',
            'updated_count' => $updated,
            'total_requested' => count($input['ids'])
        ]);
    }

    public function cleanupPoorPerformers() {
        $input = json_decode(file_get_contents('php://input'), true);
        $threshold = $input['threshold'] ?? 50;
        
        $poorPerformers = $this->kurirModel->getPoorPerformingKurir($threshold);
        $cleaned = 0;
        
        foreach ($poorPerformers as $kurir) {
            if ($this->kurirModel->updateKurirStatus($kurir['id'], 'nonaktif')) {
                $cleaned++;
            }
        }
        
        response(200, [
            'message' => 'Poor performing couriers deactivated',
            'deactivated_count' => $cleaned,
            'threshold_used' => $threshold
        ]);
    }

    private function validateKurirInput($input) {
        $required = ['kode', 'nama'];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return false;
            }
        }

        if (strlen($input['kode']) < 2 || strlen($input['kode']) > 10) {
            return false;
        }

        if (strlen($input['nama']) < 2 || strlen($input['nama']) > 50) {
            return false;
        }

        return true;
    }
}