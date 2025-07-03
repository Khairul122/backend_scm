<?php

require_once __DIR__ . '/../controllers/RoastProduksiController.php';
require_once __DIR__ . '/../models/RoastProduksiModel.php';

$router->get('/api/roast-batches', function() {
    $controller = new RoastProduksiController();
    $controller->getAvailableGreenBeanBatches();
});

$router->get('/api/roast-batches/search', function() {
    $controller = new RoastProduksiController();
    $controller->searchAvailableBatches();
});

$router->get('/api/roast-batches/stats', function() {
    $controller = new RoastProduksiController();
    $controller->getGreenBeanStats();
});

$router->get('/api/roast-batches/freshness', function() {
    $controller = new RoastProduksiController();
    $controller->getFreshnessBuckets();
});

$router->get('/api/roast-batches/price-analysis', function() {
    $controller = new RoastProduksiController();
    $controller->getPriceRangeAnalysis();
});

$router->get('/api/roast-batches/recent', function() {
    $controller = new RoastProduksiController();
    $controller->getRecentlyAvailable();
});

$router->get('/api/roast-batches/premium', function() {
    $controller = new RoastProduksiController();
    $controller->getPremiumBatches();
});

$router->get('/api/roast-batches/suitability', function() {
    $controller = new RoastProduksiController();
    $controller->getRoastingSuitability();
});

$router->get('/api/roast-batches/origins', function() {
    $controller = new RoastProduksiController();
    $controller->getOriginAnalysis();
});

$router->get('/api/roast-batches/turnover', function() {
    $controller = new RoastProduksiController();
    $controller->getInventoryTurnover();
});

$router->get('/api/roast-batches/quality-grades', function() {
    $controller = new RoastProduksiController();
    $controller->getQualityGrades();
});

$router->get('/api/roast-batches/overview', function() {
    $controller = new RoastProduksiController();
    $controller->getGreenBeanOverview();
});

$router->get('/api/roast-batches/{id}', function($id) {
    $controller = new RoastProduksiController();
    $controller->getBatchById($id);
});

$router->patch('/api/roast-batches/{id}/roasting-info', function($id) {
    $controller = new RoastProduksiController();
    $controller->updateRoastingInformation($id);
});

$router->patch('/api/roast-batches/{id}/yields', function($id) {
    $controller = new RoastProduksiController();
    $controller->updateYields($id);
});

$router->patch('/api/roast-batches/{id}/mark-roasted', function($id) {
    $controller = new RoastProduksiController();
    $controller->markAsRoasted($id);
});