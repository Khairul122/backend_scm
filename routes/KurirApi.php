<?php

require_once __DIR__ . '/../controllers/KurirController.php';
require_once __DIR__ . '/../models/KurirModel.php';

$router->get('/api/kurir/search', function() {
    $controller = new KurirController();
    $controller->searchKurir();
});

$router->get('/api/kurir/stats', function() {
    $controller = new KurirController();
    $controller->getKurirStats();
});

$router->get('/api/kurir/performance', function() {
    $controller = new KurirController();
    $controller->getKurirPerformance();
});

$router->get('/api/kurir/delivery-time', function() {
    $controller = new KurirController();
    $controller->getKurirDeliveryTime();
});

$router->get('/api/kurir/cost-analysis', function() {
    $controller = new KurirController();
    $controller->getKurirCostAnalysis();
});

$router->get('/api/kurir/analytics', function() {
    $controller = new KurirController();
    $controller->getKurirAnalytics();
});

$router->get('/api/kurir/usage-stats', function() {
    $controller = new KurirController();
    $controller->getKurirUsageStats();
});

$router->get('/api/kurir/poor-performers', function() {
    $controller = new KurirController();
    $controller->getPoorPerformingKurir();
});

$router->get('/api/kurir/trends', function() {
    $controller = new KurirController();
    $controller->getKurirTrends();
});

$router->get('/api/kurir/available-codes', function() {
    $controller = new KurirController();
    $controller->getAvailableKurirCodes();
});

$router->get('/api/kurir', function() {
    $controller = new KurirController();
    $controller->getAllKurir();
});

$router->post('/api/kurir', function() {
    $controller = new KurirController();
    $controller->createKurir();
});

$router->put('/api/kurir', function() {
    $controller = new KurirController();
    $controller->updateKurir();
});

$router->patch('/api/kurir', function() {
    $controller = new KurirController();
    $controller->updateKurirStatus();
});

$router->delete('/api/kurir', function() {
    $controller = new KurirController();
    $controller->deleteKurir();
});

$router->post('/api/kurir/import', function() {
    $controller = new KurirController();
    $controller->importKurirFromApi();
});

$router->post('/api/kurir/bulk-update', function() {
    $controller = new KurirController();
    $controller->bulkUpdateStatus();
});

$router->post('/api/kurir/cleanup', function() {
    $controller = new KurirController();
    $controller->cleanupPoorPerformers();
});