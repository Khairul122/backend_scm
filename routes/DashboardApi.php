<?php

require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../models/DashboardModel.php';

$router->get('/api/dashboard', function() {
    $controller = new DashboardController();
    $controller->getDashboard();
});

$router->get('/api/dashboard/admin', function() {
    $controller = new DashboardController();
    $controller->getAdminDashboard();
});

$router->get('/api/dashboard/pengepul', function() {
    $controller = new DashboardController();
    $controller->getPengepulDashboard();
});

$router->get('/api/dashboard/roasting', function() {
    $controller = new DashboardController();
    $controller->getRoastingDashboard();
});

$router->get('/api/dashboard/penjual', function() {
    $controller = new DashboardController();
    $controller->getPenjualDashboard();
});

$router->get('/api/dashboard/pembeli', function() {
    $controller = new DashboardController();
    $controller->getPembeliDashboard();
});