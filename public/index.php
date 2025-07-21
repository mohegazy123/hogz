<?php

// Load autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Initialize app configuration
\App\Config\App::init();

// Define routes
$router = new \App\Utils\Router();

// Auth routes
$router->get('/login', 'AuthController@login');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');
$router->get('/register', 'AuthController@register');
$router->post('/register', 'AuthController@register');
$router->get('/forgot-password', 'AuthController@forgotPassword');
$router->post('/forgot-password', 'AuthController@forgotPassword');

// Dashboard routes
$router->get('/', 'DashboardController@index');
$router->get('/dashboard', 'DashboardController@index');

// Customer routes
$router->get('/customers', 'CustomerController@index');
$router->get('/customers/create', 'CustomerController@create');
$router->post('/customers/create', 'CustomerController@create');
$router->get('/customers/edit/:id', 'CustomerController@edit');
$router->post('/customers/edit/:id', 'CustomerController@edit');
$router->get('/customers/view/:id', 'CustomerController@view');
$router->post('/customers/delete/:id', 'CustomerController@delete');

// Product routes
$router->get('/products', 'ProductController@index');
$router->get('/products/create', 'ProductController@create');
$router->post('/products/create', 'ProductController@create');
$router->get('/products/edit/:id', 'ProductController@edit');
$router->post('/products/edit/:id', 'ProductController@edit');
$router->get('/products/view/:id', 'ProductController@view');
$router->post('/products/delete/:id', 'ProductController@delete');

// Order routes
$router->get('/orders', 'OrderController@index');
$router->get('/orders/create', 'OrderController@create');
$router->post('/orders/create', 'OrderController@create');
$router->get('/orders/view/:id', 'OrderController@view');
$router->post('/orders/update/:id', 'OrderController@update');
$router->post('/orders/delete/:id', 'OrderController@delete');
$router->get('/orders/invoice/:id', 'OrderController@invoice');

// Handle 404 errors
$router->notFound(function() {
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/../src/Views/errors/404.php';
});

// Run the router
$router->run();