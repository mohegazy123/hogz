<?php

namespace App\Controllers;

use App\Models\User;

class AuthController extends BaseController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    public function login() {
        // If already logged in, redirect to dashboard
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }
        
        $errors = [];
        
        if ($this->isPost()) {
            $postData = $this->getPostData();
            
            // Validate required fields
            $requiredFields = ['username', 'password'];
            $errors = $this->validateRequired($postData, $requiredFields);
            
            if (empty($errors)) {
                $username = $postData['username'];
                $password = $postData['password'];
                
                // Attempt to authenticate
                $user = $this->userModel->authenticate($username, $password);
                
                if ($user) {
                    // Set session data
                    $this->setSession('user_id', $user['id']);
                    $this->setSession('username', $user['username']);
                    $this->setSession('role', $user['role']);
                    $this->setSession('first_name', $user['first_name']);
                    $this->setSession('last_name', $user['last_name']);
                    
                    // Redirect to dashboard
                    $this->redirect('/dashboard');
                } else {
                    $errors['auth'] = 'Invalid username or password';
                }
            }
        }
        
        $this->render('auth/login', [
            'title' => 'Login',
            'errors' => $errors
        ]);
    }
    
    public function logout() {
        // Clear all session data
        session_destroy();
        
        // Redirect to login page
        $this->redirect('/login');
    }
    
    public function register() {
        // If already logged in, redirect to dashboard
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }
        
        $errors = [];
        
        if ($this->isPost()) {
            $postData = $this->getPostData();
            
            // Validate required fields
            $requiredFields = ['username', 'email', 'password', 'confirm_password', 'first_name', 'last_name'];
            $errors = $this->validateRequired($postData, $requiredFields);
            
            // Validate email
            if (!empty($postData['email']) && !$this->validateEmail($postData['email'])) {
                $errors['email'] = 'Please enter a valid email address';
            }
            
            // Check if email already exists
            if (!empty($postData['email']) && $this->userModel->findByEmail($postData['email'])) {
                $errors['email'] = 'This email is already registered';
            }
            
            // Check if username already exists
            if (!empty($postData['username']) && $this->userModel->findByUsername($postData['username'])) {
                $errors['username'] = 'This username is already taken';
            }
            
            // Check if passwords match
            if (!empty($postData['password']) && !empty($postData['confirm_password']) && 
                $postData['password'] !== $postData['confirm_password']) {
                $errors['confirm_password'] = 'Passwords do not match';
            }
            
            if (empty($errors)) {
                // Prepare user data
                $userData = [
                    'username' => $postData['username'],
                    'email' => $postData['email'],
                    'password' => $postData['password'],
                    'first_name' => $postData['first_name'],
                    'last_name' => $postData['last_name'],
                    'role' => 'user', // Default role
                    'status' => 'active'
                ];
                
                // Register user
                $userId = $this->userModel->register($userData);
                
                if ($userId) {
                    $this->setFlash('success', 'Registration successful! You can now login.');
                    $this->redirect('/login');
                } else {
                    $errors['register'] = 'Registration failed. Please try again.';
                }
            }
        }
        
        $this->render('auth/register', [
            'title' => 'Register',
            'errors' => $errors
        ]);
    }
    
    public function forgotPassword() {
        $errors = [];
        $success = false;
        
        if ($this->isPost()) {
            $postData = $this->getPostData();
            
            // Validate email
            if (empty($postData['email'])) {
                $errors['email'] = 'Please enter your email address';
            } elseif (!$this->validateEmail($postData['email'])) {
                $errors['email'] = 'Please enter a valid email address';
            } else {
                $user = $this->userModel->findByEmail($postData['email']);
                
                if (!$user) {
                    $errors['email'] = 'No account found with this email address';
                } else {
                    // In a real application, you would generate a token, save it to the database,
                    // and send a password reset email with a link containing the token
                    // For demo purposes, we'll just show a success message
                    $success = true;
                }
            }
        }
        
        $this->render('auth/forgot-password', [
            'title' => 'Forgot Password',
            'errors' => $errors,
            'success' => $success
        ]);
    }
}