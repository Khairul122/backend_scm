<?php

require_once __DIR__ . '/../controllers/OngkirCacheController.php';
require_once __DIR__ . '/../models/OngkirCacheModel.php';

$router->get('/api/ongkir-cache', function() {
    $controller = new OngkirCacheController();
    $controller->getAllCache();
});

$router->get('/api/ongkir-cache/params', function() {
    $controller = new OngkirCacheController();
    $controller->getCacheByParams();
});

$router->get('/api/ongkir-cache/active', function() {
    $controller = new OngkirCacheController();
    $controller->getActiveCache();
});

$router->get('/api/ongkir-cache/search', function() {
    $controller = new OngkirCacheController();
    $controller->searchCache();
});

$router->get('/api/ongkir-cache/stats', function() {
    $controller = new OngkirCacheController();
    $controller->getCacheStats();
});

$router->get('/api/ongkir-cache/hit-rate', function() {
    $controller = new OngkirCacheController();
    $controller->getCacheHitRate();
});

$router->get('/api/ongkir-cache/api-usage', function() {
    $controller = new OngkirCacheController();
    $controller->getApiUsageStats();
});

$router->get('/api/ongkir-cache/popular-routes', function() {
    $controller = new OngkirCacheController();
    $controller->getPopularRoutes();
});

$router->get('/api/ongkir-cache/by-courier', function() {
    $controller = new OngkirCacheController();
    $controller->getCacheByCourier();
});

$router->get('/api/ongkir-cache/expired', function() {
    $controller = new OngkirCacheController();
    $controller->getExpiredCache();
});

$router->get('/api/ongkir-cache/analytics', function() {
    $controller = new OngkirCacheController();
    $controller->getCacheAnalytics();
});

$router->get('/api/ongkir-cache/size', function() {
    $controller = new OngkirCacheController();
    $controller->getCacheSize();
});

$router->get('/api/ongkir-cache/{id}', function($id) {
    $controller = new OngkirCacheController();
    $controller->getCacheById($id);
});

$router->post('/api/ongkir-cache/generate', function() {
    $controller = new OngkirCacheController();
    $controller->generateCache();
});

$router->post('/api/ongkir-cache/refresh-expired', function() {
    $controller = new OngkirCacheController();
    $controller->refreshExpiredCache();
});

$router->post('/api/ongkir-cache/bulk-refresh', function() {
    $controller = new OngkirCacheController();
    $controller->bulkRefresh();
});

$router->post('/api/ongkir-cache/bulk-delete', function() {
    $controller = new OngkirCacheController();
    $controller->bulkDelete();
});

$router->post('/api/ongkir-cache/clear-old', function() {
    $controller = new OngkirCacheController();
    $controller->clearOldCache();
});

$router->post('/api/ongkir-cache/optimize', function() {
    $controller = new OngkirCacheController();
    $controller->optimizeCache();
});

$router->put('/api/ongkir-cache/{id}', function($id) {
    $controller = new OngkirCacheController();
    $controller->updateCache($id);
});

$router->patch('/api/ongkir-cache/{id}/refresh', function($id) {
    $controller = new OngkirCacheController();
    $controller->refreshCache($id);
});

$router->delete('/api/ongkir-cache/{id}', function($id) {
    $controller = new OngkirCacheController();
    $controller->deleteCache($id);
});

$router->delete('/api/ongkir-cache/expired', function() {
    $controller = new OngkirCacheController();
    $controller->clearExpiredCache();
});

$router->delete('/api/ongkir-cache/all', function() {
    $controller = new OngkirCacheController();
    $controller->clearAllCache();
});