<?php
require_once 'includes/init.php';

// Redirect authenticated users
if (isAuthenticated()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('customer/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Professional Banking Solution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-lg">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-bank2"></i><?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features"><i class="bi bi-star"></i> Features</a>
                    </li>

                </ul>
                <div class="navbar-buttons">
                    <a href="login.php" class="btn-nav-login">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </a>
                    <a href="register.php" class="btn-nav-register">
                        <i class="bi bi-person-plus"></i> Register
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section class="hero-section">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Secure Digital Banking</h1>
                <p>Experience the future of banking with our robust, secure, and user-friendly platform designed for seamless financial management.</p>
                <div class="hero-buttons">
                    <a href="login.php" class="btn-primary-hero">
                        <i class="bi bi-box-arrow-in-right"></i> Login Now
                    </a>
                    <a href="register.php" class="btn-secondary-hero">
                        <i class="bi bi-person-plus"></i> Get Started
                    </a>
                </div>
            </div>
            <div class="hero-icon">
                <i class="bi bi-bank"></i>
            </div>
        </div>
    </section>

    <!-- FEATURES SECTION -->
    <section class="features-section" id="features">
        <div class="section-title">
            <h2>Powerful Features</h2>
            <p>Everything you need for secure online banking</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <h3>Bank-Grade Security</h3>
                <p>Advanced encryption, bcrypt password hashing, and role-based access control ensure your data is always protected.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
                <h3>Instant Transfers</h3>
                <p>Transfer funds between accounts instantly with real-time balance updates and atomic transaction processing.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-graph-up"></i>
                </div>
                <h3>Smart Dashboard</h3>
                <p>Get real-time insights with comprehensive dashboards showing balances, transactions, and financial analytics.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-person-check"></i>
                </div>
                <h3>Admin Approval</h3>
                <p>New customer registrations go through a secure approval process ensuring account authenticity.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <h3>Detailed Statements</h3>
                <p>Generate and download transaction statements with comprehensive filtering and date-range options.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-activity"></i>
                </div>
                <h3>Audit Trail</h3>
                <p>Complete activity logging with IP tracking for compliance and security monitoring.</p>
            </div>
        </div>
    </section>

    <!-- STATISTICS SECTION -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">50K+</div>
                    <div class="stat-label">Happy Customers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$2B+</div>
                    <div class="stat-label">Transactions Processed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Customer Support</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">99.9%</div>
                    <div class="stat-label">Uptime Guaranteed</div>
                </div>
            </div>
        </div>
    </section>



    <!-- FOOTER -->
    <footer>
        <div class="container">
            <h5><?php echo SITE_NAME; ?></h5>
            <p>Professional Online Banking Management System</p>
            <p>&copy; 2025 <?php echo SITE_NAME; ?>. All rights reserved.</p>
            <p style="margin-top: 20px; font-size: 12px; opacity: 0.7;">Built with PHP, MySQL, and Bootstrap | Secure, Reliable, Professional</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });

        // Copy to clipboard function
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                const btn = event.target.closest('.copy-btn');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check2"></i>';
                btn.style.background = '#28a745';
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style.background = '';
                }, 2000);
            });
        }

        // Add scroll animation for elements
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.feature-card, .stat-card, .demo-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>
