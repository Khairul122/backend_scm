<?php

require_once __DIR__ . '/../controllers/UlasanController.php';
require_once __DIR__ . '/../models/UlasanModel.php';

$router->get('/api/ulasan', function() {
    $controller = new UlasanController();
    $controller->getAllUlasan();
});

$router->get('/api/ulasan/search', function() {
    $controller = new UlasanController();
    $controller->searchUlasan();
});

$router->get('/api/ulasan/stats', function() {
    $controller = new UlasanController();
    $controller->getUlasanStats();
});

$router->get('/api/ulasan/quality', function() {
    $controller = new UlasanController();
    $controller->getReviewQuality();
});

$router->get('/api/ulasan/fake', function() {
    $controller = new UlasanController();
    $controller->getFakeReviews();
});

$router->get('/api/ulasan/inappropriate', function() {
    $controller = new UlasanController();
    $controller->getInappropriateReviews();
});

$router->get('/api/ulasan/trends', function() {
    $controller = new UlasanController();
    $controller->getReviewTrends();
});

$router->get('/api/ulasan/produk-ratings', function() {
    $controller = new UlasanController();
    $controller->getProdukRatings();
});

$router->get('/api/ulasan/suspicious-users', function() {
    $controller = new UlasanController();
    $controller->getSuspiciousUsers();
});

$router->get('/api/ulasan/recent', function() {
    $controller = new UlasanController();
    $controller->getRecentReviews();
});

$router->get('/api/ulasan/moderation', function() {
    $controller = new UlasanController();
    $controller->getReviewModeration();
});

$router->get('/api/ulasan/{id}', function($id) {
    $controller = new UlasanController();
    $controller->getUlasanById($id);
});

$router->post('/api/ulasan', function() {
    $controller = new UlasanController();
    $controller->createUlasan();
});

$router->post('/api/ulasan/bulk-delete', function() {
    $controller = new UlasanController();
    $controller->bulkDeleteReviews();
});

$router->post('/api/ulasan/cleanup-fake', function() {
    $controller = new UlasanController();
    $controller->cleanupFakeReviews();
});

$router->post('/api/ulasan/moderate-inappropriate', function() {
    $controller = new UlasanController();
    $controller->moderateInappropriate();
});

$router->put('/api/ulasan/{id}', function($id) {
    $controller = new UlasanController();
    $controller->updateUlasan($id);
});

$router->delete('/api/ulasan/{id}', function($id) {
    $controller = new UlasanController();
    $controller->deleteUlasan($id);
});