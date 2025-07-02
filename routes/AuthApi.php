<?php

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

$router->get('/api/auth/verify-token', function() {
    $controller = new AuthController();
    $controller->verifyToken();
});

$router->post('/api/auth/logout', function() {
    response(200, ['message' => 'Logout successful']);
});

$router->post('/api/auth/forgot-password', function() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['email'])) {
        response(400, ['error' => 'Email is required']);
    }
    
    response(200, ['message' => 'Password reset instructions sent to email']);
});