<?php

namespace App\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Department;

class DashboardController extends BaseController {
    private $orderModel;
    private $productModel;
    private $customerModel;
    private $departmentModel;
    
    public function __construct() {
        $this->orderModel = new Order();
        $this->productModel = new Product();
        $this->customerModel = new Customer();
        $this->departmentModel = new Department();
    }
    
    public function index() {
        // Require authentication for dashboard
        $this->requireAuth();
        
        // Get stats for dashboard
        $today = date('Y-m-d');
        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-t');
        
        // Today's orders
        $todayOrders = $this->orderModel->getOrdersByDateRange($today . ' 00:00:00', $today . ' 23:59:59');
        $todayOrdersCount = count($todayOrders);
        $todaySales = array_sum(array_column($todayOrders, 'total_amount'));
        
        // Monthly orders
        $monthlyOrders = $this->orderModel->getOrdersByDateRange($startOfMonth . ' 00:00:00', $endOfMonth . ' 23:59:59');
        $monthlyOrdersCount = count($monthlyOrders);
        $monthlySales = array_sum(array_column($monthlyOrders, 'total_amount'));
        
        // Recent orders (last 5)
        $recentOrders = array_slice($this->orderModel->getAll(5, 0, 'order_date DESC'), 0, 5);
        
        // Low stock products
        $lowStockProducts = $this->productModel->getLowStockProducts();
        $lowStockCount = count($lowStockProducts);
        
        // Customer count
        $customerCount = $this->customerModel->count();
        
        // Product count
        $productCount = $this->productModel->count();
        
        $this->render('dashboard/index', [
            'title' => 'Dashboard',
            'todayOrdersCount' => $todayOrdersCount,
            'todaySales' => $todaySales,
            'monthlyOrdersCount' => $monthlyOrdersCount,
            'monthlySales' => $monthlySales,
            'recentOrders' => $recentOrders,
            'lowStockProducts' => $lowStockProducts,
            'lowStockCount' => $lowStockCount,
            'customerCount' => $customerCount,
            'productCount' => $productCount
        ]);
    }
}