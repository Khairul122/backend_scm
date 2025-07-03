<?php

require_once __DIR__ . '/../controllers/LimitProdukController.php';
require_once __DIR__ . '/../models/LimitProdukModel.php';

$router->get('/api/limit-produk', function() {
    $controller = new LimitProdukController();
    $controller->getAllProduk();
});

$router->get('/api/limit-produk/search', function() {
    $controller = new LimitProdukController();
    $controller->searchProduk();
});

$router->get('/api/limit-produk/stats', function() {
    $controller = new LimitProdukController();
    $controller->getProdukStats();
});

$router->get('/api/limit-produk/low-stock', function() {
    $controller = new LimitProdukController();
    $controller->getLowStockProduk();
});

$router->get('/api/limit-produk/wholesale', function() {
    $controller = new LimitProdukController();
    $controller->getProdukForWholesale();
});

$router->get('/api/limit-produk/varieties', function() {
    $controller = new LimitProdukController();
    $controller->getGreenBeanVarieties();
});

$router->get('/api/limit-produk/recent', function() {
    $controller = new LimitProdukController();
    $controller->getRecentlyUpdated();
});

$router->get('/api/limit-produk/high-value', function() {
    $controller = new LimitProdukController();
    $controller->getHighValueProducts();
});

$router->get('/api/limit-produk/{id}', function($id) {
    $controller = new LimitProdukController();
    $controller->getProdukById($id);
});

$router->post('/api/limit-produk', function() {
    $controller = new LimitProdukController();
    $controller->createProduk();
});

$router->post('/api/limit-produk/bulk-prices', function() {
    $controller = new LimitProdukController();
    $controller->bulkUpdatePrices();
});

$router->post('/api/limit-produk/bulk-status', function() {
    $controller = new LimitProdukController();
    $controller->bulkUpdateStatus();
});

$router->put('/api/limit-produk/{id}', function($id) {
    $controller = new LimitProdukController();
    $controller->updateProduk($id);
});

$router->patch('/api/limit-produk/{id}/price', function($id) {
    $controller = new LimitProdukController();
    $controller->updateHarga($id);
});

$router->patch('/api/limit-produk/{id}/stock', function($id) {
    $controller = new LimitProdukController();
    $controller->updateStok($id);
});

$router->patch('/api/limit-produk/{id}/status', function($id) {
    $controller = new LimitProdukController();
    $controller->updateStatus($id);
});

$router->delete('/api/limit-produk/{id}', function($id) {
    $controller = new LimitProdukController();
    $controller->deleteProduk($id);
});