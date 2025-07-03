<?php

require_once __DIR__ . '/../controllers/BatchProduksiController.php';
require_once __DIR__ . '/../models/BatchProduksiModel.php';

$router->get('/api/batch-produksi', function() {
    $controller = new BatchProduksiController();
    $controller->getAllBatch();
});

$router->get('/api/batch-produksi/search', function() {
    $controller = new BatchProduksiController();
    $controller->searchBatch();
});

$router->get('/api/batch-produksi/stats', function() {
    $controller = new BatchProduksiController();
    $controller->getBatchStats();
});

$router->get('/api/batch-produksi/processing', function() {
    $controller = new BatchProduksiController();
    $controller->getProcessingBatches();
});

$router->get('/api/batch-produksi/trends', function() {
    $controller = new BatchProduksiController();
    $controller->getProductionTrends();
});

$router->get('/api/batch-produksi/productivity', function() {
    $controller = new BatchProduksiController();
    $controller->getPetaniProductivity();
});

$router->get('/api/batch-produksi/quality', function() {
    $controller = new BatchProduksiController();
    $controller->getQualityAnalysis();
});

$router->get('/api/batch-produksi/recent-pickups', function() {
    $controller = new BatchProduksiController();
    $controller->getRecentPickups();
});

$router->get('/api/batch-produksi/ready-sale', function() {
    $controller = new BatchProduksiController();
    $controller->getReadyForSale();
});

$router->get('/api/batch-produksi/inventory', function() {
    $controller = new BatchProduksiController();
    $controller->getInventoryStatus();
});

$router->get('/api/batch-produksi/price-analysis', function() {
    $controller = new BatchProduksiController();
    $controller->getPriceAnalysis();
});

$router->get('/api/batch-produksi/slow-moving', function() {
    $controller = new BatchProduksiController();
    $controller->getSlowMovingBatches();
});

$router->get('/api/batch-produksi/overview', function() {
    $controller = new BatchProduksiController();
    $controller->getProductionOverview();
});

$router->get('/api/batch-produksi/generate-code', function() {
    $controller = new BatchProduksiController();
    $controller->generateBatchCode();
});

$router->get('/api/batch-produksi/{id}', function($id) {
    $controller = new BatchProduksiController();
    $controller->getBatchById($id);
});

$router->post('/api/batch-produksi', function() {
    $controller = new BatchProduksiController();
    $controller->createBatch();
});

$router->post('/api/batch-produksi/pickup', function() {
    $controller = new BatchProduksiController();
    $controller->inputFromPickup();
});

$router->post('/api/batch-produksi/bulk-update', function() {
    $controller = new BatchProduksiController();
    $controller->bulkUpdateStatus();
});

$router->put('/api/batch-produksi/{id}', function($id) {
    $controller = new BatchProduksiController();
    $controller->updateBatch($id);
});

$router->patch('/api/batch-produksi/{id}/status', function($id) {
    $controller = new BatchProduksiController();
    $controller->updateBatchStatus($id);
});

$router->patch('/api/batch-produksi/{id}/quantity', function($id) {
    $controller = new BatchProduksiController();
    $controller->updateQuantity($id);
});

$router->patch('/api/batch-produksi/{id}/quality', function($id) {
    $controller = new BatchProduksiController();
    $controller->updateQualityScore($id);
});