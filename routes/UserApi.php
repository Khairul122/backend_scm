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

$router->get('/api/users/{id}', function($id) {
    $controller = new UserController();
    $controller->getUserById($id);
});

$router->post('/api/users', function() {
    $controller = new UserController();
    $controller->createUser();
});

$router->put('/api/users/{id}', function($id) {
    $controller = new UserController();
    $controller->updateUser($id);
});

$router->delete('/api/users/{id}', function($id) {
    $controller = new UserController();
    $controller->deleteUser($id);
});

$router->patch('/api/users/{id}/status', function($id) {
    $controller = new UserController();
    $controller->updateUserStatus($id);
});

$router->patch('/api/users/{id}/password', function($id) {
    $controller = new UserController();
    $controller->resetPassword($id);
});