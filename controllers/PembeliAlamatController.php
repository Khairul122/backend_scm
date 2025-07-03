<?php
class PembeliAlamatController {
    private $pembeliAlamatModel;

    public function __construct() {
        $this->pembeliAlamatModel = new PembeliAlamatModel();
    }

    public function getAllAddresses() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied. Customer role required.']);
            return;
        }

        $addresses = $this->pembeliAlamatModel->getAllAddresses($currentUser['id']);
        
        response(200, [
            'message' => 'Shipping addresses retrieved successfully',
            'data' => $addresses
        ]);
    }

    public function getAddressById($addressId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $address = $this->pembeliAlamatModel->getAddressById($addressId, $currentUser['id']);
        
        if (!$address) {
            response(404, ['error' => 'Address not found or access denied']);
            return;
        }
        
        response(200, [
            'message' => 'Address details retrieved successfully',
            'data' => $address
        ]);
    }

    public function createAddress() {
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

        if (isset($input['kode_pos']) && !$this->pembeliAlamatModel->validatePostalCode($input['kode_pos'], $input['city_id'])) {
            response(400, ['error' => 'Postal code does not match the selected city']);
            return;
        }

        $addressId = $this->pembeliAlamatModel->createAddress($currentUser['id'], $input);
        
        if ($addressId) {
            $address = $this->pembeliAlamatModel->getAddressById($addressId, $currentUser['id']);
            response(201, [
                'message' => 'Shipping address created successfully',
                'data' => $address
            ]);
        } else {
            response(500, ['error' => 'Failed to create shipping address. Please check province/city combination.']);
        }
    }

    public function updateAddress($addressId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['kode_pos']) && isset($input['city_id'])) {
            if (!$this->pembeliAlamatModel->validatePostalCode($input['kode_pos'], $input['city_id'])) {
                response(400, ['error' => 'Postal code does not match the selected city']);
                return;
            }
        }

        if ($this->pembeliAlamatModel->updateAddress($addressId, $currentUser['id'], $input)) {
            $address = $this->pembeliAlamatModel->getAddressById($addressId, $currentUser['id']);
            response(200, [
                'message' => 'Address updated successfully',
                'data' => $address
            ]);
        } else {
            response(500, ['error' => 'Failed to update address or address not found']);
        }
    }

    public function deleteAddress($addressId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        if ($this->pembeliAlamatModel->deleteAddress($addressId, $currentUser['id'])) {
            response(200, ['message' => 'Address deleted successfully']);
        } else {
            response(500, ['error' => 'Failed to delete address or address not found']);
        }
    }

    public function setDefaultAddress($addressId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        if ($this->pembeliAlamatModel->setDefaultAddress($addressId, $currentUser['id'])) {
            response(200, ['message' => 'Default address set successfully']);
        } else {
            response(500, ['error' => 'Failed to set default address or address not found']);
        }
    }

    public function getDefaultAddress() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $address = $this->pembeliAlamatModel->getDefaultAddress($currentUser['id']);
        
        if (!$address) {
            response(404, ['error' => 'No default address found']);
            return;
        }
        
        response(200, [
            'message' => 'Default address retrieved successfully',
            'data' => $address
        ]);
    }

    public function searchAddresses() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            response(400, ['error' => 'Search query required']);
            return;
        }

        $addresses = $this->pembeliAlamatModel->searchAddresses($currentUser['id'], $query);
        
        response(200, [
            'message' => 'Search results retrieved successfully',
            'data' => $addresses
        ]);
    }

    public function getAddressesByCity($cityId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $addresses = $this->pembeliAlamatModel->getAddressesByCity($currentUser['id'], $cityId);
        
        response(200, [
            'message' => 'Addresses by city retrieved successfully',
            'data' => $addresses
        ]);
    }

    public function getAddressesByProvince($provinceId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $addresses = $this->pembeliAlamatModel->getAddressesByProvince($currentUser['id'], $provinceId);
        
        response(200, [
            'message' => 'Addresses by province retrieved successfully',
            'data' => $addresses
        ]);
    }

    public function getAddressStatistics() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $statistics = $this->pembeliAlamatModel->getAddressStatistics($currentUser['id']);
        
        response(200, [
            'message' => 'Address statistics retrieved successfully',
            'data' => $statistics
        ]);
    }

    public function getFrequentlyUsedAddresses() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $limit = $_GET['limit'] ?? 5;
        $addresses = $this->pembeliAlamatModel->getFrequentlyUsedAddresses($currentUser['id'], $limit);
        
        response(200, [
            'message' => 'Frequently used addresses retrieved successfully',
            'data' => $addresses
        ]);
    }

    public function getProvinces() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $provinces = $this->pembeliAlamatModel->getProvinces();
        
        response(200, [
            'message' => 'Provinces retrieved successfully',
            'data' => $provinces
        ]);
    }

    public function getCitiesByProvince($provinceId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $cities = $this->pembeliAlamatModel->getCitiesByProvince($provinceId);
        
        response(200, [
            'message' => 'Cities retrieved successfully',
            'data' => $cities
        ]);
    }

    public function duplicateAddress($addressId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $newLabel = $input['new_label'] ?? null;

        $newAddressId = $this->pembeliAlamatModel->duplicateAddress($addressId, $currentUser['id'], $newLabel);
        
        if ($newAddressId) {
            $address = $this->pembeliAlamatModel->getAddressById($newAddressId, $currentUser['id']);
            response(201, [
                'message' => 'Address duplicated successfully',
                'data' => $address
            ]);
        } else {
            response(500, ['error' => 'Failed to duplicate address or address not found']);
        }
    }

    public function getAddressUsageHistory($addressId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $history = $this->pembeliAlamatModel->getAddressUsageHistory($addressId, $currentUser['id']);
        
        if ($history === false) {
            response(404, ['error' => 'Address not found or access denied']);
            return;
        }
        
        response(200, [
            'message' => 'Address usage history retrieved successfully',
            'data' => $history
        ]);
    }

    public function getAddressOverview() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $allAddresses = $this->pembeliAlamatModel->getAllAddresses($currentUser['id']);
        $defaultAddress = $this->pembeliAlamatModel->getDefaultAddress($currentUser['id']);
        $statistics = $this->pembeliAlamatModel->getAddressStatistics($currentUser['id']);
        $frequentlyUsed = $this->pembeliAlamatModel->getFrequentlyUsedAddresses($currentUser['id'], 3);

        response(200, [
            'message' => 'Address overview retrieved successfully',
            'data' => [
                'all_addresses' => $allAddresses,
                'default_address' => $defaultAddress,
                'statistics' => $statistics,
                'frequently_used' => $frequentlyUsed
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

        if (!preg_match('/^[0-9+\-\s()]+$/', $input['no_telepon'])) {
            return false;
        }

        if (!preg_match('/^[0-9]{5}$/', $input['kode_pos'])) {
            return false;
        }

        if (strlen($input['label']) > 50) {
            return false;
        }

        if (strlen($input['nama_penerima']) > 100) {
            return false;
        }

        return true;
    }
}