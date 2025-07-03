<?php

require_once __DIR__ . '/../controllers/PembeliProdukController.php';
require_once __DIR__ . '/../models/PembeliProdukModel.php';

$router->get('/api/pembeli-products', function() {
    $controller = new PembeliProdukController();
    $controller->getAllProducts();
});

$router->get('/api/pembeli-products/{id}', function($id) {
    $controller = new PembeliProdukController();
    $controller->getProductById($id);
});

$router->get('/api/pembeli-products/search', function() {
    $controller = new PembeliProdukController();
    $controller->searchProducts();
});

$router->get('/api/pembeli-products/category/{category_id}', function($category_id) {
    $controller = new PembeliProdukController();
    $controller->getProductsByCategory($category_id);
});

$router->get('/api/pembeli-products/seller/{seller_id}', function($seller_id) {
    $controller = new PembeliProdukController();
    $controller->getProductsBySeller($seller_id);
});

$router->get('/api/pembeli-products/featured', function() {
    $controller = new PembeliProdukController();
    $controller->getFeaturedProducts();
});

$router->get('/api/pembeli-products/popular', function() {
    $controller = new PembeliProdukController();
    $controller->getPopularProducts();
});

$router->get('/api/pembeli-products/new', function() {
    $controller = new PembeliProdukController();
    $controller->getNewProducts();
});

$router->get('/api/pembeli-products/{id}/related', function($id) {
    $controller = new PembeliProdukController();
    $controller->getRelatedProducts($id);
});

$router->get('/api/pembeli-products/{id}/reviews', function($id) {
    $controller = new PembeliProdukController();
    $controller->getProductReviews($id);
});

$router->get('/api/pembeli-products/categories', function() {
    $controller = new PembeliProdukController();
    $controller->getCategories();
});

$router->get('/api/pembeli-products/price-range', function() {
    $controller = new PembeliProdukController();
    $controller->getPriceRange();
});

$router->get('/api/pembeli-products/seller-info/{seller_id}', function($seller_id) {
    $controller = new PembeliProdukController();
    $controller->getSellerInfo($seller_id);
});

$router->get('/api/pembeli-products/filters', function() {
    $controller = new PembeliProdukController();
    $controller->getProductFilters();
});

$router->get('/api/pembeli-products/overview', function() {
    $controller = new PembeliProdukController();
    $controller->getProductOverview();
});