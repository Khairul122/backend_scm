<?php

require_once __DIR__ . '/../controllers/PesananController.php';
require_once __DIR__ . '/../models/PesananModel.php';

$router->get('/api/pesanan', function() {
    $controller = new PesananController();
    $controller->getAllPesanan();
});

$router->get('/api/pesanan/search', function() {
    $controller = new PesananController();
    $controller->searchPesanan();
});

$router->get('/api/pesanan/stats', function() {
    $controller = new PesananController();
    $controller->getPesananStats();
});

$router->get('/api/pesanan/fraud-patterns', function() {
    $controller = new PesananController();
    $controller->getFraudPatterns();
});

$router->get('/api/pesanan/suspicious', function() {
    $controller = new PesananController();
    $controller->getSuspiciousOrders();
});

$router->get('/api/pesanan/purchasing-patterns', function() {
    $controller = new PesananController();
    $controller->getPurchasingPatterns();
});

$router->get('/api/pesanan/revenue-analysis', function() {
    $controller = new PesananController();
    $controller->getRevenueAnalysis();
});

$router->get('/api/pesanan/disputes', function() {
    $controller = new PesananController();
    $controller->getDisputeOrders();
});

$router->get('/api/pesanan/high-value', function() {
    $controller = new PesananController();
    $controller->getHighValueOrders();
});

$router->get('/api/pesanan/oversight', function() {
    $controller = new PesananController();
    $controller->getTransactionOversight();
});

$router->get('/api/pesanan/{id}', function($id) {
    $controller = new PesananController();
    $controller->getPesananById($id);
});

$router->get('/api/pesanan/{id}/details', function($id) {
    $controller = new PesananController();
    $controller->getDetailPesanan($id);
});

$router->post('/api/pesanan', function() {
    $controller = new PesananController();
    $controller->createPesanan();
});

$router->post('/api/pesanan/details', function() {
    $controller = new PesananController();
    $controller->createDetailPesanan();
});

$router->post('/api/pesanan/bulk-update', function() {
    $controller = new PesananController();
    $controller->bulkUpdateStatus();
});

$router->put('/api/pesanan/{id}', function($id) {
    $controller = new PesananController();
    $controller->updatePesanan($id);
});

$router->put('/api/pesanan/details/{id}', function($id) {
    $controller = new PesananController();
    $controller->updateDetailPesanan($id);
});

$router->patch('/api/pesanan/{id}/status', function($id) {
    $controller = new PesananController();
    $controller->updatePesananStatus($id);
});

$router->patch('/api/pesanan/{id}/cancel-fraud', function($id) {
    $controller = new PesananController();
    $controller->cancelFraudulentOrder($id);
});

$router->delete('/api/pesanan/{id}', function($id) {
    $controller = new PesananController();
    $controller->deletePesanan($id);
});

$router->delete('/api/pesanan/details/{id}', function($id) {
    $controller = new PesananController();
    $controller->deleteDetailPesanan($id);
});