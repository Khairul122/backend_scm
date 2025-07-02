<?php

require_once __DIR__ . '/../controllers/ReturController.php';
require_once __DIR__ . '/../models/ReturModel.php';

$router->get('/api/retur', function() {
    $controller = new ReturController();
    $controller->getAllRetur();
});

$router->get('/api/retur/search', function() {
    $controller = new ReturController();
    $controller->searchRetur();
});

$router->get('/api/retur/stats', function() {
    $controller = new ReturController();
    $controller->getReturStats();
});

$router->get('/api/retur/policy', function() {
    $controller = new ReturController();
    $controller->getReturPolicy();
});

$router->get('/api/retur/compliance', function() {
    $controller = new ReturController();
    $controller->getReturCompliance();
});

$router->get('/api/retur/analytics', function() {
    $controller = new ReturController();
    $controller->getReturAnalytics();
});

$router->get('/api/retur/products', function() {
    $controller = new ReturController();
    $controller->getReturByProduk();
});

$router->get('/api/retur/trends', function() {
    $controller = new ReturController();
    $controller->getReturTrends();
});

$router->get('/api/retur/outdated', function() {
    $controller = new ReturController();
    $controller->getOutdatedRetur();
});

$router->get('/api/retur/{id}', function($id) {
    $controller = new ReturController();
    $controller->getReturById($id);
});

$router->post('/api/retur', function() {
    $controller = new ReturController();
    $controller->createRetur();
});

$router->post('/api/retur/validate', function() {
    $controller = new ReturController();
    $controller->validateReturEligibility();
});

$router->post('/api/retur/bulk-update', function() {
    $controller = new ReturController();
    $controller->bulkUpdateStatus();
});

$router->put('/api/retur/{id}', function($id) {
    $controller = new ReturController();
    $controller->updateRetur($id);
});

$router->patch('/api/retur/{id}/status', function($id) {
    $controller = new ReturController();
    $controller->updateReturStatus($id);
});

$router->delete('/api/retur/{id}', function($id) {
    $controller = new ReturController();
    $controller->deleteRetur($id);
});