<?php
class OngkirController {
    private $ongkirModel;

    public function __construct() {
        $this->ongkirModel = new OngkirModel();
    }

    public function getProvinsi() {
        $provinsi = $this->ongkirModel->getAllProvinsi();
        response(200, ['data' => $provinsi]);
    }

    public function getKota() {
        $provinceId = $_GET['province_id'] ?? null;
        
        if ($provinceId) {
            $kota = $this->ongkirModel->getKotaByProvinsi($provinceId);
        } else {
            $kota = $this->ongkirModel->getAllKota();
        }
        
        response(200, ['data' => $kota]);
    }

    public function searchKota() {
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            response(400, ['error' => 'Query required']);
            return;
        }

        $kota = $this->ongkirModel->searchKota($query);
        response(200, ['data' => $kota]);
    }

    public function getCost() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input['origin'] || !$input['destination'] || !$input['weight'] || !$input['courier']) {
            response(400, ['error' => 'All fields required']);
            return;
        }

        $cost = $this->ongkirModel->getCost(
            $input['origin'],
            $input['destination'],
            $input['weight'],
            $input['courier']
        );

        response(200, ['data' => $cost]);
    }

    public function importAll() {
        $this->ongkirModel->importProvinsiFromApi();
        $this->ongkirModel->importKotaFromApi();
        response(200, ['message' => 'Import completed']);
    }

    public function getProvinsiById($id) {
        $provinsi = $this->ongkirModel->getProvinsiById($id);
        $kota = $this->ongkirModel->getKotaByProvinsi($id);
        
        response(200, [
            'provinsi' => $provinsi,
            'kota' => $kota
        ]);
    }

    public function getKotaById($id) {
        $kota = $this->ongkirModel->getKotaById($id);
        response(200, ['data' => $kota]);
    }
}