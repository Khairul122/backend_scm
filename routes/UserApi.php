<?php

require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../models/UserModel.php';

$router->get('/api/users', function() {
    $controller = new UserController();
    $controller->getAllUsers();
});

$router->get('/api/users/search', function() {
    $controller = new UserController();
    $controller->searchUsers();
});

$router->get('/api/users/stats', function() {
    $controller = new UserController();
    $controller->getUserStats();
});

$router->get('/api/users/{id}', function() {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));
    $id = end($segments);
    
    $controller = new UserController();
    $controller->getUserById($id);
});

$router->post('/api/users', function() {
    $controller = new UserController();
    $controller->createUser();
});

$router->put('/api/users/{id}', function() {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));
    $id = end($segments);
    
    $controller = new UserController();
    $controller->updateUser($id);
});

$router->delete('/api/users/{id}', function() {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));
    $id = end($segments);
    
    $controller = new UserController();
    $controller->deleteUser($id);
});

$router->patch('/api/users/{id}/status', function() {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));
    $id = $segments[count($segments) - 2]; // ambil ID yang sebelum 'status'
    
    $controller = new UserController();
    $controller->updateUserStatus($id);
});

$router->patch('/api/users/{id}/password', function() {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $segments = explode('/', trim($uri, '/'));
    $id = $segments[count($segments) - 2]; // ambil ID yang sebelum 'password'
    
    $controller = new UserController();
    $controller->resetPassword($id);
});