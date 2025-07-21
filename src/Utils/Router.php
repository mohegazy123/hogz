<?php

namespace App\Utils;

class Router {
    protected $routes = [];
    protected $notFoundCallback;
    protected $baseController;
    
    public function __construct() {
        $this->baseController = "\\App\\Controllers\\";
    }
    
    public function get($route, $callback) {
        $this->addRoute('GET', $route, $callback);
        return $this;
    }
    
    public function post($route, $callback) {
        $this->addRoute('POST', $route, $callback);
        return $this;
    }
    
    public function any($route, $callback) {
        $this->addRoute('GET|POST', $route, $callback);
        return $this;
    }
    
    protected function addRoute($method, $route, $callback) {
        // Convert route to regex pattern
        $pattern = $this->routeToRegex($route);
        
        $this->routes[$method][$pattern] = [
            'route' => $route,
            'callback' => $callback
        ];
    }
    
    protected function routeToRegex($route) {
        // Convert route parameters to regex pattern
        $pattern = preg_replace('/\/:([^\/]+)/', '/(?<$1>[^/]+)', $route);
        $pattern = str_replace('/', '\/', $pattern);
        return '/^' . $pattern . '$/';
    }
    
    public function notFound($callback) {
        $this->notFoundCallback = $callback;
        return $this;
    }
    
    public function run() {
        $uri = $this->getRequestUri();
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Check if route exists
        $routeFound = false;
        
        $allowedMethods = $method . '|GET|POST';
        $allowedMethodsArr = explode('|', $allowedMethods);
        
        foreach ($allowedMethodsArr as $allowedMethod) {
            if (!isset($this->routes[$allowedMethod])) {
                continue;
            }
            
            foreach ($this->routes[$allowedMethod] as $pattern => $route) {
                if (preg_match($pattern, $uri, $matches)) {
                    $routeFound = true;
                    
                    // Extract parameters
                    $params = array_filter($matches, function($key) {
                        return !is_numeric($key);
                    }, ARRAY_FILTER_USE_KEY);
                    
                    // Execute callback with parameters
                    $this->executeCallback($route['callback'], array_values($params));
                    break 2;
                }
            }
        }
        
        // If no route found
        if (!$routeFound) {
            if ($this->notFoundCallback) {
                call_user_func($this->notFoundCallback);
            } else {
                header('HTTP/1.0 404 Not Found');
                echo '404 Page Not Found';
            }
        }
    }
    
    protected function getRequestUri() {
        $uri = $_SERVER['REQUEST_URI'];
        
        // Remove query string
        if (strpos($uri, '?') !== false) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }
        
        // Remove trailing slash
        $uri = rtrim($uri, '/');
        
        // If empty uri, set to root
        if (empty($uri)) {
            $uri = '/';
        }
        
        return $uri;
    }
    
    protected function executeCallback($callback, $params = []) {
        if (is_callable($callback)) {
            call_user_func_array($callback, $params);
        } elseif (is_string($callback)) {
            // Parse controller@method format
            list($controller, $method) = explode('@', $callback);
            
            // Add namespace
            if (strpos($controller, '\\') === false) {
                $controller = $this->baseController . $controller;
            }
            
            // Create controller instance
            $controllerInstance = new $controller();
            
            // Call method with parameters
            call_user_func_array([$controllerInstance, $method], $params);
        }
    }
}