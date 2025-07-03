<?php
class PenjualProdukController {
    private $penjualProdukModel;

    public function __construct() {
        $this->penjualProdukModel = new PenjualProdukModel();
    }

    public function getAllProducts() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied. Seller role required.']);
            return;
        }

        $kategoriId = $_GET['kategori'] ?? null;
        
        if ($kategoriId) {
            $products = $this->penjualProdukModel->getProductsByCategory($kategoriId, $currentUser['id']);
        } else {
            $products = $this->penjualProdukModel->getAllProducts($currentUser['id']);
        }
        
        response(200, [
            'message' => 'Products retrieved successfully',
            'data' => $products
        ]);
    }

    public function getProductById($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $product = $this->penjualProdukModel->getProductById($id, $currentUser['id']);
        
        if (!$product) {
            response(404, ['error' => 'Product not found or access denied']);
            return;
        }
        
        response(200, [
            'message' => 'Product details retrieved successfully',
            'data' => $product
        ]);
    }

    public function createProduct() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$this->validateProductInput($input)) {
            response(400, ['error' => 'Invalid input data. Required: nama_produk, deskripsi, harga, stok, kategori_id, berat']);
            return;
        }

        $input['status'] = $input['status'] ?? 'aktif';
        
        $productId = $this->penjualProdukModel->createProduct($input, $currentUser['id']);
        
        if ($productId) {
            $product = $this->penjualProdukModel->getProductById($productId, $currentUser['id']);
            response(201, [
                'message' => 'Product created successfully',
                'data' => $product
            ]);
        } else {
            response(500, ['error' => 'Failed to create product']);
        }
    }

    public function updateProduct($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        if (!$this->penjualProdukModel->checkProductOwnership($id, $currentUser['id'])) {
            response(404, ['error' => 'Product not found or access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($this->penjualProdukModel->updateProduct($id, $input, $currentUser['id'])) {
            $product = $this->penjualProdukModel->getProductById($id, $currentUser['id']);
            response(200, [
                'message' => 'Product updated successfully',
                'data' => $product
            ]);
        } else {
            response(500, ['error' => 'Failed to update product']);
        }
    }

    public function deleteProduct($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        if (!$this->penjualProdukModel->checkProductOwnership($id, $currentUser['id'])) {
            response(404, ['error' => 'Product not found or access denied']);
            return;
        }

        if ($this->penjualProdukModel->deleteProduct($id, $currentUser['id'])) {
            response(200, ['message' => 'Product deleted or deactivated successfully']);
        } else {
            response(500, ['error' => 'Failed to delete product']);
        }
    }

    public function searchProducts() {
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

        $products = $this->penjualProdukModel->searchProducts($query, $currentUser['id']);
        
        response(200, [
            'message' => 'Search results retrieved successfully',
            'data' => $products
        ]);
    }

    public function getProductStatistics() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $statistics = $this->penjualProdukModel->getProductStatistics($currentUser['id']);
        
        response(200, [
            'message' => 'Product statistics retrieved successfully',
            'data' => $statistics
        ]);
    }

    public function getTopSellingProducts() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $limit = $_GET['limit'] ?? 10;
        $products = $this->penjualProdukModel->getTopSellingProducts($currentUser['id'], $limit);
        
        response(200, [
            'message' => 'Top selling products retrieved successfully',
            'data' => $products
        ]);
    }

    public function getLowStockProducts() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $threshold = $_GET['threshold'] ?? 5;
        $products = $this->penjualProdukModel->getLowStockProducts($currentUser['id'], $threshold);
        
        response(200, [
            'message' => "Products with stock below {$threshold} units retrieved successfully",
            'data' => $products
        ]);
    }

    public function getProductPerformance() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $performance = $this->penjualProdukModel->getProductPerformance($currentUser['id']);
        
        response(200, [
            'message' => 'Product performance analysis retrieved successfully',
            'data' => $performance
        ]);
    }

    public function getRevenueByProduct() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        $revenue = $this->penjualProdukModel->getRevenueByProduct($currentUser['id'], $startDate, $endDate);
        
        response(200, [
            'message' => 'Revenue by product retrieved successfully',
            'data' => $revenue
        ]);
    }

    public function getCategoryAnalysis() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $analysis = $this->penjualProdukModel->getCategoryAnalysis($currentUser['id']);
        
        response(200, [
            'message' => 'Category analysis retrieved successfully',
            'data' => $analysis
        ]);
    }

    public function getPackagingSizeAnalysis() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $analysis = $this->penjualProdukModel->getPackagingSizeAnalysis($currentUser['id']);
        
        response(200, [
            'message' => 'Packaging size analysis retrieved successfully',
            'data' => $analysis
        ]);
    }

    public function getCompetitivePricing($produkId) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        if (!$this->penjualProdukModel->checkProductOwnership($produkId, $currentUser['id'])) {
            response(404, ['error' => 'Product not found or access denied']);
            return;
        }

        $pricing = $this->penjualProdukModel->getCompetitivePricing($currentUser['id'], $produkId);
        
        response(200, [
            'message' => 'Competitive pricing analysis retrieved successfully',
            'data' => $pricing
        ]);
    }

    public function updateStock($id) {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        if (!$this->penjualProdukModel->checkProductOwnership($id, $currentUser['id'])) {
            response(404, ['error' => 'Product not found or access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['stok']) || !is_numeric($input['stok']) || $input['stok'] < 0) {
            response(400, ['error' => 'Valid stock quantity required']);
            return;
        }

        if ($this->penjualProdukModel->updateStock($id, $input['stok'], $currentUser['id'])) {
            response(200, ['message' => 'Stock updated successfully']);
        } else {
            response(500, ['error' => 'Failed to update stock']);
        }
    }

    public function bulkUpdatePrices() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['products']) || !is_array($input['products'])) {
            response(400, ['error' => 'Products array required']);
            return;
        }

        foreach ($input['products'] as $product) {
            if (!isset($product['id']) || !isset($product['harga']) || !is_numeric($product['harga'])) {
                response(400, ['error' => 'Each product must have valid id and harga']);
                return;
            }
            
            if (!$this->penjualProdukModel->checkProductOwnership($product['id'], $currentUser['id'])) {
                response(403, ['error' => 'You can only update prices for your own products']);
                return;
            }
        }

        if ($this->penjualProdukModel->bulkUpdatePrices($input['products'], $currentUser['id'])) {
            response(200, [
                'message' => 'Prices updated successfully',
                'updated_count' => count($input['products'])
            ]);
        } else {
            response(500, ['error' => 'Failed to update prices']);
        }
    }

    public function getProductRecommendations() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $recommendations = $this->penjualProdukModel->getProductRecommendations($currentUser['id']);
        
        response(200, [
            'message' => 'Product recommendations retrieved successfully',
            'data' => $recommendations
        ]);
    }

    public function getProductOverview() {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || !in_array($currentUser['role'], ['penjual', 'admin'])) {
            response(403, ['error' => 'Access denied']);
            return;
        }

        $statistics = $this->penjualProdukModel->getProductStatistics($currentUser['id']);
        $topSelling = $this->penjualProdukModel->getTopSellingProducts($currentUser['id'], 5);
        $lowStock = $this->penjualProdukModel->getLowStockProducts($currentUser['id'], 5);
        $categoryAnalysis = $this->penjualProdukModel->getCategoryAnalysis($currentUser['id']);
        $recentProducts = array_slice($this->penjualProdukModel->getAllProducts($currentUser['id']), 0, 5);
        $recommendations = $this->penjualProdukModel->getProductRecommendations($currentUser['id']);

        response(200, [
            'message' => 'Product overview retrieved successfully',
            'data' => [
                'statistics' => $statistics,
                'top_selling' => $topSelling,
                'low_stock' => $lowStock,
                'category_analysis' => $categoryAnalysis,
                'recent_products' => $recentProducts,
                'recommendations' => $recommendations
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

    private function validateProductInput($input) {
        $required = ['nama_produk', 'deskripsi', 'harga', 'stok', 'kategori_id', 'berat'];
        
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

        if (!is_numeric($input['kategori_id']) || !in_array($input['kategori_id'], [1, 2])) {
            return false;
        }

        if (!is_numeric($input['berat']) || $input['berat'] <= 0) {
            return false;
        }

        if (isset($input['status']) && !in_array($input['status'], ['aktif', 'nonaktif'])) {
            return false;
        }

        return true;
    }
}