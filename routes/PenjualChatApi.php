<?php

require_once __DIR__ . '/../controllers/PenjualChatController.php';
require_once __DIR__ . '/../models/PenjualChatModel.php';

$router->get('/api/penjual-chat/conversations', function() {
    $controller = new PenjualChatController();
    $controller->getConversations();
});

$router->get('/api/penjual-chat/messages/{produk_id}/{pembeli_id}', function($produk_id, $pembeli_id) {
    $controller = new PenjualChatController();
    $controller->getChatMessages($produk_id, $pembeli_id);
});

$router->get('/api/penjual-chat/history/{produk_id}/{pembeli_id}', function($produk_id, $pembeli_id) {
    $controller = new PenjualChatController();
    $controller->getConversationHistory($produk_id, $pembeli_id);
});

$router->get('/api/penjual-chat/search', function() {
    $controller = new PenjualChatController();
    $controller->searchConversations();
});

$router->get('/api/penjual-chat/statistics', function() {
    $controller = new PenjualChatController();
    $controller->getChatStatistics();
});

$router->get('/api/penjual-chat/products', function() {
    $controller = new PenjualChatController();
    $controller->getProductsWithChats();
});

$router->get('/api/penjual-chat/customers', function() {
    $controller = new PenjualChatController();
    $controller->getActiveCustomers();
});

$router->get('/api/penjual-chat/popular-products', function() {
    $controller = new PenjualChatController();
    $controller->getPopularInquiryProducts();
});

$router->get('/api/penjual-chat/unread-count', function() {
    $controller = new PenjualChatController();
    $controller->getUnreadCount();
});

$router->get('/api/penjual-chat/response-time', function() {
    $controller = new PenjualChatController();
    $controller->getResponseTimeAnalysis();
});

$router->get('/api/penjual-chat/trends', function() {
    $controller = new PenjualChatController();
    $controller->getChatTrends();
});

$router->get('/api/penjual-chat/frequent-questions', function() {
    $controller = new PenjualChatController();
    $controller->getFrequentQuestions();
});

$router->get('/api/penjual-chat/conversion-analysis', function() {
    $controller = new PenjualChatController();
    $controller->getChatConversionAnalysis();
});

$router->get('/api/penjual-chat/overview', function() {
    $controller = new PenjualChatController();
    $controller->getChatOverview();
});

$router->post('/api/penjual-chat/send', function() {
    $controller = new PenjualChatController();
    $controller->sendMessage();
});

$router->post('/api/penjual-chat/quick-reply', function() {
    $controller = new PenjualChatController();
    $controller->quickReply();
});

$router->patch('/api/penjual-chat/mark-read/{produk_id}/{pembeli_id}', function($produk_id, $pembeli_id) {
    $controller = new PenjualChatController();
    $controller->markConversationAsRead($produk_id, $pembeli_id);
});