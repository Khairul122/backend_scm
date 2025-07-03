<?php
class PenjualPesananController {
    private $penjualPesananModel;

    public function __construct() {
        $this->penjualPesananModel = new PenjualPesananModel();
    }

    public function getAllOrders() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied. Seller role required.']);
            return;
        }

        $status = $_GET['status'] ?? null;
        $orders = $this->penjualPesananModel->getOrdersForSeller($currentUser['id'], $status);
        
        response(200, [
            'message' => 'Orders retrieved successfully',
            'data' => $orders
        ]);
    }

    public function getOrderById($orderId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $order = $this->penjualPesananModel->getOrderById($orderId, $currentUser['id']);
        
        if (!$order) {
            response(404, ['error' => 'Order not found or access denied']);
            return;
        }

        $orderDetails = $this->penjualPesananModel->getOrderDetails($orderId, $currentUser['id']);
        
        response(200, [
            'message' => 'Order details retrieved successfully',
            'data' => [
                'order' => $order,
                'details' => $orderDetails
            ]
        ]);
    }

    public function updateOrderStatus($orderId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['status']) || empty($input['status'])) {
            response(400, ['error' => 'Status is required']);
            return;
        }

        $allowedStatuses = ['confirmed', 'processed', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($input['status'], $allowedStatuses)) {
            response(400, ['error' => 'Invalid status. Allowed: ' . implode(', ', $allowedStatuses)]);
            return;
        }

        if ($this->penjualPesananModel->updateOrderStatus($orderId, $input['status'], $currentUser['id'])) {
            response(200, ['message' => 'Order status updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update order status']);
        }
    }

    public function updateShippingInfo($orderId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['resi_pengiriman']) || empty($input['resi_pengiriman'])) {
            response(400, ['error' => 'Tracking number (resi_pengiriman) is required']);
            return;
        }

        if ($this->penjualPesananModel->updateShippingInfo($orderId, $input['resi_pengiriman'], $currentUser['id'])) {
            response(200, ['message' => 'Shipping information updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update shipping information']);
        }
    }

    public function getOrderStatistics() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $statistics = $this->penjualPesananModel->getOrderStatistics($currentUser['id']);
        
        response(200, [
            'message' => 'Order statistics retrieved successfully',
            'data' => $statistics
        ]);
    }

    public function getRecentOrders() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $limit = $_GET['limit'] ?? 10;
        $orders = $this->penjualPesananModel->getRecentOrders($currentUser['id'], $limit);
        
        response(200, [
            'message' => 'Recent orders retrieved successfully',
            'data' => $orders
        ]);
    }

    public function getTopCustomers() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $limit = $_GET['limit'] ?? 10;
        $customers = $this->penjualPesananModel->getTopCustomers($currentUser['id'], $limit);
        
        response(200, [
            'message' => 'Top customers retrieved successfully',
            'data' => $customers
        ]);
    }

    public function getTopSellingProducts() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $limit = $_GET['limit'] ?? 10;
        $products = $this->penjualPesananModel->getTopSellingProducts($currentUser['id'], $limit);
        
        response(200, [
            'message' => 'Top selling products retrieved successfully',
            'data' => $products
        ]);
    }

    public function getOrdersByDateRange() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        if (!$startDate || !$endDate) {
            response(400, ['error' => 'Both start_date and end_date are required']);
            return;
        }

        $orders = $this->penjualPesananModel->getOrdersByDateRange($currentUser['id'], $startDate, $endDate);
        
        response(200, [
            'message' => 'Orders by date range retrieved successfully',
            'data' => $orders
        ]);
    }

    public function getRevenueAnalysis() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $period = $_GET['period'] ?? 'month';
        if (!in_array($period, ['week', 'month'])) {
            response(400, ['error' => 'Invalid period. Use "week" or "month"']);
            return;
        }

        $analysis = $this->penjualPesananModel->getRevenueAnalysis($currentUser['id'], $period);
        
        response(200, [
            'message' => "Revenue analysis by {$period} retrieved successfully",
            'data' => $analysis
        ]);
    }

    public function getOrderTrends() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $days = $_GET['days'] ?? 30;
        $trends = $this->penjualPesananModel->getOrderTrends($currentUser['id'], $days);
        
        response(200, [
            'message' => "Order trends for last {$days} days retrieved successfully",
            'data' => $trends
        ]);
    }

    public function getPaymentMethodAnalysis() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $analysis = $this->penjualPesananModel->getPaymentMethodAnalysis($currentUser['id']);
        
        response(200, [
            'message' => 'Payment method analysis retrieved successfully',
            'data' => $analysis
        ]);
    }

    public function getShippingAnalysis() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $analysis = $this->penjualPesananModel->getShippingAnalysis($currentUser['id']);
        
        response(200, [
            'message' => 'Shipping analysis retrieved successfully',
            'data' => $analysis
        ]);
    }

    public function searchOrders() {
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

        $orders = $this->penjualPesananModel->searchOrders($query, $currentUser['id']);
        
        response(200, [
            'message' => 'Search results retrieved successfully',
            'data' => $orders
        ]);
    }

    public function getOrderAlerts() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $alerts = $this->penjualPesananModel->getOrderAlerts($currentUser['id']);
        
        response(200, [
            'message' => 'Order alerts retrieved successfully',
            'data' => $alerts
        ]);
    }

    public function getOrderOverview() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $statistics = $this->penjualPesananModel->getOrderStatistics($currentUser['id']);
        $recentOrders = $this->penjualPesananModel->getRecentOrders($currentUser['id'], 5);
        $topCustomers = $this->penjualPesananModel->getTopCustomers($currentUser['id'], 5);
        $topProducts = $this->penjualPesananModel->getTopSellingProducts($currentUser['id'], 5);
        $alerts = $this->penjualPesananModel->getOrderAlerts($currentUser['id']);
        $trends = $this->penjualPesananModel->getOrderTrends($currentUser['id'], 7);
        $paymentAnalysis = $this->penjualPesananModel->getPaymentMethodAnalysis($currentUser['id']);

        response(200, [
            'message' => 'Order overview retrieved successfully',
            'data' => [
                'statistics' => $statistics,
                'recent_orders' => $recentOrders,
                'top_customers' => $topCustomers,
                'top_products' => $topProducts,
                'alerts' => $alerts,
                'weekly_trends' => $trends,
                'payment_analysis' => $paymentAnalysis
            ]
        ]);
    }

    public function confirmOrder($orderId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        if ($this->penjualPesananModel->updateOrderStatus($orderId, 'confirmed', $currentUser['id'])) {
            response(200, ['message' => 'Order confirmed successfully']);
        } else {
            response(500, ['error' => 'Failed to confirm order']);
        }
    }

    public function processOrder($orderId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        if ($this->penjualPesananModel->updateOrderStatus($orderId, 'processed', $currentUser['id'])) {
            response(200, ['message' => 'Order marked as processed successfully']);
        } else {
            response(500, ['error' => 'Failed to process order']);
        }
    }

    public function shipOrder($orderId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['resi_pengiriman']) || empty($input['resi_pengiriman'])) {
            response(400, ['error' => 'Tracking number (resi_pengiriman) is required']);
            return;
        }

        if ($this->penjualPesananModel->updateShippingInfo($orderId, $input['resi_pengiriman'], $currentUser['id'])) {
            response(200, ['message' => 'Order shipped successfully']);
        } else {
            response(500, ['error' => 'Failed to ship order']);
        }
    }

    public function deliverOrder($orderId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        if ($this->penjualPesananModel->updateOrderStatus($orderId, 'delivered', $currentUser['id'])) {
            response(200, ['message' => 'Order marked as delivered successfully']);
        } else {
            response(500, ['error' => 'Failed to mark order as delivered']);
        }
    }

    public function cancelOrder($orderId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        if ($this->penjualPesananModel->updateOrderStatus($orderId, 'cancelled', $currentUser['id'])) {
            response(200, ['message' => 'Order cancelled successfully']);
        } else {
            response(500, ['error' => 'Failed to cancel order']);
        }
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
}