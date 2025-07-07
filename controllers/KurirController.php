<?php
class KurirController
{
    private $kurirModel;

    public function __construct()
    {
        $this->kurirModel = new KurirModel();
    }

    public function getAllKurir()
    {
        $status = $_GET['status'] ?? null;
        $id = $_GET['id'] ?? null;

        if ($id) {
            return $this->getKurirById($id);
        }

        if ($status === 'active') {
            $kurir = $this->kurirModel->getActiveKurir();
        } else {
            $kurir = $this->kurirModel->getAllKurir();
        }

        header('Content-Type: application/json');
        echo json_encode(['status' => 200, 'data' => $kurir]);
    }

    public function getKurirById($id = null)
    {
        if (!$id) {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? $_GET['id'] ?? null;
        }

        if (!$id) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'ID required']);
            return;
        }

        $kurir = $this->kurirModel->getKurirById($id);

        if (!$kurir) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['status' => 404, 'error' => 'Courier not found']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode(['status' => 200, 'data' => $kurir]);
    }

    public function createKurir()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$this->validateKurirInput($input)) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Invalid input data']);
            return;
        }

        if (!$this->kurirModel->validateKurirCode($input['kode'])) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Courier code not supported by Raja Ongkir API']);
            return;
        }

        if ($this->kurirModel->checkKurirExists($input['kode'])) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Courier code already exists']);
            return;
        }

        $kurirId = $this->kurirModel->createKurir($input);

        if ($kurirId) {
            $kurir = $this->kurirModel->getKurirById($kurirId);
            header('Content-Type: application/json');
            http_response_code(201);
            echo json_encode(['status' => 201, 'message' => 'Courier added successfully', 'data' => $kurir]);
        } else {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['status' => 500, 'error' => 'Failed to add courier']);
        }
    }

    public function updateKurir($id = null)
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$id) {
            $id = $input['id'] ?? $_GET['id'] ?? null;
        }

        if (!$id) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'ID required']);
            return;
        }

        if (!$this->kurirModel->getKurirById($id)) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['status' => 404, 'error' => 'Courier not found']);
            return;
        }

        if (isset($input['kode'])) {
            if (!$this->kurirModel->validateKurirCode($input['kode'])) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['status' => 400, 'error' => 'Courier code not supported by Raja Ongkir API']);
                return;
            }

            if ($this->kurirModel->checkKurirExists($input['kode'], $id)) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['status' => 400, 'error' => 'Courier code already exists']);
                return;
            }
        }

        if ($this->kurirModel->updateKurir($id, $input)) {
            $kurir = $this->kurirModel->getKurirById($id);
            header('Content-Type: application/json');
            echo json_encode(['status' => 200, 'message' => 'Courier updated successfully', 'data' => $kurir]);
        } else {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['status' => 500, 'error' => 'Failed to update courier']);
        }
    }

    public function deleteKurir($id = null)
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$id) {
            $id = $input['id'] ?? $_GET['id'] ?? null;
        }

        if (!$id) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'ID required']);
            return;
        }

        if (!$this->kurirModel->getKurirById($id)) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['status' => 404, 'error' => 'Courier not found']);
            return;
        }

        if ($this->kurirModel->deleteKurir($id)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 200, 'message' => 'Courier deleted successfully']);
        } else {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['status' => 500, 'error' => 'Failed to delete courier']);
        }
    }

    public function updateKurirStatus($id = null)
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$id) {
            $id = $input['id'] ?? $_GET['id'] ?? null;
        }

        if (!$id) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'ID required']);
            return;
        }

        if (!isset($input['status']) || !in_array($input['status'], ['aktif', 'nonaktif'])) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Invalid status']);
            return;
        }

        if (!$this->kurirModel->getKurirById($id)) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['status' => 404, 'error' => 'Courier not found']);
            return;
        }

        if ($this->kurirModel->updateKurirStatus($id, $input['status'])) {
            $kurir = $this->kurirModel->getKurirById($id);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 200,
                'message' => 'Courier status updated successfully',
                'data' => $kurir
            ]);
        } else {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['status' => 500, 'error' => 'Failed to update courier status']);
        }
    }

    public function searchKurir()
    {
        $query = $_GET['q'] ?? '';

        if (empty($query)) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Search query required']);
            return;
        }

        $kurir = $this->kurirModel->searchKurir($query);
        header('Content-Type: application/json');
        echo json_encode(['status' => 200, 'data' => $kurir]);
    }

    public function getKurirStats()
    {
        $stats = $this->kurirModel->getKurirStats();
        header('Content-Type: application/json');
        echo json_encode(['status' => 200, 'data' => $stats]);
    }

    public function getKurirPerformance()
    {
        $performance = $this->kurirModel->getKurirPerformance();

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 200,
            'message' => 'Courier performance monitoring',
            'data' => $performance
        ]);
    }

    public function getKurirDeliveryTime()
    {
        $deliveryTime = $this->kurirModel->getKurirDeliveryTime();

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 200,
            'message' => 'Courier delivery time analysis',
            'data' => $deliveryTime
        ]);
    }

    public function getKurirCostAnalysis()
    {
        $costAnalysis = $this->kurirModel->getKurirCostAnalysis();

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 200,
            'message' => 'Courier cost analysis',
            'data' => $costAnalysis
        ]);
    }

    public function importKurirFromApi()
    {
        $imported = $this->kurirModel->importKurirFromApi();

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 200,
            'message' => 'Couriers imported from Raja Ongkir API',
            'imported_count' => $imported
        ]);
    }

    public function getPoorPerformingKurir()
    {
        $threshold = $_GET['threshold'] ?? 70;
        $kurir = $this->kurirModel->getPoorPerformingKurir($threshold);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 200,
            'message' => "Couriers with success rate below {$threshold}%",
            'data' => $kurir
        ]);
    }

    public function getKurirTrends()
    {
        $days = $_GET['days'] ?? 30;
        $trends = $this->kurirModel->getKurirTrends($days);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 200,
            'message' => "Courier usage trends for last {$days} days",
            'data' => $trends
        ]);
    }

    public function getKurirAnalytics()
    {
        $stats = $this->kurirModel->getKurirStats();
        $performance = $this->kurirModel->getKurirPerformance();
        $deliveryTime = $this->kurirModel->getKurirDeliveryTime();
        $costAnalysis = $this->kurirModel->getKurirCostAnalysis();
        $usageStats = $this->kurirModel->getKurirUsageStats();

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 200,
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

    public function getKurirUsageStats()
    {
        $usageStats = $this->kurirModel->getKurirUsageStats();

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 200,
            'message' => 'Courier usage statistics',
            'data' => $usageStats
        ]);
    }

    public function getAvailableKurirCodes()
    {
        $supportedCouriers = [
            ['kode' => 'jne', 'nama' => 'JNE'],
            ['kode' => 'pos', 'nama' => 'POS Indonesia'],
            ['kode' => 'tiki', 'nama' => 'TIKI'],
            ['kode' => 'rpx', 'nama' => 'RPX'],
            ['kode' => 'esl', 'nama' => 'ESL Express'],
            ['kode' => 'pcp', 'nama' => 'PCP Express'],
            ['kode' => 'jet', 'nama' => 'JET Express'],
            ['kode' => 'dse', 'nama' => 'DSE'],
            ['kode' => 'first', 'nama' => 'First Logistics'],
            ['kode' => 'ncs', 'nama' => 'NCS'],
            ['kode' => 'star', 'nama' => 'Star Cargo']
        ];

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 200,
            'message' => 'Available courier codes supported by Raja Ongkir API',
            'data' => $supportedCouriers
        ]);
    }

    public function bulkUpdateStatus()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['ids']) || !isset($input['status']) || !is_array($input['ids'])) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Invalid input data']);
            return;
        }

        if (!in_array($input['status'], ['aktif', 'nonaktif'])) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Invalid status']);
            return;
        }

        $updated = 0;
        foreach ($input['ids'] as $id) {
            if ($this->kurirModel->updateKurirStatus($id, $input['status'])) {
                $updated++;
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 200,
            'message' => 'Bulk status update completed',
            'updated_count' => $updated,
            'total_requested' => count($input['ids'])
        ]);
    }

    public function cleanupPoorPerformers()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $threshold = $input['threshold'] ?? 50;

        $poorPerformers = $this->kurirModel->getPoorPerformingKurir($threshold);
        $cleaned = 0;

        foreach ($poorPerformers as $kurir) {
            if ($this->kurirModel->updateKurirStatus($kurir['id'], 'nonaktif')) {
                $cleaned++;
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 200,
            'message' => 'Poor performing couriers deactivated',
            'deactivated_count' => $cleaned,
            'threshold_used' => $threshold
        ]);
    }

    private function validateKurirInput($input)
    {
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