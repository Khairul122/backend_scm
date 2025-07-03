<?php

require_once __DIR__ . '/../controllers/PenjualPesananController.php';
require_once __DIR__ . '/../models/PenjualPesananModel.php';

$router->get('/api/penjual-orders', function() {
    $controller = new PenjualPesananController();
    $controller->getAllOrders();
});

$router->get('/api/penjual-orders/{id}', function($id) {
    $controller = new PenjualPesananController();
    $controller->getOrderById($id);
});

$router->get('/api/penjual-orders/search', function() {
    $controller = new PenjualPesananController();
    $controller->searchOrders();
});

$router->get('/api/penjual-orders/statistics', function() {
    $controller = new PenjualPesananController();
    $controller->getOrderStatistics();
});

$router->get('/api/penjual-orders/recent', function() {
    $controller = new PenjualPesananController();
    $controller->getRecentOrders();
});

$router->get('/api/penjual-orders/top-customers', function() {
    $controller = new PenjualPesananController();
    $controller->getTopCustomers();
});

$router->get('/api/penjual-orders/top-products', function() {
    $controller = new PenjualPesananController();
    $controller->getTopSellingProducts();
});

$router->get('/api/penjual-orders/date-range', function() {
    $controller = new PenjualPesananController();
    $controller->getOrdersByDateRange();
});

$router->get('/api/penjual-orders/revenue-analysis', function() {
    $controller = new PenjualPesananController();
    $controller->getRevenueAnalysis();
});

$router->get('/api/penjual-orders/trends', function() {
    $controller = new PenjualPesananController();
    $controller->getOrderTrends();
});

$router->get('/api/penjual-orders/payment-analysis', function() {
    $controller = new PenjualPesananController();
    $controller->getPaymentMethodAnalysis();
});

$router->get('/api/penjual-orders/shipping-analysis', function() {
    $controller = new PenjualPesananController();
    $controller->getShippingAnalysis();
});

$router->get('/api/penjual-orders/alerts', function() {
    $controller = new PenjualPesananController();
    $controller->getOrderAlerts();
});

$router->get('/api/penjual-orders/overview', function() {
    $controller = new PenjualPesananController();
    $controller->getOrderOverview();
});

$router->put('/api/penjual-orders/{id}/status', function($id) {
    $controller = new PenjualPesananController();
    $controller->updateOrderStatus($id);
});

$router->patch('/api/penjual-orders/{id}/shipping', function($id) {
    $controller = new PenjualPesananController();
    $controller->updateShippingInfo($id);
});

$router->patch('/api/penjual-orders/{id}/confirm', function($id) {
    $controller = new PenjualPesananController();
    $controller->confirmOrder($id);
});

$router->patch('/api/penjual-orders/{id}/process', function($id) {
    $controller = new PenjualPesananController();
    $controller->processOrder($id);
});

$router->patch('/api/penjual-orders/{id}/ship', function($id) {
    $controller = new PenjualPesananController();
    $controller->shipOrder($id);
});

$router->patch('/api/penjual-orders/{id}/deliver', function($id) {
    $controller = new PenjualPesananController();
    $controller->deliverOrder($id);
});

$router->patch('/api/penjual-orders/{id}/cancel', function($id) {
    $controller = new PenjualPesananController();
    $controller->cancelOrder($id);
});