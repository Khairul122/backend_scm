<?php

require_once __DIR__ . '/../controllers/RoastUlasanController.php';
require_once __DIR__ . '/../models/RoastUlasanModel.php';

$router->get('/api/roast-reviews', function() {
    $controller = new RoastUlasanController();
    $controller->getProductReviews();
});

$router->get('/api/roast-reviews/product/{produk_id}', function($produk_id) {
    $controller = new RoastUlasanController();
    $controller->getProductReviews($produk_id);
});

$router->get('/api/roast-reviews/review/{review_id}', function($review_id) {
    $controller = new RoastUlasanController();
    $controller->getReviewById($review_id);
});

$router->get('/api/roast-reviews/statistics', function() {
    $controller = new RoastUlasanController();
    $controller->getReviewStatistics();
});

$router->get('/api/roast-reviews/product-ratings', function() {
    $controller = new RoastUlasanController();
    $controller->getProductRatings();
});

$router->get('/api/roast-reviews/recent', function() {
    $controller = new RoastUlasanController();
    $controller->getRecentReviews();
});

$router->get('/api/roast-reviews/top-rated', function() {
    $controller = new RoastUlasanController();
    $controller->getTopRatedProducts();
});

$router->get('/api/roast-reviews/customers', function() {
    $controller = new RoastUlasanController();
    $controller->getCustomerReviews();
});

$router->get('/api/roast-reviews/search', function() {
    $controller = new RoastUlasanController();
    $controller->searchReviews();
});

$router->get('/api/roast-reviews/rating/{rating}', function($rating) {
    $controller = new RoastUlasanController();
    $controller->getReviewsByRating($rating);
});

$router->get('/api/roast-reviews/trends', function() {
    $controller = new RoastUlasanController();
    $controller->getReviewTrends();
});

$router->get('/api/roast-reviews/analytics', function() {
    $controller = new RoastUlasanController();
    $controller->getReviewAnalytics();
});

$router->get('/api/roast-reviews/comparison', function() {
    $controller = new RoastUlasanController();
    $controller->getProductComparison();
});

$router->get('/api/roast-reviews/insights', function() {
    $controller = new RoastUlasanController();
    $controller->getReviewInsights();
});

$router->get('/api/roast-reviews/monthly-summary', function() {
    $controller = new RoastUlasanController();
    $controller->getMonthlyReviewSummary();
});

$router->get('/api/roast-reviews/overview', function() {
    $controller = new RoastUlasanController();
    $controller->getReviewOverview();
});