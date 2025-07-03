<?php

require_once __DIR__ . '/../controllers/GudangController.php';
require_once __DIR__ . '/../models/GudangModel.php';

$router->get('/api/gudang', function() {
    $controller = new GudangController();
    $controller->getAllStokGudang();
});

$router->get('/api/gudang/search', function() {
    $controller = new GudangController();
    $controller->searchStock();
});

$router->get('/api/gudang/stock-levels', function() {
    $controller = new GudangController();
    $controller->getStockLevels();
});

$router->get('/api/gudang/multi-location', function() {
    $controller = new GudangController();
    $controller->getMultiLocationStock();
});

$router->get('/api/gudang/low-stock', function() {
    $controller = new GudangController();
    $controller->getLowStockItems();
});

$router->get('/api/gudang/movement', function() {
    $controller = new GudangController();
    $controller->getStockMovement();
});

$router->get('/api/gudang/aging', function() {
    $controller = new GudangController();
    $controller->getStockAging();
});

$router->get('/api/gudang/valuation', function() {
    $controller = new GudangController();
    $controller->getStockValuation();
});

$router->get('/api/gudang/turnover', function() {
    $controller = new GudangController();
    $controller->getStockTurnover();
});

$router->get('/api/gudang/capacity', function() {
    $controller = new GudangController();
    $controller->getCapacityByLocation();
});

$router->get('/api/gudang/alerts', function() {
    $controller = new GudangController();
    $controller->getStockAlerts();
});

$router->get('/api/gudang/overview', function() {
    $controller = new GudangController();
    $controller->getWarehouseOverview();
});

$router->get('/api/gudang/locations', function() {
    $controller = new GudangController();
    $controller->getAvailableLocations();
});

$router->get('/api/gudang/batch/{batchId}', function($batchId) {
    $controller = new GudangController();
    $controller->getStockByBatch($batchId);
});

$router->get('/api/gudang/stock/{id}', function($id) {
    $controller = new GudangController();
    $controller->getStokById($id);
});