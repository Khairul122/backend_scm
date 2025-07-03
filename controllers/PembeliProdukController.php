<?php

class PembeliProdukController
{
    private $pembeliProdukModel;

    public function __construct()
    {
        $this->pembeliProdukModel = new PembeliProdukModel();
    }

    public function getAllProducts()
    {
        $currentUser = $this->getCurrentUser();
        
        if (!$this->validateBuyerAccess($currentUser)) {
            return;
        }

        $filters = $this->buildFilters();
        $products = $this->pembeliProdukModel->getAllProducts($filters);
        $totalCount = $this->pembeliProdukModel->getProductCount($filters);

        $this->sendSuccessResponse('Products retrieved successfully', $products, [
            'total' => $totalCount,
            'limit' => $filters['limit'] ?? 20,
            'offset' => $filters['offset'] ?? 0,
            'current_page' => floor(($filters['offset'] ?? 0) / ($filters['limit'] ?? 20)) + 1,
            'total_pages' => ceil($totalCount / ($filters['limit'] ?? 20))
        ]);
    }

    public function getProductById($productId)
    {
        $currentUser = $this->getCurrentUser();
        
        if (!$this->validateBuyerAccess($currentUser)) {
            return;
        }

        $product = $this->pembeliProdukModel->getProductById($productId);
        
        if (!$product) {
            response(404, ['error' => 'Product not found or not available']);
            return;
        }

        $relatedProducts = $this->pembeliProdukModel->getRelatedProducts($productId, 5);
        $reviews = $this->pembeliProdukModel->getProductReviews($productId, 5, 0);
        
        $this->sendSuccessResponse('Product details retrieved successfully', [
            'product' => $product,
            'related_products' => $relatedProducts,
            'recent_reviews' => $reviews
        ]);
    }

    public function searchProducts()
    {
        $currentUser = $this->getCurrentUser();
        
        if (!$this->validateBuyerAccess($currentUser)) {
            return;
        }

        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            response(400, ['error' => 'Search query required']);
            return;
        }

        $filters = $this->buildFilters();
        $products = $this->pembeliProdukModel->searchProducts($query, $filters);
        
        $this->sendSuccessResponse('Search results retrieved successfully', $products, [
            'search_query' => $query,
            'pagination' => [
                'limit' => $filters['limit'] ?? 20,
                'offset' => $filters['offset'] ?? 0
            ]
        ]);
    }

    public function getProductsByCategory($categoryId)
    {
        $currentUser = $this->getCurrentUser();
        
        if (!$this->validateBuyerAccess($currentUser)) {
            return;
        }

        $filters = $this->buildFilters();
        $products = $this->pembeliProdukModel->getProductsByCategory($categoryId, $filters);
        
        $this->sendSuccessResponse('Products by category retrieved successfully', $products, [
            'category_id' => $categoryId,
            'pagination' => [
                'limit' => $filters['limit'] ?? 20,
                'offset' => $filters['offset'] ?? 0
            ]
        ]);
    }

    public function getProductsBySeller($sellerId)
    {
        $currentUser = $this->getCurrentUser();
        
        if (!$this->validateBuyerAccess($currentUser)) {
            return;
        }

        $filters = $this->buildFilters();
        $products = $this->pembeliProdukModel->getProductsBySeller($sellerId, $filters);
        $sellerInfo = $this->pembeliProdukModel->getSellerInfo($sellerId);
        
        $this->sendSuccessResponse('Products by seller retrieved successfully', [
            'seller_info' => $sellerInfo,
            'products' => $products
        ], [
            'pagination' => [
                'limit' => $filters['limit'] ?? 20,
                'offset' => $filters['offset'] ?? 0
            ]
        ]);
    }

    public function getFeaturedProducts()
    {
        $currentUser = $this->getCurrentUser();
        
        if (!$this->validateBuyerAccess($currentUser)) {
            return;
        }

        $limit = $_GET['limit'] ?? 10;
        $products = $this->pembeliProdukModel->getFeaturedProducts($limit);
        
        $this->sendSuccessResponse('Featured products retrieved successfully', $products);
    }

    public function getPopularProducts()
    {
        $currentUser = $this->getCurrentUser();
        
        if (!$this->validateBuyerAccess($currentUser)) {
            return;
        }

        $limit = $_GET['limit'] ?? 10;
        $products = $this->pembeliProdukModel->getPopularProducts($limit);
        
        $this->sendSuccessResponse('Popular products retrieved successfully', $products);
    }

    public function getNewProducts()
    {
        $currentUser = $this->getCurrentUser();
        
        if (!$this->validateBuyerAccess($currentUser)) {
            return;
        }

        $limit = $_GET['limit'] ?? 10;
        $products = $this->pembeliProdukModel->getNewProducts($limit);
        
        $this->sendSuccessResponse('New products retrieved successfully', $products);
    }

    public function getRelatedProducts($productId)
    {
        $currentUser = $this->getCurrentUser();
        
        if (!$this->validateBuyerAccess($currentUser)) {
            return;
        }

        $limit = $_GET['limit'] ?? 5;
        $products = $this->pembeliProdukModel->getRelatedProducts($productId, $limit);
        
        $this->sendSuccessResponse('Related products retrieved successfully', $products);
    }

    public function getProductReviews($productId)
    {
        $currentUser = $this->getCurrentUser();
        
        if (!$this->validateBuyerAccess($currentUser)) {
            return;
        }

        $limit = $_GET['limit'] ?? 10;
        $page = $_GET['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $reviews = $this->pembeliProdukModel->getProductReviews($productId, $limit, $offset);
        
        $this->sendSuccessResponse('Product reviews retrieved successfully', $reviews, [
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'offset' => $offset
            ]
        ]);
    }

    public function getCategories()
    {
        $currentUser = $this->getCurrentUser();
        
        if (!$this->validateBuyerAccess($currentUser)) {
            return;
        }

        $categories = $this->pembeliProdukModel->getCategories();
        
        $this->sendSuccessResponse('Categories retrieved successfully', $categories);
    }

    public function getPriceRange()
    {
        $currentUser = $this->getCurrentUser();
        
        if (!$this->validateBuyerAccess($currentUser)) {
            return;
        }

        $categoryId = $_GET['category_id'] ?? null;
        $priceRange = $this->pembeliProdukModel->getPriceRange($categoryId);
        
        $this->sendSuccessResponse('Price range retrieved successfully', $priceRange);
    }

    public function getSellerInfo($sellerId)
    {
        $currentUser = $this->getCurrentUser();
        
        if (!$this->validateBuyerAccess($currentUser)) {
            return;
        }

        $sellerInfo = $this->pembeliProdukModel->getSellerInfo($sellerId);
        
        if (!$sellerInfo) {
            response(404, ['error' => 'Seller not found']);
            return;
        }
        
        $this->sendSuccessResponse('Seller information retrieved successfully', $sellerInfo);
    }

    public function getProductFilters()
    {
        $currentUser = $this->getCurrentUser();
        
        if (!$this->validateBuyerAccess($currentUser)) {
            return;
        }

        $categories = $this->pembeliProdukModel->getCategories();
        $priceRange = $this->pembeliProdukModel->getPriceRange();
        
        $this->sendSuccessResponse('Product filters retrieved successfully', [
            'categories' => $categories,
            'price_range' => $priceRange,
            'sort_options' => $this->getSortOptions()
        ]);
    }

    public function getProductOverview()
    {
        $currentUser = $this->getCurrentUser();
        
        if (!$this->validateBuyerAccess($currentUser)) {
            return;
        }

        $featuredProducts = $this->pembeliProdukModel->getFeaturedProducts(6);
        $popularProducts = $this->pembeliProdukModel->getPopularProducts(6);
        $newProducts = $this->pembeliProdukModel->getNewProducts(6);
        $categories = $this->pembeliProdukModel->getCategories();

        $this->sendSuccessResponse('Product overview retrieved successfully', [
            'featured_products' => $featuredProducts,
            'popular_products' => $popularProducts,
            'new_products' => $newProducts,
            'categories' => $categories
        ]);
    }

    private function getCurrentUser()
    {
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

    private function validateBuyerAccess($currentUser)
    {
        if (!$currentUser || $currentUser['role'] !== 'pembeli') {
            response(403, ['error' => 'Access denied']);
            return false;
        }
        return true;
    }

    private function buildFilters()
    {
        $filters = [];

        if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
            $filters['kategori_id'] = (int)$_GET['category_id'];
        }

        if (isset($_GET['min_price']) && !empty($_GET['min_price'])) {
            $filters['min_price'] = (float)$_GET['min_price'];
        }

        if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
            $filters['max_price'] = (float)$_GET['max_price'];
        }

        if (isset($_GET['in_stock']) && $_GET['in_stock'] === 'true') {
            $filters['in_stock'] = true;
        }

        if (isset($_GET['seller_id']) && !empty($_GET['seller_id'])) {
            $filters['penjual_id'] = (int)$_GET['seller_id'];
        }

        if (isset($_GET['sort']) && !empty($_GET['sort'])) {
            $allowedSorts = ['newest', 'price_asc', 'price_desc', 'name_asc', 'name_desc', 'rating', 'popular'];
            if (in_array($_GET['sort'], $allowedSorts)) {
                $filters['sort'] = $_GET['sort'];
            }
        }

        if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
            $filters['limit'] = min(max((int)$_GET['limit'], 1), 100);
        }

        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = max((int)$_GET['page'], 1);
            $limit = $filters['limit'] ?? 20;
            $filters['offset'] = ($page - 1) * $limit;
        }

        return $filters;
    }

    private function getSortOptions()
    {
        return [
            ['value' => 'newest', 'label' => 'Terbaru'],
            ['value' => 'price_asc', 'label' => 'Harga Terendah'],
            ['value' => 'price_desc', 'label' => 'Harga Tertinggi'],
            ['value' => 'name_asc', 'label' => 'Nama A-Z'],
            ['value' => 'name_desc', 'label' => 'Nama Z-A'],
            ['value' => 'rating', 'label' => 'Rating Tertinggi'],
            ['value' => 'popular', 'label' => 'Terpopuler']
        ];
    }

    private function sendSuccessResponse($message, $data, $additionalData = [])
    {
        $responseData = [
            'message' => $message,
            'data' => $data
        ];

        if (!empty($additionalData)) {
            $responseData = array_merge($responseData, $additionalData);
        }

        response(200, $responseData);
    }
}