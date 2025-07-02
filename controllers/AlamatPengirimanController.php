<?php
class AlamatPengirimanController {
    private $alamatModel;

    public function __construct() {
        $this->alamatModel = new AlamatPengirimanModel();
    }

    public function getAllAlamat() {
        $userId = $_GET['user_id'] ?? null;
        $provinceId = $_GET['province_id'] ?? null;
        $cityId = $_GET['city_id'] ?? null;
        
        if ($userId) {
            $alamat = $this->alamatModel->getAlamatByUser($userId);
        } elseif ($provinceId) {
            $alamat = $this->alamatModel->getAlamatByProvince($provinceId);
        } elseif ($cityId) {
            $alamat = $this->alamatModel->getAlamatByCity($cityId);
        } else {
            $alamat = $this->alamatModel->getAllAlamat();
        }
        
        response(200, ['data' => $alamat]);
    }

    public function getAlamatById($id) {
        $alamat = $this->alamatModel->getAlamatById($id);
        
        if (!$alamat) {
            response(404, ['error' => 'Address not found']);
            return;
        }
        
        response(200, ['data' => $alamat]);
    }

    public function createAlamat() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateAlamatInput($input)) {
            response(400, ['error' => 'Invalid input data']);
            return;
        }

        if (!$this->alamatModel->validateAlamatData($input)) {
            response(400, ['error' => 'Invalid province or city combination']);
            return;
        }

        $alamatId = $this->alamatModel->createAlamat($input);
        
        if ($alamatId) {
            $alamat = $this->alamatModel->getAlamatById($alamatId);
            response(201, ['message' => 'Address created successfully', 'data' => $alamat]);
        } else {
            response(500, ['error' => 'Failed to create address']);
        }
    }

    public function updateAlamat($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->alamatModel->getAlamatById($id)) {
            response(404, ['error' => 'Address not found']);
            return;
        }

        if ((isset($input['province_id']) || isset($input['city_id'])) && !$this->alamatModel->validateAlamatData($input)) {
            response(400, ['error' => 'Invalid province or city combination']);
            return;
        }

        if ($this->alamatModel->updateAlamat($id, $input)) {
            $alamat = $this->alamatModel->getAlamatById($id);
            response(200, ['message' => 'Address updated successfully', 'data' => $alamat]);
        } else {
            response(500, ['error' => 'Failed to update address']);
        }
    }

    public function deleteAlamat($id) {
        if (!$this->alamatModel->getAlamatById($id)) {
            response(404, ['error' => 'Address not found']);
            return;
        }

        if ($this->alamatModel->deleteAlamat($id)) {
            response(200, ['message' => 'Address deleted successfully']);
        } else {
            response(500, ['error' => 'Failed to delete address']);
        }
    }

    public function searchAlamat() {
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            response(400, ['error' => 'Search query required']);
            return;
        }

        $alamat = $this->alamatModel->searchAlamat($query);
        response(200, ['data' => $alamat]);
    }

    public function getAlamatStats() {
        $stats = $this->alamatModel->getAlamatStats();
        response(200, ['data' => $stats]);
    }

    public function getSuspiciousAlamat() {
        $alamat = $this->alamatModel->getSuspiciousAlamat();
        response(200, [
            'message' => 'Suspicious addresses for fraud detection',
            'data' => $alamat
        ]);
    }

    public function validateUserAlamat() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['user_id'])) {
            response(400, ['error' => 'User ID required']);
            return;
        }

        $userAlamat = $this->alamatModel->getAlamatByUser($input['user_id']);
        
        $validation = [
            'has_address' => !empty($userAlamat),
            'has_default' => false,
            'total_addresses' => count($userAlamat),
            'addresses' => $userAlamat
        ];

        foreach ($userAlamat as $alamat) {
            if ($alamat['is_default']) {
                $validation['has_default'] = true;
                break;
            }
        }

        response(200, [
            'message' => 'User address validation completed',
            'data' => $validation
        ]);
    }

    public function setDefaultAlamat($id) {
        $alamat = $this->alamatModel->getAlamatById($id);
        
        if (!$alamat) {
            response(404, ['error' => 'Address not found']);
            return;
        }

        if ($this->alamatModel->updateAlamat($id, ['is_default' => 1])) {
            response(200, ['message' => 'Default address updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update default address']);
        }
    }

    private function validateAlamatInput($input) {
        $required = ['user_id', 'label', 'nama_penerima', 'no_telepon', 'province_id', 'city_id', 'alamat_lengkap', 'kode_pos'];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return false;
            }
        }

        if (!is_numeric($input['user_id']) || !is_numeric($input['province_id']) || !is_numeric($input['city_id'])) {
            return false;
        }

        if (!preg_match('/^[0-9+\-\s]+$/', $input['no_telepon'])) {
            return false;
        }

        if (!preg_match('/^[0-9]{5}$/', $input['kode_pos'])) {
            return false;
        }

        $input['is_default'] = $input['is_default'] ?? 0;

        return true;
    }
}