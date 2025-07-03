<?php

require_once __DIR__ . '/../controllers/PenjualGudangController.php';
require_once __DIR__ . '/../models/PenjualGudangModel.php';

$router->get('/api/penjual-inventory', function() {
    $controller = new PenjualGudangController();
    $controller->getAllInventory();
});

$router->get('/api/penjual-inventory/{id}', function($id) {
    $controller = new PenjualGudangController();
    $controller->getInventoryById($id);
});

$router->get('/api/penjual-inventory/search', function() {
    $controller = new PenjualGudangController();
    $controller->searchInventory();
});

$router->get('/api/penjual-inventory/statistics', function() {
    $controller = new PenjualGudangController();
    $controller->getInventoryStatistics();
});

$router->get('/api/penjual-inventory/stock-levels', function() {
    $controller = new PenjualGudangController();
    $controller->getStockLevels();
});

$router->get('/api/penjual-inventory/location-analysis', function() {
    $controller = new PenjualGudangController();
    $controller->getLocationAnalysis();
});

$router->get('/api/penjual-inventory/low-stock', function() {
    $controller = new PenjualGudangController();
    $controller->getLowStockItems();
});

$router->get('/api/penjual-inventory/aging', function() {
    $controller = new PenjualGudangController();
    $controller->getInventoryAging();
});

$router->get('/api/penjual-inventory/valuation', function() {
    $controller = new PenjualGudangController();
    $controller->getInventoryValuation();
});

$router->get('/api/penjual-inventory/category-analysis', function() {
    $controller = new PenjualGudangController();
    $controller->getCategoryStockAnalysis();
});

$router->get('/api/penjual-inventory/movements', function() {
    $controller = new PenjualGudangController();
    $controller->getInventoryMovements();
});

$router->get('/api/penjual-inventory/alerts', function() {
    $controller = new PenjualGudangController();
    $controller->getStockAlerts();
});

$router->get('/api/penjual-inventory/overview', function() {
    $controller = new PenjualGudangController();
    $controller->getInventoryOverview();
});

$router->post('/api/penjual-inventory', function() {
    $controller = new PenjualGudangController();
    $controller->addInventory();
});

$router->post('/api/penjual-inventory/adjust-sale', function() {
    $controller = new PenjualGudangController();
    $controller->adjustStockAfterSale();
});

$router->post('/api/penjual-inventory/restock', function() {
    $controller = new PenjualGudangController();
    $controller->restockInventory();
});

$router->put('/api/penjual-inventory/{id}', function($id) {
    $controller = new PenjualGudangController();
    $controller->updateInventory($id);
});

$router->patch('/api/penjual-inventory/{id}/transfer', function($id) {
    $controller = new PenjualGudangController();
    $controller->transferInventory($id);
});