<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title . ' - ' : '' ?>ERP System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        body {
            padding-top: 56px;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            position: fixed;
            top: 56px;
            bottom: 0;
            left: 0;
            z-index: 1000;
            padding: 20px 0;
            width: 250px;
            overflow-x: hidden;
            overflow-y: auto;
            background-color: #343a40;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.75);
            font-weight: 500;
            padding: 10px 20px;
            border-left: 3px solid transparent;
        }
        
        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #007bff;
        }
        
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #007bff;
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            font-weight: 500;
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .badge {
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard">ERP System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user me-1"></i> 
                                <?= $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="/profile"><i class="fas fa-user-circle me-2"></i> Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/logout"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/register">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar and Content -->
    <div class="container-fluid">
        <div class="row">
            <?php if (isset($_SESSION['user_id'])): ?>
                <nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar">
                    <div class="position-sticky">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false ? 'active' : '' ?>" href="/dashboard">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/customers') !== false ? 'active' : '' ?>" href="/customers">
                                    <i class="fas fa-users"></i> Customers
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/products') !== false ? 'active' : '' ?>" href="/products">
                                    <i class="fas fa-box"></i> Products
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/orders') !== false ? 'active' : '' ?>" href="/orders">
                                    <i class="fas fa-shopping-cart"></i> Orders
                                </a>
                            </li>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/reports') !== false ? 'active' : '' ?>" href="/reports">
                                    <i class="fas fa-chart-bar"></i> Reports
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/users') !== false ? 'active' : '' ?>" href="/users">
                                    <i class="fas fa-user-shield"></i> Users
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/settings') !== false ? 'active' : '' ?>" href="/settings">
                                    <i class="fas fa-cog"></i> Settings
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </nav>

                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                    <?php if (isset($_SESSION['flash']['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                            <?= $_SESSION['flash']['success'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['flash']['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                            <?= $_SESSION['flash']['error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['flash']['info'])): ?>
                        <div class="alert alert-info alert-dismissible fade show mt-3" role="alert">
                            <?= $_SESSION['flash']['info'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
            <?php else: ?>
                <main class="col-md-12 ms-sm-auto px-md-4 main-content">
            <?php endif; ?>