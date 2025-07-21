<?php

namespace App\Controllers;

use App\Models\Product;
use App\Models\Department;

class ProductController extends BaseController {
    private $productModel;
    
    public function __construct() {
        $this->productModel = new Product();
    }
    
    public function index() {
        $this->requireAuth();
        
        $queryData = $this->getQueryData();
        $page = isset($queryData['page']) ? (int)$queryData['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Process filters
        $filters = [];
        if (!empty($queryData['category_id'])) {
            $filters['category_id'] = (int)$queryData['category_id'];
        }
        
        if (!empty($queryData['supplier_id'])) {
            $filters['supplier_id'] = (int)$queryData['supplier_id'];
        }
        
        if (!empty($queryData['status'])) {
            $filters['status'] = $queryData['status'];
        }
        
        if (!empty($queryData['search'])) {
            $filters['search'] = $queryData['search'];
        }
        
        // Get products with filters
        $products = $this->productModel->getProducts($filters, $limit, $offset);
        $totalProducts = $this->productModel->count();
        $totalPages = ceil($totalProducts / $limit);
        
        $this->render('product/index', [
            'title' => 'Products',
            'products' => $products,
            'filters' => $filters,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }
    
    public function create() {
        $this->requireAuth();
        
        $errors = [];
        $product = [
            'name' => '',
            'sku' => '',
            'description' => '',
            'category_id' => '',
            'supplier_id' => '',
            'cost_price' => '',
            'selling_price' => '',
            'quantity' => '',
            'reorder_level' => '',
            'tax_rate' => '',
            'status' => 'active'
        ];
        
        if ($this->isPost()) {
            $postData = $this->getPostData();
            
            // Validate required fields
            $requiredFields = ['name', 'sku', 'category_id', 'cost_price', 'selling_price', 'quantity'];
            $errors = $this->validateRequired($postData, $requiredFields);
            
            // Validate numeric fields
            $numericFields = ['cost_price', 'selling_price', 'quantity', 'reorder_level', 'tax_rate'];
            foreach ($numericFields as $field) {
                if (!empty($postData[$field]) && !is_numeric($postData[$field])) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be a number';
                }
            }
            
            if (empty($errors)) {
                // Prepare product data
                $productData = [
                    'name' => $postData['name'],
                    'sku' => $postData['sku'],
                    'description' => $postData['description'] ?? '',
                    'category_id' => (int)$postData['category_id'],
                    'supplier_id' => !empty($postData['supplier_id']) ? (int)$postData['supplier_id'] : null,
                    'cost_price' => (float)$postData['cost_price'],
                    'selling_price' => (float)$postData['selling_price'],
                    'quantity' => (int)$postData['quantity'],
                    'reorder_level' => !empty($postData['reorder_level']) ? (int)$postData['reorder_level'] : 0,
                    'tax_rate' => !empty($postData['tax_rate']) ? (float)$postData['tax_rate'] : 0,
                    'status' => $postData['status'] ?? 'active'
                ];
                
                // Create product
                $productId = $this->productModel->create($productData);
                
                if ($productId) {
                    $this->setFlash('success', 'Product created successfully!');
                    $this->redirect('/products');
                } else {
                    $errors['create'] = 'Failed to create product. Please try again.';
                }
            }
            
            // Repopulate form data
            $product = $postData;
        }
        
        // Get categories and suppliers for select dropdown
        $categories = (new \App\Models\Department())->getAll(null, 0, 'name ASC');
        $suppliers = (new \App\Models\User())->getAll(null, 0, 'company ASC');
        
        $this->render('product/create', [
            'title' => 'Create Product',
            'product' => $product,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'errors' => $errors
        ]);
    }
    
    public function edit($id) {
        $this->requireAuth();
        
        $product = $this->productModel->findById($id);
        
        if (!$product) {
            $this->setFlash('error', 'Product not found');
            $this->redirect('/products');
        }
        
        $errors = [];
        
        if ($this->isPost()) {
            $postData = $this->getPostData();
            
            // Validate required fields
            $requiredFields = ['name', 'sku', 'category_id', 'cost_price', 'selling_price', 'quantity'];
            $errors = $this->validateRequired($postData, $requiredFields);
            
            // Validate numeric fields
            $numericFields = ['cost_price', 'selling_price', 'quantity', 'reorder_level', 'tax_rate'];
            foreach ($numericFields as $field) {
                if (!empty($postData[$field]) && !is_numeric($postData[$field])) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be a number';
                }
            }
            
            if (empty($errors)) {
                // Prepare product data
                $productData = [
                    'name' => $postData['name'],
                    'sku' => $postData['sku'],
                    'description' => $postData['description'] ?? '',
                    'category_id' => (int)$postData['category_id'],
                    'supplier_id' => !empty($postData['supplier_id']) ? (int)$postData['supplier_id'] : null,
                    'cost_price' => (float)$postData['cost_price'],
                    'selling_price' => (float)$postData['selling_price'],
                    'quantity' => (int)$postData['quantity'],
                    'reorder_level' => !empty($postData['reorder_level']) ? (int)$postData['reorder_level'] : 0,
                    'tax_rate' => !empty($postData['tax_rate']) ? (float)$postData['tax_rate'] : 0,
                    'status' => $postData['status'] ?? 'active'
                ];
                
                // Update product
                $success = $this->productModel->update($id, $productData);
                
                if ($success) {
                    $this->setFlash('success', 'Product updated successfully!');
                    $this->redirect('/products');
                } else {
                    $errors['update'] = 'Failed to update product. Please try again.';
                }
            }
            
            // Repopulate form data
            $product = $postData;
        }
        
        // Get categories and suppliers for select dropdown
        $categories = (new \App\Models\Department())->getAll(null, 0, 'name ASC');
        $suppliers = (new \App\Models\User())->getAll(null, 0, 'company ASC');
        
        $this->render('product/edit', [
            'title' => 'Edit Product',
            'product' => $product,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'errors' => $errors
        ]);
    }
    
    public function view($id) {
        $this->requireAuth();
        
        $product = $this->productModel->findById($id);
        
        if (!$product) {
            $this->setFlash('error', 'Product not found');
            $this->redirect('/products');
        }
        
        // Get related data
        $category = (new \App\Models\Department())->findById($product['category_id']);
        $supplier = null;
        if (!empty($product['supplier_id'])) {
            $supplier = (new \App\Models\User())->findById($product['supplier_id']);
        }
        
        $this->render('product/view', [
            'title' => 'View Product',
            'product' => $product,
            'category' => $category,
            'supplier' => $supplier
        ]);
    }
    
    public function delete($id) {
        $this->requireAuth();
        
        if ($this->isPost()) {
            $product = $this->productModel->findById($id);
            
            if (!$product) {
                $this->setFlash('error', 'Product not found');
                $this->redirect('/products');
            }
            
            $success = $this->productModel->delete($id);
            
            if ($success) {
                $this->setFlash('success', 'Product deleted successfully!');
            } else {
                $this->setFlash('error', 'Failed to delete product');
            }
        }
        
        $this->redirect('/products');
    }
}