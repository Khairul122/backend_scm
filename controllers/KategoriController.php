<?php
class KategoriController {
    private $kategoriModel;

    public function __construct() {
        $this->kategoriModel = new KategoriModel();
    }

    public function index() {
        $kategori = $this->kategoriModel->getAllKategori();
        
        response(200, [
            'message' => 'Kategori retrieved successfully',
            'data' => $kategori
        ]);
    }

    public function show() {
        $id = $this->getIdFromUrl();
        
        if (!$id || !is_numeric($id)) {
            response(400, ['error' => 'Invalid kategori ID']);
        }

        $kategori = $this->kategoriModel->getKategoriById($id);
        
        if (!$kategori) {
            response(404, ['error' => 'Kategori not found']);
        }

        $products = $this->kategoriModel->getKategoriWithProducts($id);

        response(200, [
            'message' => 'Kategori retrieved successfully',
            'data' => $kategori,
            'products' => $products
        ]);
    }

    public function store() {
        $user = $this->getCurrentUser();
        
        if (!$user || $user['role'] !== 'admin') {
            response(403, ['error' => 'Admin access required']);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['nama_kategori'])) {
            response(400, ['error' => 'Nama kategori is required']);
        }

        if ($this->kategoriModel->checkKategoriExists($input['nama_kategori'])) {
            response(409, ['error' => 'Kategori name already exists']);
        }

        $data = [
            'nama_kategori' => $input['nama_kategori'],
            'deskripsi' => $input['deskripsi'] ?? ''
        ];

        $kategoriId = $this->kategoriModel->createKategori($data);
        
        if ($kategoriId) {
            $kategori = $this->kategoriModel->getKategoriById($kategoriId);
            response(201, [
                'message' => 'Kategori created successfully',
                'data' => $kategori
            ]);
        } else {
            response(500, ['error' => 'Failed to create kategori']);
        }
    }

    public function update() {
        $user = $this->getCurrentUser();
        
        if (!$user || $user['role'] !== 'admin') {
            response(403, ['error' => 'Admin access required']);
        }

        $id = $this->getIdFromUrl();
        
        if (!$id || !is_numeric($id)) {
            response(400, ['error' => 'Invalid kategori ID']);
        }

        $existingKategori = $this->kategoriModel->getKategoriById($id);
        if (!$existingKategori) {
            response(404, ['error' => 'Kategori not found']);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['nama_kategori'])) {
            response(400, ['error' => 'Nama kategori is required']);
        }

        if ($this->kategoriModel->checkKategoriExists($input['nama_kategori'], $id)) {
            response(409, ['error' => 'Kategori name already exists']);
        }

        $data = [
            'nama_kategori' => $input['nama_kategori'],
            'deskripsi' => $input['deskripsi'] ?? ''
        ];

        $updated = $this->kategoriModel->updateKategori($id, $data);
        
        if ($updated) {
            $kategori = $this->kategoriModel->getKategoriById($id);
            response(200, [
                'message' => 'Kategori updated successfully',
                'data' => $kategori
            ]);
        } else {
            response(500, ['error' => 'Failed to update kategori']);
        }
    }

    public function delete() {
        $user = $this->getCurrentUser();
        
        if (!$user || $user['role'] !== 'admin') {
            response(403, ['error' => 'Admin access required']);
        }

        $id = $this->getIdFromUrl();
        
        if (!$id || !is_numeric($id)) {
            response(400, ['error' => 'Invalid kategori ID']);
        }

        $existingKategori = $this->kategoriModel->getKategoriById($id);
        if (!$existingKategori) {
            response(404, ['error' => 'Kategori not found']);
        }

        $deleted = $this->kategoriModel->deleteKategori($id);
        
        if ($deleted) {
            response(200, ['message' => 'Kategori deleted successfully']);
        } else {
            response(400, ['error' => 'Cannot delete kategori. Products still exist in this category']);
        }
    }

    public function getProducts() {
        $id = $this->getIdFromUrl('products');
        
        if (!$id || !is_numeric($id)) {
            response(400, ['error' => 'Invalid kategori ID']);
        }

        $kategori = $this->kategoriModel->getKategoriById($id);
        if (!$kategori) {
            response(404, ['error' => 'Kategori not found']);
        }

        $products = $this->kategoriModel->getKategoriWithProducts($id);

        response(200, [
            'message' => 'Products retrieved successfully',
            'kategori' => $kategori,
            'data' => $products
        ]);
    }

    private function getIdFromUrl($suffix = null) {
        $uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($uri, PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        
        if ($suffix) {
            $productIndex = array_search($suffix, $segments);
            if ($productIndex !== false && $productIndex > 0) {
                return $segments[$productIndex - 1];
            }
        } else {
            $kategoriIndex = array_search('kategori', $segments);
            if ($kategoriIndex !== false && isset($segments[$kategoriIndex + 1])) {
                return $segments[$kategoriIndex + 1];
            }
        }
        
        return null;
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