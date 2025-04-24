<?php
// Include configuration file
require_once 'config.php';

// Get database connection
$db = getDBConnection();

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php?redirect=orders');
    exit();
}

// Get user information if logged in
$user = null;
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching user data: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Orders - StreamFlix</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #141414;
            color: #fff;
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        
        /* Header styles */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 60px;
            background: linear-gradient(to right, rgba(0, 0, 0, 0.95), rgba(0, 0, 0, 0.8));
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .navbar-brand img {
            height: 45px;
            transition: transform 0.3s ease;
        }
        
        .navbar-brand img:hover {
            transform: scale(1.05);
        }
        
        .navbar-menu {
            display: flex;
            gap: 30px;
        }
        
        .navbar-menu a {
            color: #e5e5e5;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            transition: color 0.3s;
            position: relative;
        }
        
        .navbar-menu a:hover, 
        .navbar-menu a.active {
            color: #fff;
        }
        
        .navbar-menu a:after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 0;
            background-color: #e50914;
            transition: width 0.3s;
        }
        
        .navbar-menu a:hover:after, 
        .navbar-menu a.active:after {
            width: 100%;
        }
        
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .cart-icon {
            position: relative;
            color: #fff;
            font-size: 18px;
            text-decoration: none;
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #e50914;
            color: white;
            font-size: 10px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .user-menu {
            position: relative;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            object-fit: cover;
        }
        
        /* Orders styles */
        .main-content {
            padding: 40px 0;
        }
        
        .orders-section .card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            background-color: #1a1a1a;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .orders-section .card-header {
            border-bottom: none;
            padding: 1.5rem;
            background-color: #e50914;
        }
        
        .orders-section .card-title {
            font-weight: 600;
        }

        .orders-section .table {
            margin-bottom: 0;
            color: #e5e5e5;
        }
        
        .orders-section .table th {
            border-color: #333;
            color: #fff;
            font-weight: 500;
        }
        
        .orders-section .table td {
            border-color: #333;
            vertical-align: middle;
        }

        .orders-section .btn {
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .orders-section .btn:hover {
            transform: translateY(-2px);
        }
        
        .badge {
            padding: 6px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        /* Footer styles */
        footer {
            background: linear-gradient(to bottom, #0a0a0a, #141414);
            padding: 60px 0 30px;
            margin-top: 60px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px;
        }
        
        .footer-section h4 {
            color: #fff;
            font-size: 18px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .footer-section p {
            color: #999;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li {
            margin-bottom: 12px;
        }
        
        .footer-links a {
            color: #999;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: #e50914;
        }
        
        .social-icons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .social-icons a {
            color: #fff;
            font-size: 18px;
            transition: color 0.3s, transform 0.3s;
        }
        
        .social-icons a:hover {
            color: #e50914;
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 40px;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer-bottom p {
            color: #777;
            font-size: 14px;
            margin: 0;
        }
        
        .footer-bottom-links {
            display: flex;
            gap: 20px;
        }
        
        .footer-bottom-links a {
            color: #777;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .footer-bottom-links a:hover {
            color: #e50914;
        }
        
        @media (max-width: 992px) {
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
            }
            
            .navbar-menu {
                display: none;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .footer-bottom {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .footer-bottom-links {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="index.php">
                <img src="assets/images/logo.png" alt="StreamFlix Logo">
            </a>
        </div>
        
        <div class="navbar-menu">
            <a href="index.php">Home</a>
            <a href="movies.php">Movies</a>
            <a href="categories.php">Categories</a>
            <a href="orders.php" class="active">My Orders</a>
            <a href="about.php">About</a>
        </div>
        
        <div class="navbar-right">
            <a href="cart.php" class="cart-icon">
                <i class="fas fa-shopping-cart"></i>
                <?php if(isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                <span class="cart-count"><?php echo count($_SESSION['cart']); ?></span>
                <?php endif; ?>
            </a>
            
            <?php if (isLoggedIn()): ?>
            <div class="user-menu">
                <?php if (!empty($user['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="User" class="user-avatar">
                <?php else: ?>
                    <img src="assets/images/default-avatar.png" alt="User" class="user-avatar">
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="navbar-menu">
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            </div>
            <?php endif; ?>
        </div>
    </nav>

<main class="main-content">
    <section class="orders-section py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h2 class="card-title mb-0">Your Orders</h2>
                        </div>
                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Movie</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        try {
                                            $orders = $db->query("
                                                SELECT o.id, o.created_at, o.status, oi.price, m.title 
                                                FROM orders o 
                                                JOIN order_items oi ON o.id = oi.order_id 
                                                JOIN movies m ON oi.movie_id = m.id 
                                                WHERE o.user_id = " . $_SESSION['user_id'] . " 
                                                ORDER BY o.created_at DESC
                                            ");
                                            
                                            if ($orders->rowCount() > 0) {
                                                while ($order = $orders->fetch(PDO::FETCH_ASSOC)) {
                                                    echo '<tr>
                                                        <td>#' . $order['id'] . '</td>
                                                        <td>' . htmlspecialchars($order['title']) . '</td>
                                                        <td>$' . formatPrice($order['price']) . '</td>
                                                        <td><span class="badge bg-' . getStatusColor($order['status']) . '">' . ucfirst($order['status']) . '</span></td>
                                                        <td>' . date('M d, Y', strtotime($order['created_at'])) . '</td>
                                                        <td><a href="order-details.php?id=' . $order['id'] . '" class="btn btn-sm btn-outline-primary">View</a></td>
                                                    </tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="6" class="text-center">You have no orders yet.</td></tr>';
                                            }
                                        } catch (PDOException $e) {
                                            echo '<tr><td colspan="6" class="text-center"><div class="alert alert-danger">Error loading orders. Please try again later.</div></td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="text-center mt-4">
                                <a href="index.php" class="btn btn-outline-primary">Back to Home</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Footer -->
<footer>
    <div class="footer-container">
        <div class="footer-grid">
            <div class="footer-section">
                <h4><?php echo SITE_NAME; ?></h4>
                <p>Your ultimate destination for streaming the latest and greatest movies. Watch anytime, anywhere.</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="movies.php">Movies</a></li>
                    <li><a href="categories.php">Categories</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Categories</h4>
                <ul class="footer-links">
                    <li><a href="category.php?slug=action">Action</a></li>
                    <li><a href="category.php?slug=comedy">Comedy</a></li>
                    <li><a href="category.php?slug=drama">Drama</a></li>
                    <li><a href="category.php?slug=horror">Horror</a></li>
                    <li><a href="category.php?slug=sci-fi">Sci-Fi</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Customer Support</h4>
                <ul class="footer-links">
                    <li><a href="faq.php">FAQ</a></li>
                    <li><a href="help.php">Help Center</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="terms.php">Terms of Service</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            <div class="footer-bottom-links">
                <a href="privacy.php">Privacy Policy</a>
                <a href="terms.php">Terms of Service</a>
                <a href="sitemap.php">Sitemap</a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Active menu item
    document.addEventListener('DOMContentLoaded', function() {
        const currentLocation = window.location.pathname;
        
        document.querySelectorAll('.navbar-menu a').forEach(link => {
            if (currentLocation.includes(link.getAttribute('href'))) {
                link.classList.add('active');
            }
        });
    });
</script>
</body>
</html> 