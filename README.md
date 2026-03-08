# Bank Management System (Web Application)

A complete online banking system built with PHP and MySQL, featuring separate admin and customer portals with comprehensive account management, fund transfers, and transaction tracking.

## 📋 Features

### Customer Portal
- ✅ Secure Login & Registration
- ✅ Multi-Account Management
- ✅ View Account Balances
- ✅ Fund Transfers (Within Bank)
- ✅ Transaction History
- ✅ Account Statements
- ✅ Profile Management
- ✅ Password Management
- ✅ Session Timeout (15 minutes auto-logout)

### Admin Panel
- ✅ Customer Registration Approval/Rejection
- ✅ Account Management
- ✅ Process Deposits & Withdrawals
- ✅ Monitor All Transactions
- ✅ Generate Reports
- ✅ View Activity Logs
- ✅ Customer Management
- ✅ Account Status Control
- ✅ Transaction History Filters

## 🛠 Tech Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework**: Bootstrap 5.3
- **Security**: Password Hashing (bcrypt), Prepared Statements, CSRF Protection

## 📦 Installation

### Prerequisites
- Apache Web Server (XAMPP/WAMP recommended)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern Web Browser

### Steps

1. **Place Project in Web Root**
   ```bash
   Copy the project folder to: C:\xampp\htdocs\project
   ```

2. **Create Database**
   - Open phpMyAdmin
   - Create a new database named `bank_management_system`
   - Import the SQL schema:
     ```sql
     -- Open: db/schema.sql
     -- Execute all queries
     ```

3. **Configure Database Connection**
   - Edit `includes/config.php`
   - Verify database credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASSWORD', '');
     define('DB_NAME', 'bank_management_system');
     ```

4. **Start Apache and MySQL**
   - Start XAMPP/WAMP services
   - Open browser and navigate to: `http://localhost/project`

## 🚀 Quick Start

### Demo Admin Login
```
Username: admin
Password: admin@123
```

### Create a Test Customer Account
1. Click "Register" on home page
2. Fill in customer details
3. Go to Admin Panel → Registrations
4. Approve the registration request
5. Set a password for the new account
6. Customer can now login

## 📁 Project Structure

```
project/
├── index.php                  # Home page
├── login.php                  # Login page
├── register.php               # Registration page
├── logout.php                 # Logout handler
├── forgot_password.php        # Password reset (optional)
│
├── admin/                     # Admin Panel
│   ├── header.php
│   ├── footer.php
│   ├── dashboard.php          # Admin dashboard
│   ├── registrations.php      # Approve/reject registrations
│   ├── customers.php          # Manage customers
│   ├── accounts.php           # Manage accounts
│   ├── transactions.php       # Process transactions
│   ├── reports.php            # Generate reports
│   ├── activity_logs.php      # View activity logs
│   ├── get_customer_details.php
│   └── get_account_details.php
│
├── customer/                  # Customer Portal
│   ├── header.php
│   ├── footer.php
│   ├── dashboard.php          # Customer dashboard
│   ├── accounts.php           # View accounts
│   ├── transfer.php           # Fund transfer
│   ├── transactions.php       # View transactions
│   ├── profile.php            # Edit profile
│   └── get_account_statement.php
│
├── includes/                  # Core Files
│   ├── init.php              # Application initializer
│   ├── config.php            # Configuration
│   ├── Database.php          # Database class
│   └── functions.php         # Helper functions
│
├── db/
│   └── schema.sql            # Database schema
│
├── assets/
│   ├── css/
│   │   ├── admin-style.css
│   │   └── customer-style.css
│   └── js/
│       ├── admin-script.js
│       └── customer-script.js
│
└── logs/                      # Application logs
```

## 🔐 Security Features

1. **Password Security**
   - Bcrypt hashing (PASSWORD_BCRYPT)
   - Minimum 8 characters, letters + numbers required
   - Show/hide password toggle

2. **Database Security**
   - Prepared statements to prevent SQL injection
   - Input sanitization and validation
   - Secure password storage

3. **Session Management**
   - Automatic session timeout (15 minutes)
   - Session-based authentication
   - Role-based access control

4. **Authorization**
   - Backend role verification
   - URL-level access control
   - Function-level permission checks

5. **Audit Trail**
   - Activity logging for all transactions
   - User action tracking
   - IP address logging

## 💾 Database Schema

### Main Tables

**users**
- user_id (PK)
- username (UNIQUE)
- password_hash
- role (ADMIN / CUSTOMER)
- status (ACTIVE / INACTIVE)
- last_login
- created_at, updated_at

**customers**
- customer_id (PK)
- user_id (FK)
- customer_code (UNIQUE)
- full_name, dob, email, phone
- address, city, state, zip_code
- aadhar_number, pan_number
- registration_status
- approved_by, approved_at

**accounts**
- account_id (PK)
- account_number (UNIQUE)
- customer_id (FK)
- account_type (SAVINGS / CURRENT)
- balance
- status (ACTIVE / INACTIVE / CLOSED)
- opened_at

**transactions**
- transaction_id (PK)
- account_id (FK)
- transaction_type (DEPOSIT / WITHDRAW / TRANSFER)
- amount
- related_account_id (for transfers)
- balance_before, balance_after
- remark
- transaction_date
- processed_by

**registration_requests**
- request_id (PK)
- Customer information
- request_status (PENDING / APPROVED / REJECTED)
- rejection_reason
- reviewed_by, reviewed_at

**activity_logs**
- log_id (PK)
- user_id (FK)
- action
- description
- ip_address, user_agent
- created_at

## 🔄 User Workflows

### Customer Registration & Account Creation

1. **Customer Registers**
   - Fills registration form
   - Account status: PENDING APPROVAL
   
2. **Admin Approves**
   - Views pending registrations
   - Clicks "Approve"
   - Sets initial password
   - System creates user, customer, and account records
   
3. **Customer Login**
   - Uses credentials set by admin
   - Can change password after login
   - Accesses customer dashboard

### Fund Transfer Process

1. **Customer Initiates Transfer**
   - Selects "From" account
   - Enters recipient account number
   - Enters amount
   - Optional: adds remark

2. **System Validates**
   - Checks sender account is active
   - Checks recipient account exists and is active
   - Verifies sufficient balance
   
3. **Transfer Executes**
   - Deducts from sender account
   - Credits receiver account
   - Creates transaction records for both
   - Transaction is atomic (all-or-nothing)

## 📊 Admin Operations

### Process Deposits
1. Go to Transactions → Process Deposit/Withdraw tab
2. Select customer account
3. Enter amount and remark
4. System updates balance and creates transaction

### Generate Reports
- Date-range based reports
- Transaction summaries (deposits, withdrawals, transfers)
- Customer statistics
- Account balance reports
- Top customers by balance

### Monitor Activity
- View all user logins/logouts
- Track transaction processing
- Monitor registration approvals
- View IP addresses and user agents

## 🎨 User Interface

### Admin Dashboard
- Summary statistics cards
- Recent transactions list
- Quick action buttons
- Responsive navigation

### Customer Dashboard
- Account summary
- Quick actions (transfer, view accounts)
- Recent transactions
- Welcome message

### Responsive Design
- Mobile-friendly Bootstrap layout
- Sidebar navigation collapses on mobile
- Touch-friendly button sizes
- Optimized for tablets and phones

## 🧪 Testing

### Test Scenarios

**Admin Login**
- Username: `admin`
- Password: `admin@123`

**Create Test Customer**
1. Register as new customer
2. Approve from admin panel
3. Login with new credentials
4. Create multiple accounts
5. Test fund transfers
6. View transaction history

**Test Transactions**
- Create test accounts with initial balance
- Test deposits
- Test withdrawals
- Test transfers between accounts
- Verify balance updates

## 🐛 Troubleshooting

### Database Connection Issues
```
Error: Database Connection Failed
Solution: Check config.php database credentials
         Ensure MySQL is running
         Create database: bank_management_system
```

### Registration Approval Issues
```
Issue: Can't approve registration
Solution: Check admin is logged in with ADMIN role
         Verify registration request status is PENDING
         Ensure password meets requirements
```

### Fund Transfer Errors
```
Issue: Transfer fails with "Account not found"
Solution: Verify recipient account number is correct
         Check account is ACTIVE status
         Verify sufficient balance
```

### Session Timeout
```
Default: 15 minutes (900 seconds)
Edit: includes/config.php
      Change SESSION_TIMEOUT constant
```

## 📝 Key Functions (in includes/functions.php)

- `hashPassword()` - Bcrypt password hashing
- `verifyPassword()` - Password verification
- `validatePassword()` - Check password strength
- `generateAccountNumber()` - Create unique account numbers
- `sanitizeInput()` - Prevent XSS attacks
- `isAuthenticated()` - Check if user is logged in
- `requireAuth()` - Enforce authentication
- `requireAdmin()` - Enforce admin access
- `formatCurrency()` - Display amounts in INR
- `getStatusBadge()` - HTML status badges
- `getTransactionBadge()` - HTML transaction type badges

## 🚨 Important Notes

1. **Atomic Transactions**
   - All fund transfers use database transactions
   - Ensures consistency (debit and credit both succeed or both fail)

2. **Password Security**
   - Passwords are hashed with bcrypt
   - Plain text passwords are NEVER stored or displayed

3. **Session Management**
   - Sessions expire after 15 minutes of inactivity
   - Users are auto-logged out
   - Must login again to continue

4. **Account Status**
   - Inactive/closed accounts cannot perform transactions
   - Only admins can activate/deactivate accounts

5. **Audit Trail**
   - All actions are logged in activity_logs
   - Useful for security and compliance

## 📞 Support

For issues or questions:
1. Check the Troubleshooting section
2. Review error logs in logs/ directory
3. Verify database schema with db/schema.sql
4. Check browser console for JavaScript errors

## 📄 License

This is an educational project for BCA/BCS students.

## ✨ Future Enhancements

- [ ] Email OTP verification
- [ ] SMS notifications for transactions
- [ ] Interest calculations
- [ ] Recurring transactions
- [ ] Mobile app
- [ ] Advanced reporting and analytics
- [ ] Two-factor authentication
- [ ] Bill payment feature
- [ ] Loan management
- [ ] API integration

---

**Built with ❤️ for secure online banking**
