<?php
class PesananController {
    private $pesananModel;

    public function __construct() {
        $this->pesananModel = new PesananModel();
    }

    public function getAllPesanan() {
        $status = $_GET['status'] ?? null;
        $userId = $_GET['user_id'] ?? null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        if ($status) {
            $pesanan = $this->pesananModel->getPesananByStatus($status);
        } elseif ($userId) {
            $pesanan = $this->pesananModel->getPesananByUser($userId);
        } elseif ($startDate && $endDate) {
            $pesanan = $this->pesananModel->getOrdersByDateRange($startDate, $endDate);
        } else {
            $pesanan = $this->pesananModel->getAllPesanan();
        }
        
        response(200, ['data' => $pesanan]);
    }

    public function getPesananById($id) {
        $pesanan = $this->pesananModel->getPesananById($id);
        
        if (!$pesanan) {
            response(404, ['error' => 'Order not found']);
            return;
        }
        
        $details = $this->pesananModel->getDetailPesanan($id);
        $pesanan['details'] = $details;
        
        response(200, ['data' => $pesanan]);
    }

    public function createPesanan() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validatePesananInput($input)) {
            response(400, ['error' => 'Invalid input data']);
            return;
        }

        $pesananId = $this->pesananModel->createPesanan($input);
        
        if ($pesananId) {
            if (isset($input['details']) && is_array($input['details'])) {
                foreach ($input['details'] as $detail) {
                    $detail['pesanan_id'] = $pesananId;
                    $this->pesananModel->createDetailPesanan($detail);
                }
            }
            
            $pesanan = $this->pesananModel->getPesananById($pesananId);
            response(201, ['message' => 'Order created successfully', 'data' => $pesanan]);
        } else {
            response(500, ['error' => 'Failed to create order']);
        }
    }

    public function updatePesanan($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->pesananModel->getPesananById($id)) {
            response(404, ['error' => 'Order not found']);
            return;
        }

        if ($this->pesananModel->updatePesanan($id, $input)) {
            $pesanan = $this->pesananModel->getPesananById($id);
            response(200, ['message' => 'Order updated successfully', 'data' => $pesanan]);
        } else {
            response(500, ['error' => 'Failed to update order']);
        }
    }

    public function deletePesanan($id) {
        if (!$this->pesananModel->getPesananById($id)) {
            response(404, ['error' => 'Order not found']);
            return;
        }

        if ($this->pesananModel->deletePesanan($id)) {
            response(200, ['message' => 'Order deleted successfully']);
        } else {
            response(500, ['error' => 'Failed to delete order']);
        }
    }

    public function updatePesananStatus($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['status']) || !in_array($input['status'], ['pending', 'confirmed', 'processed', 'shipped', 'delivered', 'cancelled'])) {
            response(400, ['error' => 'Invalid status']);
            return;
        }

        if (!$this->pesananModel->getPesananById($id)) {
            response(404, ['error' => 'Order not found']);
            return;
        }

        if ($this->pesananModel->updatePesananStatus($id, $input['status'])) {
            response(200, ['message' => 'Order status updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update order status']);
        }
    }

    public function getDetailPesanan($id) {
        $details = $this->pesananModel->getDetailPesanan($id);
        response(200, ['data' => $details]);
    }

    public function createDetailPesanan() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateDetailInput($input)) {
            response(400, ['error' => 'Invalid input data']);
            return;
        }

        $detailId = $this->pesananModel->createDetailPesanan($input);
        
        if ($detailId) {
            response(201, ['message' => 'Order detail created successfully', 'id' => $detailId]);
        } else {
            response(500, ['error' => 'Failed to create order detail']);
        }
    }

    public function updateDetailPesanan($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($this->pesananModel->updateDetailPesanan($id, $input)) {
            response(200, ['message' => 'Order detail updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update order detail']);
        }
    }

    public function deleteDetailPesanan($id) {
        if ($this->pesananModel->deleteDetailPesanan($id)) {
            response(200, ['message' => 'Order detail deleted successfully']);
        } else {
            response(500, ['error' => 'Failed to delete order detail']);
        }
    }

    public function searchPesanan() {
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            response(400, ['error' => 'Search query required']);
            return;
        }

        $pesanan = $this->pesananModel->searchPesanan($query);
        response(200, ['data' => $pesanan]);
    }

    public function getPesananStats() {
        $stats = $this->pesananModel->getPesananStats();
        response(200, ['data' => $stats]);
    }

    public function getFraudPatterns() {
        $patterns = $this->pesananModel->getFraudPatterns();
        
        response(200, [
            'message' => 'Fraud pattern detection analysis',
            'data' => $patterns
        ]);
    }

    public function getSuspiciousOrders() {
        $suspicious = $this->pesananModel->getSuspiciousOrders();
        
        response(200, [
            'message' => 'Suspicious orders requiring review',
            'data' => $suspicious
        ]);
    }

    public function getPurchasingPatterns() {
        $patterns = $this->pesananModel->getPurchasingPatterns();
        
        response(200, [
            'message' => 'Customer purchasing pattern analysis',
            'data' => $patterns
        ]);
    }

    public function getRevenueAnalysis() {
        $days = $_GET['days'] ?? 30;
        $analysis = $this->pesananModel->getRevenueAnalysis($days);
        
        response(200, [
            'message' => "Revenue analysis for last {$days} days",
            'data' => $analysis
        ]);
    }

    public function getDisputeOrders() {
        $disputes = $this->pesananModel->getDisputeOrders();
        
        response(200, [
            'message' => 'Orders requiring dispute resolution',
            'data' => $disputes
        ]);
    }

    public function getHighValueOrders() {
        $threshold = $_GET['threshold'] ?? 1000000;
        $orders = $this->pesananModel->getHighValueOrders($threshold);
        
        response(200, [
            'message' => "High value orders above {$threshold}",
            'data' => $orders
        ]);
    }

    public function bulkUpdateStatus() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['ids']) || !isset($input['status']) || !is_array($input['ids'])) {
            response(400, ['error' => 'Invalid input data']);
            return;
        }

        if (!in_array($input['status'], ['confirmed', 'processed', 'shipped', 'delivered', 'cancelled'])) {
            response(400, ['error' => 'Invalid status']);
            return;
        }

        if ($this->pesananModel->bulkUpdateStatus($input['ids'], $input['status'])) {
            response(200, [
                'message' => 'Bulk status update completed',
                'updated_count' => count($input['ids'])
            ]);
        } else {
            response(500, ['error' => 'Failed to update order statuses']);
        }
    }

    public function getTransactionOversight() {
        $stats = $this->pesananModel->getPesananStats();
        $fraud = $this->pesananModel->getFraudPatterns();
        $suspicious = $this->pesananModel->getSuspiciousOrders();
        $disputes = $this->pesananModel->getDisputeOrders();
        $revenue = $this->pesananModel->getRevenueAnalysis(30);
        
        response(200, [
            'message' => 'Comprehensive transaction oversight',
            'data' => [
                'overview' => $stats,
                'fraud_patterns' => $fraud,
                'suspicious_orders' => $suspicious,
                'disputes' => $disputes,
                'revenue_analysis' => $revenue
            ]
        ]);
    }

    public function cancelFraudulentOrder($id) {
        $pesanan = $this->pesananModel->getPesananById($id);
        
        if (!$pesanan) {
            response(404, ['error' => 'Order not found']);
            return;
        }

        if ($this->pesananModel->updatePesananStatus($id, 'cancelled')) {
            response(200, ['message' => 'Fraudulent order cancelled successfully']);
        } else {
            response(500, ['error' => 'Failed to cancel order']);
        }
    }

    private function validatePesananInput($input) {
        $required = ['user_id', 'alamat_pengiriman_id', 'subtotal', 'total', 'metode_pembayaran', 'kurir_kode', 'kurir_service', 'berat_total'];
        
        foreach ($required as $field) {
            if (!isset($input[$field])) {
                return false;
            }
        }

        if (!is_numeric($input['user_id']) || !is_numeric($input['alamat_pengiriman_id'])) {
            return false;
        }

        if (!in_array($input['metode_pembayaran'], ['cod', 'transfer'])) {
            return false;
        }

        return true;
    }

    private function validateDetailInput($input) {
        $required = ['pesanan_id', 'produk_id', 'nama_produk', 'harga', 'jumlah', 'subtotal'];
        
        foreach ($required as $field) {
            if (!isset($input[$field])) {
                return false;
            }
        }

        if (!is_numeric($input['pesanan_id']) || !is_numeric($input['produk_id']) || !is_numeric($input['jumlah'])) {
            return false;
        }

        return true;
    }
}