<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=checkout.php');
    exit;
}

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if a specific movie was requested for direct purchase
if (isset($_GET['movie_id']) && is_numeric($_GET['movie_id'])) {
    $movie_id = (int)$_GET['movie_id'];
    
    // Clear the cart and add only this movie
    $_SESSION['cart'] = [$movie_id];
    
    // Redirect to remove the query parameter
    header('Location: checkout.php');
    exit;
}

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    header('Location: movies.php');
    exit;
}

// Get user information
$userId = $_SESSION['user_id'];
try {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // If user not found, redirect to login
        session_destroy();
        header('Location: login.php?redirect=checkout.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching user data: " . $e->getMessage());
    $user = [];
}

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store order details in session for confirmation page
    $totalAmount = 0;
    
    // Get movie details for each item in cart
    $cart_items = [];
    foreach ($_SESSION['cart'] as $movie_id) {
        try {
            $stmt = $db->prepare("SELECT id, title, price FROM movies WHERE id = ?");
            $stmt->execute([$movie_id]);
            $movie = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($movie) {
                $cart_items[] = $movie;
                $totalAmount += $movie['price'];
            }
        } catch (PDOException $e) {
            error_log("Error fetching movie details: " . $e->getMessage());
        }
    }
    
    // Create order in database
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Create order
        $stmt = $db->prepare("INSERT INTO orders (user_id, total_amount, status, created_at) VALUES (?, ?, 'completed', NOW())");
        $stmt->execute([$_SESSION['user_id'], $totalAmount]);
        $order_id = $db->lastInsertId();
        
        // Add order items
        foreach ($cart_items as $movie) {
            $stmt = $db->prepare("INSERT INTO order_items (order_id, movie_id, price, quantity) VALUES (?, ?, ?, 1)");
            $stmt->execute([$order_id, $movie['id'], $movie['price']]);
        }
        
        // Commit transaction
        $db->commit();
        
        // Store order details in session for confirmation page
        $_SESSION['last_order'] = [
            'id' => $order_id,
            'total' => $totalAmount,
            'items' => count($cart_items),
            'date' => date('Y-m-d H:i:s')
        ];
        
        // Clear cart
        $_SESSION['cart'] = [];
        
        // Redirect to confirmation page
        header('Location: order-confirmation.php?id=' . $order_id);
        exit();
    } catch (PDOException $e) {
        // Rollback transaction on error
        $db->rollBack();
        error_log("Error creating order: " . $e->getMessage());
        $_SESSION['error_message'] = "An error occurred while processing your order. Please try again.";
    }
}

// Calculate cart totals
$subtotal = 0;
$tax = 0;
$shipping = 0;
$total = 0;

// Get movie details for each item in cart
$cart_items = [];
foreach ($_SESSION['cart'] as $movie_id) {
    try {
        $stmt = $db->prepare("SELECT id, title, price, poster_url, thumbnail FROM movies WHERE id = ?");
        $stmt->execute([$movie_id]);
        $movie = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($movie) {
            $cart_items[] = $movie;
            $subtotal += $movie['price'];
        }
    } catch (PDOException $e) {
        error_log("Error fetching movie details: " . $e->getMessage());
    }
}

$tax = $subtotal * 0.07; // 7% tax
$total = $subtotal + $tax + $shipping;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - StreamFlix</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
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
        
        /* Checkout styles */
        .checkout-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }
        
        .checkout-main {
            background-color: #1a1a1a;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .checkout-title {
            font-size: 24px;
            margin-bottom: 30px;
            font-weight: 600;
            color: #fff;
            border-bottom: 1px solid #333;
            padding-bottom: 15px;
        }
        
        .checkout-steps {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 1px solid #333;
            padding-bottom: 20px;
        }
        
        .checkout-step {
            display: flex;
            align-items: center;
            margin-right: 30px;
            opacity: 0.5;
        }
        
        .checkout-step.active {
            opacity: 1;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #333;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }
        
        .checkout-step.active .step-number {
            background-color: #e50914;
        }
        
        .step-label {
            font-weight: 500;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section-title {
            font-size: 18px;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .form-section-title i {
            margin-right: 10px;
            color: #e50914;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #ccc;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            background-color: #2a2a2a;
            border: 1px solid #333;
            border-radius: 5px;
            color: #fff;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-control:focus {
            border-color: #e50914;
            outline: none;
            box-shadow: 0 0 0 2px rgba(229, 9, 20, 0.2);
        }
        
        .form-control::placeholder {
            color: #666;
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .payment-method {
            background-color: #2a2a2a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s, transform 0.2s;
        }
        
        .payment-method:hover {
            transform: translateY(-3px);
        }
        
        .payment-method.selected {
            border-color: #e50914;
            background-color: rgba(229, 9, 20, 0.1);
        }
        
        .payment-method i {
            font-size: 24px;
            margin-bottom: 10px;
            color: #ccc;
        }
        
        .payment-method.selected i {
            color: #e50914;
        }
        
        .payment-method-name {
            font-weight: 500;
        }
        
        .card-details {
            margin-top: 20px;
            display: none;
        }
        
        .card-details.active {
            display: block;
        }
        
        .order-summary {
            background-color: #1a1a1a;
            border-radius: 10px;
            padding: 30px;
            position: sticky;
            top: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .order-summary-title {
            font-size: 20px;
            margin-bottom: 20px;
            font-weight: 600;
            color: #fff;
            border-bottom: 1px solid #333;
            padding-bottom: 15px;
        }
        
        .order-items {
            margin-bottom: 20px;
            max-height: 300px;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .order-item {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        }
        
        .order-item-image {
            width: 60px;
            height: 90px;
            border-radius: 5px;
            overflow: hidden;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .order-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .order-item-details {
            flex-grow: 1;
        }
        
        .order-item-title {
            font-weight: 500;
            margin-bottom: 5px;
            font-size: 16px;
        }
        
        .order-item-price {
            color: #ccc;
            font-size: 14px;
        }
        
        .order-item-quantity {
            color: #999;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .order-totals {
            margin-top: 20px;
        }
        
        .order-total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 16px;
            color: #ccc;
        }
        
        .order-total-row.final {
            font-size: 18px;
            font-weight: 600;
            color: #fff;
            border-top: 1px solid #333;
            padding-top: 15px;
            margin-top: 15px;
        }
        
        .checkout-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: #e50914;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            margin-top: 20px;
            text-align: center;
        }
        
        .checkout-btn:hover {
            background-color: #f40612;
            transform: translateY(-2px);
        }
        
        .checkout-btn:active {
            transform: translateY(0);
        }
        
        .secure-checkout {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 15px;
            color: #999;
            font-size: 14px;
        }
        
        .secure-checkout i {
            margin-right: 8px;
            color: #4CAF50;
        }
        
        @media (max-width: 992px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
            
            .order-summary {
                position: static;
                margin-bottom: 40px;
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
            }
            
            .navbar-menu {
                display: none;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .payment-methods {
                grid-template-columns: 1fr;
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
            <a href="categories.php">Movies</a>
            <a href="about.php">About</a>
        </div>
        
        <div class="navbar-right">
            <a href="cart.php" class="cart-icon">
                <i class="fas fa-shopping-cart"></i>
                <?php if(isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                <span class="cart-count"><?php echo count($_SESSION['cart']); ?></span>
                <?php endif; ?>
            </a>
            
            <div class="user-menu">
                <img src="https://sl.bing.net/zEV8c85eWy" alt="User" class="user-avatar">
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="checkout-container">
        <div class="checkout-main">
            <h1 class="checkout-title">Checkout</h1>
            
            <div class="checkout-steps">
                <div class="checkout-step">
                    <div class="step-number">1</div>
                    <div class="step-label">Cart</div>
                </div>
                <div class="checkout-step active">
                    <div class="step-number">2</div>
                    <div class="step-label">Checkout</div>
                </div>
                <div class="checkout-step">
                    <div class="step-number">3</div>
                    <div class="step-label">Confirmation</div>
                </div>
            </div>
            
            <form method="POST" action="checkout.php" id="checkout-form">
                <div class="form-section">
                    <h2 class="form-section-title">
                        <i class="fas fa-credit-card"></i> Select Payment Method
                    </h2>
                    <div class="payment-methods">
                        <div class="payment-method selected" data-method="credit_card">
                            <i class="fas fa-credit-card"></i>
                            <div class="payment-method-name">Credit Card</div>
                        </div>
                        <div class="payment-method" data-method="paypal">
                            <i class="fab fa-paypal"></i>
                            <div class="payment-method-name">PayPal</div>
                        </div>
                        <div class="payment-method" data-method="apple_pay">
                            <i class="fab fa-apple-pay"></i>
                            <div class="payment-method-name">Apple Pay</div>
                        </div>
                    </div>
                    
                    <!-- Simplified form - only card details for the selected payment method -->
                    <div class="card-details active" id="credit_card_details">
                        <div class="form-group">
                            <label for="card_number">Card Number</label>
                            <input type="text" id="card_number" name="card_number" class="form-control" placeholder="1234 5678 9012 3456" value="4111 1111 1111 1111">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="expiry_date">Expiry Date</label>
                                <input type="text" id="expiry_date" name="expiry_date" class="form-control" placeholder="MM/YY" value="12/25">
                            </div>
                            <div class="form-group">
                                <label for="cvv">CVV</label>
                                <input type="text" id="cvv" name="cvv" class="form-control" placeholder="123" value="123">
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="checkout-btn">Complete Purchase</button>
                
                <div class="secure-checkout">
                    <i class="fas fa-lock"></i> Secure Checkout - Your information is protected
                </div>
            </form>
        </div>
        
        <div class="order-summary">
            <h2 class="order-summary-title">Order Summary</h2>
            
            <div class="order-items">
                <?php foreach($cart_items as $item): ?>
                <div class="order-item">
                    <div class="order-item-image">
                        <?php 
                        // Get movie poster URL
                        $posterUrl = !empty($item['poster_url']) ? 
                            htmlspecialchars($item['poster_url']) : 
                            (!empty($item['thumbnail']) ? 
                                htmlspecialchars($item['thumbnail']) : 
                                'https://m.media-amazon.com/images/M/MV5BMDFkYTc0MGEtZmNhMC00ZDIzLWFmNTEtODM1ZmRlYWMwMWFmXkEyXkFqcGdeQXVyMTMxODk2OTU@._V1_.jpg');
                        ?>
                        <img src="<?php echo $posterUrl; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                    </div>
                    <div class="order-item-details">
                        <div class="order-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                        <div class="order-item-price">$<?php echo number_format($item['price'], 2); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="order-totals">
                <div class="order-total-row">
                    <span>Subtotal</span>
                    <span>$<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="order-total-row">
                    <span>Tax (7%)</span>
                    <span>$<?php echo number_format($tax, 2); ?></span>
                </div>
                <div class="order-total-row">
                    <span>Shipping</span>
                    <span>$<?php echo number_format($shipping, 2); ?></span>
                </div>
                <div class="order-total-row final">
                    <span>Total</span>
                    <span>$<?php echo number_format($total, 2); ?></span>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        // Payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                // Remove selected class from all methods
                document.querySelectorAll('.payment-method').forEach(m => {
                    m.classList.remove('selected');
                });
                
                // Add selected class to clicked method
                this.classList.add('selected');
                
                // Hide all payment details
                document.querySelectorAll('.card-details').forEach(details => {
                    details.classList.remove('active');
                });
                
                // Show selected payment details
                const methodType = this.getAttribute('data-method');
                const detailsElement = document.getElementById(methodType + '_details');
                if (detailsElement) {
                    detailsElement.classList.add('active');
                }
            });
        });
        
        // Form submission - ensure it works without validation
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            // Allow form submission without validation
            return true;
        });
    </script>
</body>
</html> 
