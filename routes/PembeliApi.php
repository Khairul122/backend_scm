<?php

require_once __DIR__ . '/../controllers/PembeliController.php';
require_once __DIR__ . '/../models/PembeliModel.php';

$router->post('/api/pembeli/register', function() {
    $controller = new PembeliController();
    $controller->register();
});

$router->get('/api/pembeli/profile', function() {
    $controller = new PembeliController();
    $controller->getProfile();
});

$router->put('/api/pembeli/profile', function() {
    $controller = new PembeliController();
    $controller->updateProfile();
});

$router->patch('/api/pembeli/change-password', function() {
    $controller = new PembeliController();
    $controller->changePassword();
});

$router->post('/api/pembeli/deactivate-account', function() {
    $controller = new PembeliController();
    $controller->requestAccountDeactivation();
});

$router->get('/api/pembeli/orders', function() {
    $controller = new PembeliController();
    $controller->getOrderHistory();
});

$router->get('/api/pembeli/addresses', function() {
    $controller = new PembeliController();
    $controller->getShippingAddresses();
});

$router->post('/api/pembeli/addresses', function() {
    $controller = new PembeliController();
    $controller->addShippingAddress();
});

$router->put('/api/pembeli/addresses/{id}', function($id) {
    $controller = new PembeliController();
    $controller->updateShippingAddress($id);
});

$router->delete('/api/pembeli/addresses/{id}', function($id) {
    $controller = new PembeliController();
    $controller->deleteShippingAddress($id);
});

$router->patch('/api/pembeli/addresses/{id}/set-default', function($id) {
    $controller = new PembeliController();
    $controller->setDefaultAddress($id);
});

$router->get('/api/pembeli/wishlist', function() {
    $controller = new PembeliController();
    $controller->getWishlist();
});

$router->get('/api/pembeli/summary', function() {
    $controller = new PembeliController();
    $controller->getAccountSummary();
});

$router->get('/api/pembeli/activity', function() {
    $controller = new PembeliController();
    $controller->getRecentActivity();
});

$router->get('/api/pembeli/order-statistics', function() {
    $controller = new PembeliController();
    $controller->getOrderStatistics();
});

$router->get('/api/pembeli/dashboard', function() {
    $controller = new PembeliController();
    $controller->getDashboard();
});