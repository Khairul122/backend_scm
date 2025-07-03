<?php

require_once __DIR__ . '/../controllers/RoastProductController.php';
require_once __DIR__ . '/../models/RoastProductModel.php';

$router->get('/api/roast-products', function() {
    $controller = new RoastProductController();
    $controller->getAllRoastedProducts();
});

$router->get('/api/roast-products/search', function() {
    $controller = new RoastProductController();
    $controller->searchRoastedProducts();
});

$router->get('/api/roast-products/stats', function() {
    $controller = new RoastProductController();
    $controller->getRoastedProductStats();
});

$router->get('/api/roast-products/roast-levels', function() {
    $controller = new RoastProductController();
    $controller->getRoastLevelAnalysis();
});

$router->get('/api/roast-products/brewing-methods', function() {
    $controller = new RoastProductController();
    $controller->getBrewingMethodAnalysis();
});

$router->get('/api/roast-products/cupping-notes', function() {
    $controller = new RoastProductController();
    $controller->getCuppingNotesAnalysis();
});

$router->get('/api/roast-products/packaging', function() {
    $controller = new RoastProductController();
    $controller->getPackagingSizeAnalysis();
});

$router->get('/api/roast-products/recent', function() {
    $controller = new RoastProductController();
    $controller->getRecentRoasts();
});

$router->get('/api/roast-products/premium', function() {
    $controller = new RoastProductController();
    $controller->getPremiumRoasts();
});

$router->get('/api/roast-products/discontinued', function() {
    $controller = new RoastProductController();
    $controller->getDiscontinuedProducts();
});

$router->get('/api/roast-products/overview', function() {
    $controller = new RoastProductController();
    $controller->getRoastingOverview();
});

$router->get('/api/roast-products/{id}', function($id) {
    $controller = new RoastProductController();
    $controller->getRoastedProductById($id);
});

$router->post('/api/roast-products', function() {
    $controller = new RoastProductController();
    $controller->createRoastedProduct();
});

$router->post('/api/roast-products/bulk-profiles', function() {
    $controller = new RoastProductController();
    $controller->bulkUpdateRoastProfiles();
});

$router->post('/api/roast-products/bulk-pricing', function() {
    $controller = new RoastProductController();
    $controller->bulkUpdatePricing();
});

$router->put('/api/roast-products/{id}', function($id) {
    $controller = new RoastProductController();
    $controller->updateRoastedProduct($id);
});

$router->patch('/api/roast-products/{id}/profile', function($id) {
    $controller = new RoastProductController();
    $controller->updateRoastProfile($id);
});

$router->patch('/api/roast-products/{id}/pricing', function($id) {
    $controller = new RoastProductController();
    $controller->updatePricing($id);
});

$router->patch('/api/roast-products/{id}/discontinue', function($id) {
    $controller = new RoastProductController();
    $controller->discontinueProduct($id);
});

$router->delete('/api/roast-products/{id}', function($id) {
    $controller = new RoastProductController();
    $controller->deleteRoastedProduct($id);
});