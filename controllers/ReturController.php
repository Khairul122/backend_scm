<?php
class ReturController {
    private $returModel;

    public function __construct() {
        $this->returModel = new ReturModel();
    }

    public function getAllRetur() {
        $status = $_GET['status'] ?? null;
        $userId = $_GET['user_id'] ?? null;
        
        if ($status) {
            $retur = $this->returModel->getReturByStatus($status);
        } elseif ($userId) {
            $retur = $this->returModel->getReturByUser($userId);
        } else {
            $retur = $this->returModel->getAllRetur();
        }
        
        response(200, ['data' => $retur]);
    }

    public function getReturById($id) {
        $retur = $this->returModel->getReturById($id);
        
        if (!$retur) {
            response(404, ['error' => 'Return request not found']);
            return;
        }
        
        response(200, ['data' => $retur]);
    }

    public function createRetur() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateReturInput($input)) {
            response(400, ['error' => 'Invalid input data']);
            return;
        }

        $validation = $this->returModel->validateReturRequest($input['pesanan_id'], $input['user_id']);
        
        if (!$validation) {
            response(400, ['error' => 'Invalid order or user']);
            return;
        }

        if ($validation['status_pesanan'] !== 'delivered') {
            response(400, ['error' => 'Order must be delivered before return request']);
            return;
        }

        if ($validation['days_since_order'] > 7) {
            response(400, ['error' => 'Return request must be made within 7 days of order']);
            return;
        }

        if ($validation['existing_retur'] > 0) {
            response(400, ['error' => 'Return request already exists for this order']);
            return;
        }

        $returId = $this->returModel->createRetur($input);
        
        if ($returId) {
            $retur = $this->returModel->getReturById($returId);
            response(201, ['message' => 'Return request created successfully', 'data' => $retur]);
        } else {
            response(500, ['error' => 'Failed to create return request']);
        }
    }

    public function updateRetur($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->returModel->getReturById($id)) {
            response(404, ['error' => 'Return request not found']);
            return;
        }

        if ($this->returModel->updateRetur($id, $input)) {
            $retur = $this->returModel->getReturById($id);
            response(200, ['message' => 'Return request updated successfully', 'data' => $retur]);
        } else {
            response(500, ['error' => 'Failed to update return request']);
        }
    }

    public function deleteRetur($id) {
        if (!$this->returModel->getReturById($id)) {
            response(404, ['error' => 'Return request not found']);
            return;
        }

        if ($this->returModel->deleteRetur($id)) {
            response(200, ['message' => 'Return request deleted successfully']);
        } else {
            response(500, ['error' => 'Failed to delete return request']);
        }
    }

    public function updateReturStatus($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['status']) || !in_array($input['status'], ['pending', 'approved', 'rejected', 'completed'])) {
            response(400, ['error' => 'Invalid status']);
            return;
        }

        if (!$this->returModel->getReturById($id)) {
            response(404, ['error' => 'Return request not found']);
            return;
        }

        if ($this->returModel->updateRetur($id, ['status_retur' => $input['status']])) {
            response(200, ['message' => 'Return status updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update return status']);
        }
    }

    public function searchRetur() {
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            response(400, ['error' => 'Search query required']);
            return;
        }

        $retur = $this->returModel->searchRetur($query);
        response(200, ['data' => $retur]);
    }

    public function getReturStats() {
        $stats = $this->returModel->getReturStats();
        response(200, ['data' => $stats]);
    }

    public function getReturPolicy() {
        $policy = $this->returModel->getReturPolicy();
        
        response(200, [
            'message' => 'Current return policy configuration',
            'data' => $policy
        ]);
    }

    public function getReturCompliance() {
        $compliance = $this->returModel->getReturCompliance();
        
        response(200, [
            'message' => 'Return policy compliance monitoring',
            'data' => $compliance
        ]);
    }

    public function getReturAnalytics() {
        $stats = $this->returModel->getReturStats();
        $compliance = $this->returModel->getReturCompliance();
        $produk = $this->returModel->getReturByProduk();
        $reasons = $this->returModel->getReturReasons();
        $trends = $this->returModel->getReturTrends(30);
        
        response(200, [
            'message' => 'Comprehensive return analytics',
            'data' => [
                'overview' => $stats,
                'compliance_monitoring' => $compliance,
                'product_analysis' => $produk,
                'common_reasons' => $reasons,
                'trends' => $trends
            ]
        ]);
    }

    public function getReturByProduk() {
        $produk = $this->returModel->getReturByProduk();
        
        response(200, [
            'message' => 'Return analysis by product',
            'data' => $produk
        ]);
    }

    public function getReturTrends() {
        $days = $_GET['days'] ?? 30;
        $trends = $this->returModel->getReturTrends($days);
        
        response(200, [
            'message' => "Return trends for last {$days} days",
            'data' => $trends
        ]);
    }

    public function getOutdatedRetur() {
        $days = $_GET['days'] ?? 30;
        $retur = $this->returModel->getOutdatedRetur($days);
        
        response(200, [
            'message' => "Outdated pending returns older than {$days} days",
            'data' => $retur
        ]);
    }

    public function bulkUpdateStatus() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['ids']) || !isset($input['status']) || !is_array($input['ids'])) {
            response(400, ['error' => 'Invalid input data']);
            return;
        }

        if (!in_array($input['status'], ['approved', 'rejected', 'completed'])) {
            response(400, ['error' => 'Invalid status']);
            return;
        }

        if ($this->returModel->bulkUpdateStatus($input['ids'], $input['status'])) {
            response(200, [
                'message' => 'Bulk status update completed successfully',
                'updated_count' => count($input['ids'])
            ]);
        } else {
            response(500, ['error' => 'Failed to update return statuses']);
        }
    }

    public function validateReturEligibility() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['pesanan_id']) || !isset($input['user_id'])) {
            response(400, ['error' => 'Order ID and User ID required']);
            return;
        }

        $validation = $this->returModel->validateReturRequest($input['pesanan_id'], $input['user_id']);
        
        if (!$validation) {
            response(404, ['error' => 'Order not found']);
            return;
        }

        $eligible = true;
        $reasons = [];

        if ($validation['status_pesanan'] !== 'delivered') {
            $eligible = false;
            $reasons[] = 'Order must be delivered';
        }

        if ($validation['days_since_order'] > 7) {
            $eligible = false;
            $reasons[] = 'Return window (7 days) has expired';
        }

        if ($validation['existing_retur'] > 0) {
            $eligible = false;
            $reasons[] = 'Return request already exists';
        }

        response(200, [
            'eligible' => $eligible,
            'reasons' => $reasons,
            'order_details' => $validation
        ]);
    }

    private function validateReturInput($input) {
        $required = ['pesanan_id', 'user_id', 'alasan'];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return false;
            }
        }

        if (!is_numeric($input['pesanan_id']) || !is_numeric($input['user_id'])) {
            return false;
        }

        if (strlen($input['alasan']) < 10) {
            return false;
        }

        return true;
    }
}