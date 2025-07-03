<?php

require_once __DIR__ . '/../controllers/PenjualProdukController.php';
require_once __DIR__ . '/../models/PenjualProdukModel.php';

$router->get('/api/penjual-products', function() {
    $controller = new PenjualProdukController();
    $controller->getAllProducts();
});

$router->get('/api/penjual-products/{id}', function($id) {
    $controller = new PenjualProdukController();
    $controller->getProductById($id);
});

$router->get('/api/penjual-products/search', function() {
    $controller = new PenjualProdukController();
    $controller->searchProducts();
});

$router->get('/api/penjual-products/statistics', function() {
    $controller = new PenjualProdukController();
    $controller->getProductStatistics();
});

$router->get('/api/penjual-products/top-selling', function() {
    $controller = new PenjualProdukController();
    $controller->getTopSellingProducts();
});

$router->get('/api/penjual-products/low-stock', function() {
    $controller = new PenjualProdukController();
    $controller->getLowStockProducts();
});

$router->get('/api/penjual-products/performance', function() {
    $controller = new PenjualProdukController();
    $controller->getProductPerformance();
});

$router->get('/api/penjual-products/revenue', function() {
    $controller = new PenjualProdukController();
    $controller->getRevenueByProduct();
});

$router->get('/api/penjual-products/category-analysis', function() {
    $controller = new PenjualProdukController();
    $controller->getCategoryAnalysis();
});

$router->get('/api/penjual-products/packaging-analysis', function() {
    $controller = new PenjualProdukController();
    $controller->getPackagingSizeAnalysis();
});

$router->get('/api/penjual-products/competitive-pricing/{produk_id}', function($produk_id) {
    $controller = new PenjualProdukController();
    $controller->getCompetitivePricing($produk_id);
});

$router->get('/api/penjual-products/recommendations', function() {
    $controller = new PenjualProdukController();
    $controller->getProductRecommendations();
});

$router->get('/api/penjual-products/overview', function() {
    $controller = new PenjualProdukController();
    $controller->getProductOverview();
});

$router->post('/api/penjual-products', function() {
    $controller = new PenjualProdukController();
    $controller->createProduct();
});

$router->post('/api/penjual-products/bulk-update-prices', function() {
    $controller = new PenjualProdukController();
    $controller->bulkUpdatePrices();
});

$router->put('/api/penjual-products/{id}', function($id) {
    $controller = new PenjualProdukController();
    $controller->updateProduct($id);
});

$router->patch('/api/penjual-products/{id}/stock', function($id) {
    $controller = new PenjualProdukController();
    $controller->updateStock($id);
});

$router->delete('/api/penjual-products/{id}', function($id) {
    $controller = new PenjualProdukController();
    $controller->deleteProduct($id);
});