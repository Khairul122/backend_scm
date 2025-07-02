<?php

require_once __DIR__ . '/../controllers/InformasiController.php';
require_once __DIR__ . '/../models/InformasiModel.php';

$router->get('/api/informasi', function() {
    $controller = new InformasiController();
    $controller->getAllInformasi();
});

$router->get('/api/informasi/search', function() {
    $controller = new InformasiController();
    $controller->searchInformasi();
});

$router->get('/api/informasi/stats', function() {
    $controller = new InformasiController();
    $controller->getInformasiStats();
});

$router->get('/api/informasi/popular', function() {
    $controller = new InformasiController();
    $controller->getPopularInformasi();
});

$router->get('/api/informasi/recent', function() {
    $controller = new InformasiController();
    $controller->getRecentInformasi();
});

$router->get('/api/informasi/outdated', function() {
    $controller = new InformasiController();
    $controller->getOutdatedInformasi();
});

$router->get('/api/informasi/performance', function() {
    $controller = new InformasiController();
    $controller->getInformasiPerformance();
});

$router->get('/api/informasi/topics', function() {
    $controller = new InformasiController();
    $controller->getInformasiByTopic();
});

$router->get('/api/informasi/authors', function() {
    $controller = new InformasiController();
    $controller->getAuthorStats();
});

$router->get('/api/informasi/{id}', function($id) {
    $controller = new InformasiController();
    $controller->getInformasiById($id);
});

$router->post('/api/informasi', function() {
    $controller = new InformasiController();
    $controller->createInformasi();
});

$router->post('/api/informasi/cleanup', function() {
    $controller = new InformasiController();
    $controller->bulkDeleteOutdated();
});

$router->put('/api/informasi/{id}', function($id) {
    $controller = new InformasiController();
    $controller->updateInformasi($id);
});

$router->delete('/api/informasi/{id}', function($id) {
    $controller = new InformasiController();
    $controller->deleteInformasi($id);
});