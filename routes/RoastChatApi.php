<?php

require_once __DIR__ . '/../controllers/RoastChatController.php';
require_once __DIR__ . '/../models/RoastChatModel.php';

$router->get('/api/roast-chat/conversations', function() {
    $controller = new RoastChatController();
    $controller->getConversations();
});

$router->get('/api/roast-chat/messages/{produk_id}/{pembeli_id}', function($produk_id, $pembeli_id) {
    $controller = new RoastChatController();
    $controller->getChatMessages($produk_id, $pembeli_id);
});

$router->get('/api/roast-chat/history/{produk_id}/{pembeli_id}', function($produk_id, $pembeli_id) {
    $controller = new RoastChatController();
    $controller->getConversationHistory($produk_id, $pembeli_id);
});

$router->get('/api/roast-chat/search', function() {
    $controller = new RoastChatController();
    $controller->searchConversations();
});

$router->get('/api/roast-chat/statistics', function() {
    $controller = new RoastChatController();
    $controller->getChatStatistics();
});

$router->get('/api/roast-chat/products', function() {
    $controller = new RoastChatController();
    $controller->getProductsWithChats();
});

$router->get('/api/roast-chat/customers', function() {
    $controller = new RoastChatController();
    $controller->getRecentCustomers();
});

$router->get('/api/roast-chat/popular-products', function() {
    $controller = new RoastChatController();
    $controller->getPopularProducts();
});

$router->get('/api/roast-chat/unread-count', function() {
    $controller = new RoastChatController();
    $controller->getUnreadCount();
});

$router->get('/api/roast-chat/analytics', function() {
    $controller = new RoastChatController();
    $controller->getChatAnalytics();
});

$router->get('/api/roast-chat/overview', function() {
    $controller = new RoastChatController();
    $controller->getChatOverview();
});

$router->post('/api/roast-chat/send', function() {
    $controller = new RoastChatController();
    $controller->sendMessage();
});