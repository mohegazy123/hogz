<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                <button type="button" class="btn btn-sm btn-outline-secondary">Print</button>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
                <i class="fas fa-calendar me-1"></i>
                This Week
            </button>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row">
        <!-- Today's Sales Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Today's Sales</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">$<?= number_format($todaySales, 2) ?></div>
                            <div class="text-xs text-muted mt-1"><?= $todayOrdersCount ?> orders today</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Sales Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Monthly Sales</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">$<?= number_format($monthlySales, 2) ?></div>
                            <div class="text-xs text-muted mt-1"><?= $monthlyOrdersCount ?> orders this month</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customers Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Customers</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $customerCount ?></div>
                            <div class="text-xs text-muted mt-1">Total registered customers</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Alert Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Low Stock Alerts</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $lowStockCount ?></div>
                            <div class="text-xs text-muted mt-1">Products below reorder level</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Recent Orders -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Orders</h6>
                    <a href="/orders" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentOrders)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No orders found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <tr>
                                            <td>
                                                <a href="/orders/view/<?= $order['id'] ?>"><?= $order['order_number'] ?></a>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                                            <td>
                                                <?php 
                                                    $customer = (new \App\Models\Customer())->findById($order['customer_id']);
                                                    echo $customer ? htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) : 'Unknown';
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    $statusClass = '';
                                                    switch ($order['status']) {
                                                        case 'pending':
                                                            $statusClass = 'warning';
                                                            break;
                                                        case 'processing':
                                                            $statusClass = 'info';
                                                            break;
                                                        case 'completed':
                                                            $statusClass = 'success';
                                                            break;
                                                        case 'cancelled':
                                                            $statusClass = 'danger';
                                                            break;
                                                        default:
                                                            $statusClass = 'secondary';
                                                    }
                                                ?>
                                                <span class="badge bg-<?= $statusClass ?>"><?= ucfirst($order['status']) ?></span>
                                            </td>
                                            <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Products -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-warning">Low Stock Products</h6>
                    <a href="/products" class="btn btn-sm btn-warning">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($lowStockProducts)): ?>
                        <div class="text-center py-3">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p>All products have sufficient stock.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Stock</th>
                                        <th>Reorder At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lowStockProducts as $product): ?>
                                        <tr>
                                            <td>
                                                <a href="/products/view/<?= $product['id'] ?>"><?= $product['name'] ?></a>
                                            </td>
                                            <td class="text-danger"><?= $product['quantity'] ?></td>
                                            <td><?= $product['reorder_level'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>