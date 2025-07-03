<?php
class PembeliController {
    private $pembeliModel;

    public function __construct() {
        $this->pembeliModel = new PembeliModel();
    }

    public function register() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateRegistrationInput($input)) {
            response(400, ['error' => 'Invalid input data. Required: nama_lengkap, email, no_telepon, alamat, password']);
            return;
        }

        $userId = $this->pembeliModel->register($input);
        
        if ($userId) {
            $profile = $this->pembeliModel->getProfile($userId);
            response(201, [
                'message' => 'Registration successful',
                'data' => $profile
            ]);
        } else {
            response(400, ['error' => 'Registration failed. Email or phone number may already exist.']);
        }
    }

    public function getProfile() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied. Customer role required.']);
            return;
        }

        $profile = $this->pembeliModel->getProfile($currentUser['id']);
        
        if (!$profile) {
            response(404, ['error' => 'Profile not found']);
            return;
        }
        
        response(200, [
            'message' => 'Profile retrieved successfully',
            'data' => $profile
        ]);
    }

    public function updateProfile() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($this->pembeliModel->updateProfile($currentUser['id'], $input)) {
            $profile = $this->pembeliModel->getProfile($currentUser['id']);
            response(200, [
                'message' => 'Profile updated successfully',
                'data' => $profile
            ]);
        } else {
            response(500, ['error' => 'Failed to update profile. Email or phone number may already exist.']);
        }
    }

    public function changePassword() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validatePasswordInput($input)) {
            response(400, ['error' => 'Invalid input data. Required: current_password, new_password']);
            return;
        }

        if ($this->pembeliModel->changePassword($currentUser['id'], $input['current_password'], $input['new_password'])) {
            response(200, ['message' => 'Password changed successfully']);
        } else {
            response(400, ['error' => 'Failed to change password. Current password may be incorrect.']);
        }
    }

    public function requestAccountDeactivation() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $reason = $input['reason'] ?? 'No reason provided';

        if ($this->pembeliModel->requestAccountDeactivation($currentUser['id'], $reason)) {
            response(200, ['message' => 'Account deactivation request submitted successfully']);
        } else {
            response(500, ['error' => 'Failed to submit deactivation request']);
        }
    }

    public function getOrderHistory() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $limit = $_GET['limit'] ?? 10;
        $page = $_GET['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $orders = $this->pembeliModel->getOrderHistory($currentUser['id'], $limit, $offset);
        
        response(200, [
            'message' => 'Order history retrieved successfully',
            'data' => $orders,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'offset' => $offset
            ]
        ]);
    }

    public function getShippingAddresses() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $addresses = $this->pembeliModel->getShippingAddresses($currentUser['id']);
        
        response(200, [
            'message' => 'Shipping addresses retrieved successfully',
            'data' => $addresses
        ]);
    }

    public function addShippingAddress() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateAddressInput($input)) {
            response(400, ['error' => 'Invalid input data. Required: label, nama_penerima, no_telepon, province_id, city_id, alamat_lengkap, kode_pos']);
            return;
        }

        $addressId = $this->pembeliModel->addShippingAddress($currentUser['id'], $input);
        
        if ($addressId) {
            response(201, ['message' => 'Shipping address added successfully', 'address_id' => $addressId]);
        } else {
            response(500, ['error' => 'Failed to add shipping address']);
        }
    }

    public function updateShippingAddress($addressId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($this->pembeliModel->updateShippingAddress($currentUser['id'], $addressId, $input)) {
            response(200, ['message' => 'Shipping address updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update shipping address or address not found']);
        }
    }

    public function deleteShippingAddress($addressId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        if ($this->pembeliModel->deleteShippingAddress($currentUser['id'], $addressId)) {
            response(200, ['message' => 'Shipping address deleted successfully']);
        } else {
            response(500, ['error' => 'Failed to delete shipping address or address not found']);
        }
    }

    public function setDefaultAddress($addressId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        if ($this->pembeliModel->setDefaultAddress($currentUser['id'], $addressId)) {
            response(200, ['message' => 'Default address set successfully']);
        } else {
            response(500, ['error' => 'Failed to set default address or address not found']);
        }
    }

    public function getWishlist() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $limit = $_GET['limit'] ?? 20;
        $page = $_GET['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $wishlist = $this->pembeliModel->getWishlist($currentUser['id'], $limit, $offset);
        
        response(200, [
            'message' => 'Wishlist retrieved successfully',
            'data' => $wishlist,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'offset' => $offset
            ]
        ]);
    }

    public function getAccountSummary() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $summary = $this->pembeliModel->getAccountSummary($currentUser['id']);
        
        response(200, [
            'message' => 'Account summary retrieved successfully',
            'data' => $summary
        ]);
    }

    public function getRecentActivity() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $limit = $_GET['limit'] ?? 10;
        $activities = $this->pembeliModel->getRecentActivity($currentUser['id'], $limit);
        
        response(200, [
            'message' => 'Recent activities retrieved successfully',
            'data' => $activities
        ]);
    }

    public function getOrderStatistics() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $statistics = $this->pembeliModel->getOrderStatistics($currentUser['id']);
        
        response(200, [
            'message' => 'Order statistics retrieved successfully',
            'data' => $statistics
        ]);
    }

    public function getDashboard() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $profile = $this->pembeliModel->getProfile($currentUser['id']);
        $summary = $this->pembeliModel->getAccountSummary($currentUser['id']);
        $recentOrders = $this->pembeliModel->getOrderHistory($currentUser['id'], 5, 0);
        $recentActivity = $this->pembeliModel->getRecentActivity($currentUser['id'], 5);
        $orderStatistics = $this->pembeliModel->getOrderStatistics($currentUser['id']);
        $addresses = $this->pembeliModel->getShippingAddresses($currentUser['id']);

        response(200, [
            'message' => 'Dashboard data retrieved successfully',
            'data' => [
                'profile' => $profile,
                'summary' => $summary,
                'recent_orders' => $recentOrders,
                'recent_activity' => $recentActivity,
                'order_statistics' => $orderStatistics,
                'shipping_addresses' => $addresses
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

    private function validateRegistrationInput($input) {
        $required = ['nama_lengkap', 'email', 'no_telepon', 'alamat', 'password'];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return false;
            }
        }

        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (strlen($input['password']) < 6) {
            return false;
        }

        if (!preg_match('/^[0-9+\-\s]+$/', $input['no_telepon'])) {
            return false;
        }

        return true;
    }

    private function validatePasswordInput($input) {
        $required = ['current_password', 'new_password'];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return false;
            }
        }

        if (strlen($input['new_password']) < 6) {
            return false;
        }

        return true;
    }

    private function validateAddressInput($input) {
        $required = ['label', 'nama_penerima', 'no_telepon', 'province_id', 'city_id', 'alamat_lengkap', 'kode_pos'];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return false;
            }
        }

        if (!is_numeric($input['province_id']) || !is_numeric($input['city_id'])) {
            return false;
        }

        if (!preg_match('/^[0-9+\-\s]+$/', $input['no_telepon'])) {
            return false;
        }

        if (!preg_match('/^[0-9]{5}$/', $input['kode_pos'])) {
            return false;
        }

        return true;
    }
}