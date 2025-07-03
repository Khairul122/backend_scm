<?php
class LimitProdukController {
    private $produkModel;

    public function __construct() {
        $this->produkModel = new LimitProdukModel();
    }

    public function getAllProduk() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['pengepul', 'admin'])) {
            response(403, ['error' => 'Access denied. Pengepul role required.']);
            return;
        }

        $status = $_GET['status'] ?? null;
        $penjualId = $currentUser['id'];
        
        if ($status) {
            $produk = $this->produkModel->getProdukByStatus($status, $penjualId);
        } else {
            $produk = $this->produkModel->getAllProduk($penjualId);
        }
        
        response(200, ['data' => $produk]);
    }

    public function getProdukById($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['pengepul', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $produk = $this->produkModel->getProdukById($id, $currentUser['id']);
        
        if (!$produk) {
            response(404, ['error' => 'Green bean product not found or access denied']);
            return;
        }
        
        response(200, ['data' => $produk]);
    }

    public function createProduk() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['pengepul', 'admin'])) {
            response(403, ['error' => 'Access denied. Pengepul role required.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateProdukInput($input)) {
            response(400, ['error' => 'Invalid input data']);
            return;
        }

        if (!$this->produkModel->validateGreenBeanProduct($input)) {
            response(400, ['error' => 'Product must be Green Bean variety']);
            return;
        }

        $input['penjual_id'] = $currentUser['id'];
        $input['kategori_id'] = 1;

        $produkId = $this->produkModel->createProduk($input);
        
        if ($produkId) {
            $produk = $this->produkModel->getProdukById($produkId, $currentUser['id']);
            response(201, ['message' => 'Green bean product listed for wholesale successfully', 'data' => $produk]);
        } else {
            response(500, ['error' => 'Failed to create product']);
        }
    }

    public function updateProduk($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['pengepul', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->produkModel->checkProdukOwnership($id, $currentUser['id'])) {
            response(404, ['error' => 'Product not found or access denied']);
            return;
        }

        if ($this->produkModel->updateProduk($id, $input, $currentUser['id'])) {
            $produk = $this->produkModel->getProdukById($id, $currentUser['id']);
            response(200, ['message' => 'Product updated successfully', 'data' => $produk]);
        } else {
            response(500, ['error' => 'Failed to update product']);
        }
    }

    public function deleteProduk($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['pengepul', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        if (!$this->produkModel->checkProdukOwnership($id, $currentUser['id'])) {
            response(404, ['error' => 'Product not found or access denied']);
            return;
        }

        if ($this->produkModel->deleteProduk($id, $currentUser['id'])) {
            response(200, ['message' => 'Product deactivated successfully']);
        } else {
            response(500, ['error' => 'Failed to deactivate product']);
        }
    }

    public function updateHarga($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['pengepul', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['harga']) || !is_numeric($input['harga']) || $input['harga'] <= 0) {
            response(400, ['error' => 'Valid price required']);
            return;
        }

        if (!$this->produkModel->checkProdukOwnership($id, $currentUser['id'])) {
            response(404, ['error' => 'Product not found or access denied']);
            return;
        }

        if ($this->produkModel->updateHarga($id, $input['harga'], $currentUser['id'])) {
            response(200, ['message' => 'Product price updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update price']);
        }
    }

    public function updateStok($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['pengepul', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['stok']) || !is_numeric($input['stok']) || $input['stok'] < 0) {
            response(400, ['error' => 'Valid stock quantity required']);
            return;
        }

        if (!$this->produkModel->checkProdukOwnership($id, $currentUser['id'])) {
            response(404, ['error' => 'Product not found or access denied']);
            return;
        }

        if ($this->produkModel->updateStok($id, $input['stok'], $currentUser['id'])) {
            response(200, ['message' => 'Product stock updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update stock']);
        }
    }

    public function updateStatus($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['pengepul', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['status']) || !in_array($input['status'], ['aktif', 'nonaktif'])) {
            response(400, ['error' => 'Invalid status']);
            return;
        }

        if (!$this->produkModel->checkProdukOwnership($id, $currentUser['id'])) {
            response(404, ['error' => 'Product not found or access denied']);
            return;
        }

        if ($this->produkModel->updateStatus($id, $input['status'], $currentUser['id'])) {
            response(200, ['message' => 'Product status updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update status']);
        }
    }

    public function searchProduk() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['pengepul', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            response(400, ['error' => 'Search query required']);
            return;
        }

        $produk = $this->produkModel->searchProduk($query, $currentUser['id']);
        response(200, ['data' => $produk]);
    }

    public function getProdukStats() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['pengepul', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $stats = $this->produkModel->getProdukStats($currentUser['id']);
        response(200, ['data' => $stats]);
    }

    public function getLowStockProduk() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['pengepul', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $threshold = $_GET['threshold'] ?? 10;
        $produk = $this->produkModel->getLowStockProduk($threshold, $currentUser['id']);
        
        response(200, [
            'message' => "Green bean products with stock below {$threshold} kg",
            'data' => $produk
        ]);
    }

    public function getProdukForWholesale() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['pengepul', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $produk = $this->produkModel->getProdukForWholesale($currentUser['id']);
        
        response(200, [
            'message' => 'Green bean products available for wholesale',
            'data' => $produk
        ]);
    }

    public function getGreenBeanVarieties() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['pengepul', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $varieties = $this->produkModel->getGreenBeanVarieties($currentUser['id']);
        
        response(200, [
            'message' => 'Green bean varieties analysis',
            'data' => $varieties
        ]);
    }

    public function getRecentlyUpdated() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['pengepul', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $limit = $_GET['limit'] ?? 10;
        $produk = $this->produkModel->getRecentlyUpdated($currentUser['id'], $limit);
        
        response(200, [
            'message' => "Recently updated green bean products",
            'data' => $produk
        ]);
    }

    public function getHighValueProducts() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['pengepul', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $minPrice = $_GET['min_price'] ?? 100000;
        $produk = $this->produkModel->getHighValueProducts($currentUser['id'], $minPrice);
        
        response(200, [
            'message' => "High value green bean products (≥{$minPrice})",
            'data' => $produk
        ]);
    }

    public function bulkUpdatePrices() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['pengepul', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['updates']) || !is_array($input['updates'])) {
            response(400, ['error' => 'Price updates array required']);
            return;
        }

        $updated = $this->produkModel->bulkUpdatePrices($input['updates'], $currentUser['id']);
        
        response(200, [
            'message' => 'Bulk price update completed',
            'updated_count' => $updated
        ]);
    }

    public function bulkUpdateStatus() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['pengepul', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['ids']) || !isset($input['status']) || !is_array($input['ids'])) {
            response(400, ['error' => 'Product IDs and status required']);
            return;
        }

        if (!in_array($input['status'], ['aktif', 'nonaktif'])) {
            response(400, ['error' => 'Invalid status']);
            return;
        }

        if ($this->produkModel->bulkUpdateStatus($input['ids'], $input['status'], $currentUser['id'])) {
            response(200, [
                'message' => 'Bulk status update completed',
                'updated_count' => count($input['ids'])
            ]);
        } else {
            response(500, ['error' => 'Failed to update product statuses']);
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

    private function validateProdukInput($input) {
        $required = ['nama_produk', 'deskripsi', 'harga', 'stok', 'berat'];
        
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return false;
            }
        }

        if (!is_numeric($input['harga']) || $input['harga'] <= 0) {
            return false;
        }

        if (!is_numeric($input['stok']) || $input['stok'] < 0) {
            return false;
        }

        if (!is_numeric($input['berat']) || $input['berat'] <= 0) {
            return false;
        }

        if (strlen($input['nama_produk']) < 5) {
            return false;
        }

        return true;
    }
}