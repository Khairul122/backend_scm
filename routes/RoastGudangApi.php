<?php

require_once __DIR__ . '/../controllers/RoastGudangController.php';
require_once __DIR__ . '/../models/RoastGudangModel.php';

$router->get('/api/roast-warehouse', function() {
    $controller = new RoastGudangController();
    $controller->getAllRoastedStock();
});

$router->get('/api/roast-warehouse/{id}', function($id) {
    $controller = new RoastGudangController();
    $controller->getRoastedStockById($id);
});

$router->get('/api/roast-warehouse/search', function() {
    $controller = new RoastGudangController();
    $controller->searchRoastedStock();
});

$router->get('/api/roast-warehouse/stock-levels', function() {
    $controller = new RoastGudangController();
    $controller->getRoastedStockLevels();
});

$router->get('/api/roast-warehouse/by-location', function() {
    $controller = new RoastGudangController();
    $controller->getRoastedStockByLocation();
});

$router->get('/api/roast-warehouse/low-stock', function() {
    $controller = new RoastGudangController();
    $controller->getLowRoastedStock();
});

$router->get('/api/roast-warehouse/aging', function() {
    $controller = new RoastGudangController();
    $controller->getRoastedStockAging();
});

$router->get('/api/roast-warehouse/valuation', function() {
    $controller = new RoastGudangController();
    $controller->getRoastedStockValuation();
});

$router->get('/api/roast-warehouse/roast-types', function() {
    $controller = new RoastGudangController();
    $controller->getRoastTypeAnalysis();
});

$router->get('/api/roast-warehouse/packaging', function() {
    $controller = new RoastGudangController();
    $controller->getPackagingSizeDistribution();
});

$router->get('/api/roast-warehouse/alerts', function() {
    $controller = new RoastGudangController();
    $controller->getRoastedStockAlerts();
});

$router->get('/api/roast-warehouse/overview', function() {
    $controller = new RoastGudangController();
    $controller->getRoastedWarehouseOverview();
});

$router->post('/api/roast-warehouse', function() {
    $controller = new RoastGudangController();
    $controller->createRoastedStock();
});

$router->post('/api/roast-warehouse/from-batch', function() {
    $controller = new RoastGudangController();
    $controller->addRoastedInventoryFromBatch();
});

$router->post('/api/roast-warehouse/bulk-transfer', function() {
    $controller = new RoastGudangController();
    $controller->bulkTransferRoastedStock();
});

$router->put('/api/roast-warehouse/{id}', function($id) {
    $controller = new RoastGudangController();
    $controller->updateRoastedStock($id);
});

$router->patch('/api/roast-warehouse/{id}/transfer', function($id) {
    $controller = new RoastGudangController();
    $controller->transferRoastedStock($id);
});

$router->patch('/api/roast-warehouse/{id}/sale', function($id) {
    $controller = new RoastGudangController();
    $controller->updateQuantityAfterSale($id);
});

$router->patch('/api/roast-warehouse/{id}/roasting', function($id) {
    $controller = new RoastGudangController();
    $controller->updateQuantityAfterRoasting($id);
});