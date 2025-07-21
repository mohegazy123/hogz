<?php

namespace App\Controllers;

use App\Models\Customer;
use App\Models\Order;

class CustomerController extends BaseController {
    private $customerModel;
    private $orderModel;
    
    public function __construct() {
        $this->customerModel = new Customer();
        $this->orderModel = new Order();
    }
    
    public function index() {
        $this->requireAuth();
        
        $queryData = $this->getQueryData();
        $page = isset($queryData['page']) ? (int)$queryData['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Get search query
        $search = $queryData['search'] ?? null;
        
        // Get customers with search
        $customers = $this->customerModel->getCustomers($search, $limit, $offset);
        $totalCustomers = $this->customerModel->count();
        $totalPages = ceil($totalCustomers / $limit);
        
        $this->render('customer/index', [
            'title' => 'Customers',
            'customers' => $customers,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }
    
    public function create() {
        $this->requireAuth();
        
        $errors = [];
        $customer = [
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'phone' => '',
            'company' => '',
            'address' => '',
            'city' => '',
            'state' => '',
            'zip_code' => '',
            'country' => '',
            'status' => 'active',
            'notes' => ''
        ];
        
        if ($this->isPost()) {
            $postData = $this->getPostData();
            
            // Validate required fields
            $requiredFields = ['first_name', 'last_name', 'email'];
            $errors = $this->validateRequired($postData, $requiredFields);
            
            // Validate email
            if (!empty($postData['email']) && !$this->validateEmail($postData['email'])) {
                $errors['email'] = 'Please enter a valid email address';
            }
            
            // Check if email already exists
            if (!empty($postData['email']) && $this->customerModel->findByEmail($postData['email'])) {
                $errors['email'] = 'This email is already registered to another customer';
            }
            
            if (empty($errors)) {
                // Create customer
                $customerId = $this->customerModel->create($postData);
                
                if ($customerId) {
                    $this->setFlash('success', 'Customer created successfully!');
                    $this->redirect('/customers');
                } else {
                    $errors['create'] = 'Failed to create customer. Please try again.';
                }
            }
            
            // Repopulate form data
            $customer = $postData;
        }
        
        $this->render('customer/create', [
            'title' => 'Create Customer',
            'customer' => $customer,
            'errors' => $errors
        ]);
    }
    
    public function edit($id) {
        $this->requireAuth();
        
        $customer = $this->customerModel->findById($id);
        
        if (!$customer) {
            $this->setFlash('error', 'Customer not found');
            $this->redirect('/customers');
        }
        
        $errors = [];
        
        if ($this->isPost()) {
            $postData = $this->getPostData();
            
            // Validate required fields
            $requiredFields = ['first_name', 'last_name', 'email'];
            $errors = $this->validateRequired($postData, $requiredFields);
            
            // Validate email
            if (!empty($postData['email']) && !$this->validateEmail($postData['email'])) {
                $errors['email'] = 'Please enter a valid email address';
            }
            
            // Check if email already exists (but ignore current customer's email)
            if (!empty($postData['email'])) {
                $existingCustomer = $this->customerModel->findByEmail($postData['email']);
                if ($existingCustomer && $existingCustomer['id'] != $id) {
                    $errors['email'] = 'This email is already registered to another customer';
                }
            }
            
            if (empty($errors)) {
                // Update customer
                $success = $this->customerModel->update($id, $postData);
                
                if ($success) {
                    $this->setFlash('success', 'Customer updated successfully!');
                    $this->redirect('/customers');
                } else {
                    $errors['update'] = 'Failed to update customer. Please try again.';
                }
            }
            
            // Repopulate form data
            $customer = $postData;
        }
        
        $this->render('customer/edit', [
            'title' => 'Edit Customer',
            'customer' => $customer,
            'errors' => $errors
        ]);
    }
    
    public function view($id) {
        $this->requireAuth();
        
        $customer = $this->customerModel->findById($id);
        
        if (!$customer) {
            $this->setFlash('error', 'Customer not found');
            $this->redirect('/customers');
        }
        
        // Get customer orders
        $orders = $this->orderModel->getOrdersByCustomer($id, 5);
        $ordersCount = $this->customerModel->getOrdersCount($id);
        $totalSpent = $this->customerModel->getTotalSpent($id);
        
        $this->render('customer/view', [
            'title' => 'View Customer',
            'customer' => $customer,
            'orders' => $orders,
            'ordersCount' => $ordersCount,
            'totalSpent' => $totalSpent
        ]);
    }
    
    public function delete($id) {
        $this->requireAuth();
        
        if ($this->isPost()) {
            $customer = $this->customerModel->findById($id);
            
            if (!$customer) {
                $this->setFlash('error', 'Customer not found');
                $this->redirect('/customers');
            }
            
            // Check if customer has orders
            $ordersCount = $this->customerModel->getOrdersCount($id);
            
            if ($ordersCount > 0) {
                $this->setFlash('error', 'Cannot delete customer with existing orders');
                $this->redirect('/customers/view/' . $id);
                return;
            }
            
            $success = $this->customerModel->delete($id);
            
            if ($success) {
                $this->setFlash('success', 'Customer deleted successfully!');
                $this->redirect('/customers');
            } else {
                $this->setFlash('error', 'Failed to delete customer');
                $this->redirect('/customers/view/' . $id);
            }
        } else {
            $this->redirect('/customers');
        }
    }
}