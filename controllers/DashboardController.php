<?php
class DashboardController {
    private $dashboardModel;

    public function __construct() {
        $this->dashboardModel = new DashboardModel();
    }

    public function getDashboard() {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            response(401, ['error' => 'Unauthorized']);
        }

        $userId = $user['user_id'];
        $role = $user['role'];

        switch ($role) {
            case 'admin':
                $stats = $this->dashboardModel->getAdminStats();
                break;
            case 'pengepul':
                $stats = $this->dashboardModel->getPengepulStats($userId);
                break;
            case 'roasting':
                $stats = $this->dashboardModel->getRoastingStats($userId);
                break;
            case 'penjual':
                $stats = $this->dashboardModel->getPenjualStats($userId);
                break;
            case 'pembeli':
                $stats = $this->dashboardModel->getPembeliStats($userId);
                break;
            default:
                response(403, ['error' => 'Invalid role']);
        }

        $recentOrders = $this->dashboardModel->getRecentOrders($userId, $role);

        response(200, [
            'role' => $role,
            'stats' => $stats,
            'recent_orders' => $recentOrders
        ]);
    }

    public function getAdminDashboard() {
        $user = $this->getCurrentUser();
        
        if (!$user || $user['role'] !== 'admin') {
            response(403, ['error' => 'Admin access required']);
        }

        $stats = $this->dashboardModel->getAdminStats();
        $recentOrders = $this->dashboardModel->getRecentOrders(null, 'admin', 10);

        response(200, [
            'message' => 'Admin dashboard data',
            'stats' => $stats,
            'recent_orders' => $recentOrders
        ]);
    }

    public function getPengepulDashboard() {
        $user = $this->getCurrentUser();
        
        if (!$user || $user['role'] !== 'pengepul') {
            response(403, ['error' => 'Pengepul access required']);
        }

        $stats = $this->dashboardModel->getPengepulStats($user['user_id']);
        $recentOrders = $this->dashboardModel->getRecentOrders($user['user_id'], 'pengepul');

        response(200, [
            'message' => 'Pengepul dashboard data',
            'stats' => $stats,
            'recent_orders' => $recentOrders
        ]);
    }

    public function getRoastingDashboard() {
        $user = $this->getCurrentUser();
        
        if (!$user || $user['role'] !== 'roasting') {
            response(403, ['error' => 'Roasting access required']);
        }

        $stats = $this->dashboardModel->getRoastingStats($user['user_id']);
        $recentOrders = $this->dashboardModel->getRecentOrders($user['user_id'], 'roasting');

        response(200, [
            'message' => 'Roasting dashboard data',
            'stats' => $stats,
            'recent_orders' => $recentOrders
        ]);
    }

    public function getPenjualDashboard() {
        $user = $this->getCurrentUser();
        
        if (!$user || $user['role'] !== 'penjual') {
            response(403, ['error' => 'Penjual access required']);
        }

        $stats = $this->dashboardModel->getPenjualStats($user['user_id']);
        $recentOrders = $this->dashboardModel->getRecentOrders($user['user_id'], 'penjual');

        response(200, [
            'message' => 'Penjual dashboard data',
            'stats' => $stats,
            'recent_orders' => $recentOrders
        ]);
    }

    public function getPembeliDashboard() {
        $user = $this->getCurrentUser();
        
        if (!$user || $user['role'] !== 'pembeli') {
            response(403, ['error' => 'Pembeli access required']);
        }

        $stats = $this->dashboardModel->getPembeliStats($user['user_id']);
        $recentOrders = $this->dashboardModel->getRecentOrders($user['user_id'], 'pembeli');

        response(200, [
            'message' => 'Pembeli dashboard data',
            'stats' => $stats,
            'recent_orders' => $recentOrders
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
}