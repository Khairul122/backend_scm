<?php
class RoastProductController {
    private $roastModel;

    public function __construct() {
        $this->roastModel = new RoastProductModel();
    }

    public function getAllRoastedProducts() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied. Roasting role required.']);
            return;
        }

        $status = $_GET['status'] ?? null;
        $penjualId = $currentUser['id'];
        
        if ($status) {
            $products = $this->roastModel->getRoastedProductsByStatus($status, $penjualId);
        } else {
            $products = $this->roastModel->getAllRoastedProducts($penjualId);
        }
        
        response(200, ['data' => $products]);
    }

    public function getRoastedProductById($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $product = $this->roastModel->getRoastedProductById($id, $currentUser['id']);
        
        if (!$product) {
            response(404, ['error' => 'Roasted coffee product not found or access denied']);
            return;
        }
        
        response(200, ['data' => $product]);
    }

    public function createRoastedProduct() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied. Roasting role required.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateRoastedProductInput($input)) {
            response(400, ['error' => 'Invalid input data']);
            return;
        }

        if (!$this->roastModel->validateRoastedProduct($input)) {
            response(400, ['error' => 'Product must be roasted coffee variety']);
            return;
        }

        $input['penjual_id'] = $currentUser['id'];
        $input['kategori_id'] = 2;

        if (isset($input['roast_profile'])) {
            $input['deskripsi'] = $this->roastModel->buildRoastProfileDescription($input['roast_profile']);
            unset($input['roast_profile']);
        }

        $produkId = $this->roastModel->createRoastedProduct($input);
        
        if ($produkId) {
            $product = $this->roastModel->getRoastedProductById($produkId, $currentUser['id']);
            response(201, ['message' => 'Roasted coffee product created successfully', 'data' => $product]);
        } else {
            response(500, ['error' => 'Failed to create roasted product']);
        }
    }

    public function updateRoastedProduct($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->roastModel->checkRoastedProductOwnership($id, $currentUser['id'])) {
            response(404, ['error' => 'Product not found or access denied']);
            return;
        }

        if (isset($input['roast_profile'])) {
            $input['deskripsi'] = $this->roastModel->buildRoastProfileDescription($input['roast_profile']);
            unset($input['roast_profile']);
        }

        if ($this->roastModel->updateRoastedProduct($id, $input, $currentUser['id'])) {
            $product = $this->roastModel->getRoastedProductById($id, $currentUser['id']);
            response(200, ['message' => 'Roasted product updated successfully', 'data' => $product]);
        } else {
            response(500, ['error' => 'Failed to update product']);
        }
    }

    public function deleteRoastedProduct($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        if (!$this->roastModel->checkRoastedProductOwnership($id, $currentUser['id'])) {
            response(404, ['error' => 'Product not found or access denied']);
            return;
        }

        if ($this->roastModel->deleteRoastedProduct($id, $currentUser['id'])) {
            response(200, ['message' => 'Roasted product discontinued successfully']);
        } else {
            response(500, ['error' => 'Failed to discontinue product']);
        }
    }

    public function updateRoastProfile($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['roast_data']) || !is_array($input['roast_data'])) {
            response(400, ['error' => 'Roast profile data required']);
            return;
        }

        if (!$this->roastModel->checkRoastedProductOwnership($id, $currentUser['id'])) {
            response(404, ['error' => 'Product not found or access denied']);
            return;
        }

        if ($this->roastModel->updateRoastProfile($id, $input['roast_data'], $currentUser['id'])) {
            response(200, ['message' => 'Roast profile updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update roast profile']);
        }
    }

    public function updatePricing($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['harga']) || !is_numeric($input['harga']) || $input['harga'] <= 0) {
            response(400, ['error' => 'Valid price required']);
            return;
        }

        if (!$this->roastModel->checkRoastedProductOwnership($id, $currentUser['id'])) {
            response(404, ['error' => 'Product not found or access denied']);
            return;
        }

        if ($this->roastModel->updatePricing($id, $input['harga'], $currentUser['id'])) {
            response(200, ['message' => 'Product pricing updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update pricing']);
        }
    }

    public function discontinueProduct($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        if (!$this->roastModel->checkRoastedProductOwnership($id, $currentUser['id'])) {
            response(404, ['error' => 'Product not found or access denied']);
            return;
        }

        if ($this->roastModel->discontinueProduct($id, $currentUser['id'])) {
            response(200, ['message' => 'Product discontinued successfully']);
        } else {
            response(500, ['error' => 'Failed to discontinue product']);
        }
    }

    public function searchRoastedProducts() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            response(400, ['error' => 'Search query required']);
            return;
        }

        $products = $this->roastModel->searchRoastedProducts($query, $currentUser['id']);
        response(200, ['data' => $products]);
    }

    public function getRoastedProductStats() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $stats = $this->roastModel->getRoastedProductStats($currentUser['id']);
        response(200, ['data' => $stats]);
    }

    public function getRoastLevelAnalysis() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $analysis = $this->roastModel->getRoastLevelAnalysis($currentUser['id']);
        
        response(200, [
            'message' => 'Roast level distribution analysis',
            'data' => $analysis
        ]);
    }

    public function getBrewingMethodAnalysis() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $analysis = $this->roastModel->getBrewingMethodAnalysis($currentUser['id']);
        
        response(200, [
            'message' => 'Brewing method recommendations analysis',
            'data' => $analysis
        ]);
    }

    public function getCuppingNotesAnalysis() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $analysis = $this->roastModel->getCuppingNotesAnalysis($currentUser['id']);
        
        response(200, [
            'message' => 'Cupping notes and flavor profiles analysis',
            'data' => $analysis
        ]);
    }

    public function getPackagingSizeAnalysis() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $analysis = $this->roastModel->getPackagingSizeAnalysis($currentUser['id']);
        
        response(200, [
            'message' => 'Packaging size and pricing analysis',
            'data' => $analysis
        ]);
    }

    public function getRecentRoasts() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $limit = $_GET['limit'] ?? 10;
        $products = $this->roastModel->getRecentRoasts($currentUser['id'], $limit);
        
        response(200, [
            'message' => "Recent {$limit} roasted products",
            'data' => $products
        ]);
    }

    public function getPremiumRoasts() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $minPrice = $_GET['min_price'] ?? 50000;
        $products = $this->roastModel->getPremiumRoasts($currentUser['id'], $minPrice);
        
        response(200, [
            'message' => "Premium roasted products (≥{$minPrice})",
            'data' => $products
        ]);
    }

    public function getDiscontinuedProducts() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $products = $this->roastModel->getDiscontinuedProducts($currentUser['id']);
        
        response(200, [
            'message' => 'Discontinued roasted products',
            'data' => $products
        ]);
    }

    public function bulkUpdateRoastProfiles() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['updates']) || !is_array($input['updates'])) {
            response(400, ['error' => 'Roast profile updates array required']);
            return;
        }

        $updated = $this->roastModel->bulkUpdateRoastProfiles($input['updates'], $currentUser['id']);
        
        response(200, [
            'message' => 'Bulk roast profile update completed',
            'updated_count' => $updated
        ]);
    }

    public function bulkUpdatePricing() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['updates']) || !is_array($input['updates'])) {
            response(400, ['error' => 'Pricing updates array required']);
            return;
        }

        $updated = $this->roastModel->bulkUpdatePricing($input['updates'], $currentUser['id']);
        
        response(200, [
            'message' => 'Bulk pricing update completed',
            'updated_count' => $updated
        ]);
    }

    public function getRoastingOverview() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['roasting', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $stats = $this->roastModel->getRoastedProductStats($currentUser['id']);
        $roastLevels = $this->roastModel->getRoastLevelAnalysis($currentUser['id']);
        $brewingMethods = $this->roastModel->getBrewingMethodAnalysis($currentUser['id']);
        $cuppingNotes = $this->roastModel->getCuppingNotesAnalysis($currentUser['id']);
        $packaging = $this->roastModel->getPackagingSizeAnalysis($currentUser['id']);
        $recent = $this->roastModel->getRecentRoasts($currentUser['id'], 5);
        
        response(200, [
            'message' => 'Comprehensive roasting overview',
            'data' => [
                'overview' => $stats,
                'roast_levels' => $roastLevels,
                'brewing_methods' => $brewingMethods,
                'cupping_notes' => $cuppingNotes,
                'packaging_analysis' => $packaging,
                'recent_roasts' => $recent
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

    private function validateRoastedProductInput($input) {
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