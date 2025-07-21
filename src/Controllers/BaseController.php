<?php

namespace App\Controllers;

class BaseController {
    protected function render($view, $data = []) {
        // Extract data array to variables
        extract($data);
        
        // Include header
        include_once __DIR__ . '/../Views/layouts/header.php';
        
        // Include the view
        include_once __DIR__ . '/../Views/' . $view . '.php';
        
        // Include footer
        include_once __DIR__ . '/../Views/layouts/footer.php';
    }
    
    protected function renderPartial($view, $data = []) {
        // Extract data array to variables
        extract($data);
        
        // Include only the view file
        include_once __DIR__ . '/../Views/' . $view . '.php';
    }
    
    protected function redirect($path) {
        header('Location: ' . $path);
        exit;
    }
    
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function getPostData() {
        return $_POST ?? [];
    }
    
    protected function getQueryData() {
        return $_GET ?? [];
    }
    
    protected function getRequestMethod() {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }
    
    protected function isPost() {
        return $this->getRequestMethod() === 'POST';
    }
    
    protected function isGet() {
        return $this->getRequestMethod() === 'GET';
    }
    
    protected function getSession($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    protected function setSession($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    protected function unsetSession($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
            return true;
        }
        return false;
    }
    
    protected function hasSession($key) {
        return isset($_SESSION[$key]);
    }
    
    protected function setFlash($key, $message) {
        $_SESSION['flash'][$key] = $message;
    }
    
    protected function getFlash($key, $default = null) {
        $message = $_SESSION['flash'][$key] ?? $default;
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    
    protected function hasFlash($key) {
        return isset($_SESSION['flash'][$key]);
    }
    
    protected function validateRequired($data, $fields) {
        $errors = [];
        
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        return $errors;
    }
    
    protected function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    protected function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }
    
    protected function requireAuth() {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
        }
    }
    
    protected function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
}