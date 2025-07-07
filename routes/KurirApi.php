<?php

require_once __DIR__ . '/../controllers/KurirController.php';
require_once __DIR__ . '/../models/KurirModel.php';

$kurirController = new KurirController();

$router->get('/api/kurir/available-codes', function() use ($kurirController) {
    $kurirController->getAvailableKurirCodes();
});

$router->get('/api/kurir/search', function() use ($kurirController) {
    $kurirController->searchKurir();
});

$router->get('/api/kurir/stats', function() use ($kurirController) {
    $kurirController->getKurirStats();
});

$router->get('/api/kurir/performance', function() use ($kurirController) {
    $kurirController->getKurirPerformance();
});

$router->get('/api/kurir/delivery-time', function() use ($kurirController) {
    $kurirController->getKurirDeliveryTime();
});

$router->get('/api/kurir/cost-analysis', function() use ($kurirController) {
    $kurirController->getKurirCostAnalysis();
});

$router->get('/api/kurir/analytics', function() use ($kurirController) {
    $kurirController->getKurirAnalytics();
});

$router->get('/api/kurir/usage-stats', function() use ($kurirController) {
    $kurirController->getKurirUsageStats();
});

$router->get('/api/kurir/poor-performers', function() use ($kurirController) {
    $kurirController->getPoorPerformingKurir();
});

$router->get('/api/kurir/trends', function() use ($kurirController) {
    $kurirController->getKurirTrends();
});

$router->get('/api/kurir', function() use ($kurirController) {
    $kurirController->getAllKurir();
});

$router->post('/api/kurir', function() use ($kurirController) {
    $kurirController->createKurir();
});

$router->put('/api/kurir', function() use ($kurirController) {
    $kurirController->updateKurir();
});

$router->patch('/api/kurir', function() use ($kurirController) {
    $kurirController->updateKurirStatus();
});

$router->delete('/api/kurir', function() use ($kurirController) {
    $kurirController->deleteKurir();
});

$router->post('/api/kurir/import', function() use ($kurirController) {
    $kurirController->importKurirFromApi();
});

$router->post('/api/kurir/bulk-update', function() use ($kurirController) {
    $kurirController->bulkUpdateStatus();
});

$router->post('/api/kurir/cleanup', function() use ($kurirController) {
    $kurirController->cleanupPoorPerformers();
});