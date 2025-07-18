<?php
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../models/AuthModel.php';

$router->post('/api/auth/register', function() {
    $controller = new AuthController();
    $controller->register();
});

$router->post('/api/auth/login', function() {
    $controller = new AuthController();
    $controller->login();
});

$router->get('/api/auth/profile', function() {
    $controller = new AuthController();
    $controller->profile();
});

$router->put('/api/auth/profile', function() {
    $controller = new AuthController();
    $controller->updateProfile();
});

$router->post('/api/auth/change-password', function() {
    $controller = new AuthController();
    $controller->changePassword();
});

$router->post('/api/auth/logout', function() {
    $controller = new AuthController();
    $controller->logout();
});

$router->post('/api/auth/forgot-password', function() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['email'])) {
        response(400, ['error' => 'Email is required']);
    }
    
    response(200, ['message' => 'Password reset instructions sent to email']);
});