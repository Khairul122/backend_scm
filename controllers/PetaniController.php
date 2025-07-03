<?php
class PetaniController {
    private $petaniModel;

    public function __construct() {
        $this->petaniModel = new PetaniModel();
    }

    public function getAllPetani() {
        $territory = $_GET['territory'] ?? null;
        $jenisKopi = $_GET['jenis_kopi'] ?? null;
        $status = $_GET['status'] ?? null;
        $minLuas = $_GET['min_luas'] ?? null;
        $maxLuas = $_GET['max_luas'] ?? null;
        
        if ($territory) {
            $petani = $this->petaniModel->getPetaniByTerritory($territory);
        } elseif ($jenisKopi) {
            $petani = $this->petaniModel->getPetaniByJenisKopi($jenisKopi);
        } elseif ($status === 'active') {
            $petani = $this->petaniModel->getActivePetani();
        } elseif ($status === 'inactive') {
            $petani = $this->petaniModel->getInactivePetani();
        } elseif ($minLuas || $maxLuas) {
            $petani = $this->petaniModel->getPetaniByLuasLahan($minLuas, $maxLuas);
        } else {
            $petani = $this->petaniModel->getAllPetani();
        }
        
        response(200, ['data' => $petani]);
    }

    public function getPetaniById($id) {
        $petani = $this->petaniModel->getPetaniWithBatches($id);
        
        if (!$petani) {
            response(404, ['error' => 'Farmer not found']);
            return;
        }
        
        response(200, ['data' => $petani]);
    }

    public function createPetani() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validatePetaniInput($input)) {
            response(400, ['error' => 'Invalid input data']);
            return;
        }

        if ($this->petaniModel->checkPhoneExists($input['no_telepon'])) {
            response(400, ['error' => 'Phone number already exists']);
            return;
        }

        $petaniId = $this->petaniModel->createPetani($input);
        
        if ($petaniId) {
            $petani = $this->petaniModel->getPetaniById($petaniId);
            response(201, ['message' => 'Farmer onboarded successfully', 'data' => $petani]);
        } else {
            response(500, ['error' => 'Failed to onboard farmer']);
        }
    }

    public function updatePetani($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->petaniModel->getPetaniById($id)) {
            response(404, ['error' => 'Farmer not found']);
            return;
        }

        if (isset($input['no_telepon']) && $this->petaniModel->checkPhoneExists($input['no_telepon'], $id)) {
            response(400, ['error' => 'Phone number already exists']);
            return;
        }

        if ($this->petaniModel->updatePetani($id, $input)) {
            $petani = $this->petaniModel->getPetaniById($id);
            response(200, ['message' => 'Farmer info updated successfully', 'data' => $petani]);
        } else {
            response(500, ['error' => 'Failed to update farmer info']);
        }
    }

    public function deletePetani($id) {
        if (!$this->petaniModel->getPetaniById($id)) {
            response(404, ['error' => 'Farmer not found']);
            return;
        }

        if ($this->petaniModel->deletePetani($id)) {
            response(200, ['message' => 'Farmer removed from network successfully']);
        } else {
            response(500, ['error' => 'Failed to remove farmer']);
        }
    }

    public function searchPetani() {
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            response(400, ['error' => 'Search query required']);
            return;
        }

        $petani = $this->petaniModel->searchPetani($query);
        response(200, ['data' => $petani]);
    }

    public function getPetaniStats() {
        $stats = $this->petaniModel->getPetaniStats();
        response(200, ['data' => $stats]);
    }

    public function getPetaniCapacity() {
        $capacity = $this->petaniModel->getPetaniCapacity();
        
        response(200, [
            'message' => 'Farmer capacity analysis',
            'data' => $capacity
        ]);
    }

    public function getTerritoryStats() {
        $stats = $this->petaniModel->getTerritoryStats();
        
        response(200, [
            'message' => 'Territory management statistics',
            'data' => $stats
        ]);
    }

    public function getPetaniProduction() {
        $production = $this->petaniModel->getPetaniProduction();
        
        response(200, [
            'message' => 'Farmer production analysis',
            'data' => $production
        ]);
    }

    public function getTopProducers() {
        $limit = $_GET['limit'] ?? 10;
        $producers = $this->petaniModel->getTopProducers($limit);
        
        response(200, [
            'message' => "Top {$limit} producing farmers",
            'data' => $producers
        ]);
    }

    public function getPetaniNetwork() {
        $stats = $this->petaniModel->getPetaniStats();
        $territory = $this->petaniModel->getTerritoryStats();
        $capacity = $this->petaniModel->getPetaniCapacity();
        $production = $this->petaniModel->getPetaniProduction();
        $active = $this->petaniModel->getActivePetani();
        $inactive = $this->petaniModel->getInactivePetani();
        
        response(200, [
            'message' => 'Comprehensive farmer network overview',
            'data' => [
                'overview' => $stats,
                'territory_stats' => $territory,
                'capacity_analysis' => $capacity,
                'production_summary' => $production,
                'active_farmers' => $active,
                'inactive_farmers' => $inactive
            ]
        ]);
    }

    public function onboardNewFarmer() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateOnboardingInput($input)) {
            response(400, ['error' => 'Invalid onboarding data']);
            return;
        }

        if ($this->petaniModel->checkPhoneExists($input['no_telepon'])) {
            response(400, ['error' => 'Farmer with this phone number already exists']);
            return;
        }

        $petaniId = $this->petaniModel->createPetani($input);
        
        if ($petaniId) {
            $petani = $this->petaniModel->getPetaniById($petaniId);
            response(201, [
                'message' => 'New farmer successfully onboarded to network',
                'data' => $petani
            ]);
        } else {
            response(500, ['error' => 'Failed to onboard new farmer']);
        }
    }

    public function updateFarmerCapacity($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['luas_lahan']) || !is_numeric($input['luas_lahan'])) {
            response(400, ['error' => 'Valid land area required']);
            return;
        }

        if (!$this->petaniModel->getPetaniById($id)) {
            response(404, ['error' => 'Farmer not found']);
            return;
        }

        if ($this->petaniModel->updatePetani($id, ['luas_lahan' => $input['luas_lahan']])) {
            response(200, ['message' => 'Farmer capacity updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update farmer capacity']);
        }
    }

    public function updateFarmerContact($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['no_telepon']) || empty($input['no_telepon'])) {
            response(400, ['error' => 'Phone number required']);
            return;
        }

        if (!$this->petaniModel->getPetaniById($id)) {
            response(404, ['error' => 'Farmer not found']);
            return;
        }

        if ($this->petaniModel->checkPhoneExists($input['no_telepon'], $id)) {
            response(400, ['error' => 'Phone number already exists']);
            return;
        }

        if ($this->petaniModel->updatePetani($id, ['no_telepon' => $input['no_telepon']])) {
            response(200, ['message' => 'Farmer contact updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update farmer contact']);
        }
    }

    public function bulkUpdateTerritory() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['petani_ids']) || !isset($input['territory_manager']) || !is_array($input['petani_ids'])) {
            response(400, ['error' => 'Farmer IDs and territory manager required']);
            return;
        }

        if ($this->petaniModel->bulkUpdateTerritory($input['petani_ids'], $input['territory_manager'])) {
            response(200, [
                'message' => 'Territory assignment updated successfully',
                'updated_count' => count($input['petani_ids'])
            ]);
        } else {
            response(500, ['error' => 'Failed to update territory assignments']);
        }
    }

    private function validatePetaniInput($input) {
        $required = ['nama_petani', 'no_telepon', 'alamat_kebun', 'luas_lahan', 'jenis_kopi', 'created_by'];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return false;
            }
        }

        if (!is_numeric($input['luas_lahan']) || $input['luas_lahan'] <= 0) {
            return false;
        }

        if (!in_array($input['jenis_kopi'], ['arabika', 'robusta'])) {
            return false;
        }

        if (!is_numeric($input['created_by'])) {
            return false;
        }

        return true;
    }

    private function validateOnboardingInput($input) {
        $required = ['nama_petani', 'no_telepon', 'alamat_kebun', 'luas_lahan', 'jenis_kopi', 'created_by'];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return false;
            }
        }

        if (!is_numeric($input['luas_lahan']) || $input['luas_lahan'] <= 0) {
            return false;
        }

        if (!in_array($input['jenis_kopi'], ['arabika', 'robusta'])) {
            return false;
        }

        if (strlen($input['nama_petani']) < 3) {
            return false;
        }

        if (!preg_match('/^[0-9+\-\s]+$/', $input['no_telepon'])) {
            return false;
        }

        return true;
    }
}