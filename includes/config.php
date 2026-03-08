<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'bank_management_system');
define('DB_PORT', 3306);

// Site Configuration
define('SITE_URL', 'http://localhost/project');
define('SITE_NAME', 'Bank Management System');

// Security Configuration
define('SESSION_TIMEOUT', 900); // 15 minutes in seconds
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);

// Role Constants
define('ROLE_ADMIN', 'ADMIN');
define('ROLE_CUSTOMER', 'CUSTOMER');

// Status Constants
define('STATUS_ACTIVE', 'ACTIVE');
define('STATUS_INACTIVE', 'INACTIVE');
define('STATUS_PENDING', 'PENDING');
define('STATUS_REJECTED', 'REJECTED');
define('STATUS_CLOSED', 'CLOSED');

// Account Types
define('ACCOUNT_TYPE_SAVINGS', 'SAVINGS');
define('ACCOUNT_TYPE_CURRENT', 'CURRENT');

// Transaction Types
define('TXN_DEPOSIT', 'DEPOSIT');
define('TXN_WITHDRAW', 'WITHDRAW');
define('TXN_TRANSFER', 'TRANSFER');
?>
