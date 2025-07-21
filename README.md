# PHP & MySQL ERP System

A comprehensive Enterprise Resource Planning (ERP) system built with PHP and MySQL. This system includes modules for customer management, product inventory, order processing, and reporting.

## Features

- User Authentication & Authorization
- Dashboard with Key Performance Indicators
- Customer Management
- Product & Inventory Management
- Order Processing
- Basic Reporting
- Responsive Design with Bootstrap 5

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer

## Installation

1. Clone the repository:
   ```
   git clone <repository-url>
   cd erp-system
   ```

2. Install dependencies:
   ```
   composer install
   ```

3. Create a MySQL database:
   ```
   mysql -u root -p
   CREATE DATABASE erp_system;
   exit;
   ```

4. Import the database schema:
   ```
   mysql -u root -p erp_system < database.sql
   ```

5. Copy the .env.example file to .env and update with your database credentials:
   ```
   cp .env.example .env
   ```

6. Update the .env file with your database credentials:
   ```
   DB_HOST=localhost
   DB_USER=your_username
   DB_PASSWORD=your_password
   DB_NAME=erp_system
   ```

7. Start the PHP development server:
   ```
   php -S localhost:8000 -t public
   ```

8. Access the application at `http://localhost:8000`

## Default Credentials

- Username: admin
- Password: admin123

## License

This project is open-sourced software licensed under the MIT license.