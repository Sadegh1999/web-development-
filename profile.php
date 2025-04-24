<?php
session_start();
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=profile.php');
    exit;
}

// Get user information
$userId = $_SESSION['user_id'];
$userData = null;
$orderHistory = [];
$error = null;

try {
    // Get database connection
    $db = getDBConnection();
    
    // Get user data
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        throw new Exception("User not found");
    }
    
    // Get order history
    $stmt = $db->prepare("
        SELECT o.*, COUNT(oi.id) as item_count 
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.user_id = ? 
        GROUP BY o.id 
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$userId]);
    $orderHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Profile error: " . $e->getMessage());
    $error = "An error occurred while loading your profile.";
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        // Get database connection if not already set
        if (!isset($db)) {
            $db = getDBConnection();
        }
        
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        
        // Validate inputs
        if (empty($name) || empty($email)) {
            throw new Exception("Name and email are required");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        // Update user data
        $stmt = $db->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, $email, $userId]);
        
        // Update session data
        $_SESSION['user_name'] = $name;
        
        // Refresh user data
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $success = "Profile updated successfully!";
        
    } catch (Exception $e) {
        error_log("Profile update error: " . $e->getMessage());
        $updateError = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - MovieFlix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #0a0a0a;
            color: #fff;
            font-family: 'Arial', sans-serif;
        }
        
        .profile-container {
            max-width: 1000px;
            margin: 50px auto;
            padding: 0 20px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 40px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 40px;
            color: #e50914;
            border: 3px solid #e50914;
        }
        
        .profile-title h1 {
            font-size: 32px;
            margin-bottom: 5px;
        }
        
        .profile-title p {
            color: #aaa;
            font-size: 16px;
        }
        
        .profile-tabs {
            display: flex;
            border-bottom: 1px solid #333;
            margin-bottom: 30px;
        }
        
        .profile-tab {
            padding: 15px 25px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            color: #aaa;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .profile-tab.active {
            color: #fff;
        }
        
        .profile-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: #e50914;
        }
        
        .profile-tab:hover {
            color: #fff;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .profile-card {
            background-color: rgba(30, 30, 30, 0.7);
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .profile-card h2 {
            font-size: 22px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #ddd;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid #444;
            border-radius: 5px;
            color: #fff;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #e50914;
            background-color: rgba(255, 255, 255, 0.15);
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: #e50914;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #b2070f;
        }
        
        .btn-secondary {
            background-color: #333;
        }
        
        .btn-secondary:hover {
            background-color: #555;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            border: 1px solid #28a745;
            color: #28a745;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            border: 1px solid #dc3545;
            color: #dc3545;
        }
        
        .order-card {
            background-color: rgba(40, 40, 40, 0.5);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: transform 0.3s ease;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #444;
        }
        
        .order-id {
            font-weight: bold;
            color: #e50914;
        }
        
        .order-date {
            color: #aaa;
        }
        
        .order-details {
            display: flex;
            justify-content: space-between;
        }
        
        .order-items {
            color: #ddd;
        }
        
        .order-total {
            font-weight: bold;
            font-size: 18px;
        }
        
        .order-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-top: 15px;
        }
        
        .status-completed {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }
        
        .status-processing {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        
        .no-orders {
            text-align: center;
            padding: 40px 0;
            color: #aaa;
        }
        
        .no-orders i {
            font-size: 50px;
            margin-bottom: 20px;
            color: #444;
        }
        
        /* Navbar styles */
        .navbar {
            background: linear-gradient(to right, rgba(0, 0, 0, 0.95), rgba(0, 0, 0, 0.8));
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
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
            align-items: center;
        }
        
        .navbar-menu a {
            color: #fff;
            text-decoration: none;
            margin-left: 20px;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .navbar-menu a:hover {
            color: #e50914;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-avatar {
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .profile-tabs {
                overflow-x: auto;
                white-space: nowrap;
            }
            
            .profile-tab {
                padding: 15px 15px;
            }
            
            .order-header, .order-details {
                flex-direction: column;
            }
            
            .order-date, .order-total {
                margin-top: 10px;
            }
        }
        
        .order-history {
            margin-top: 30px;
        }
        
        .order-card {
            background-color: #1a1a1a;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        }
        
        .order-info {
            display: flex;
            flex-direction: column;
        }
        
        .order-id {
            font-weight: bold;
            color: #fff;
        }
        
        .order-date {
            color: #888;
            font-size: 0.9em;
        }
        
        .order-status {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
        }
        
        .order-status.completed {
            background-color: #4CAF50;
            color: white;
        }
        
        .order-status.pending {
            background-color: #FFC107;
            color: black;
        }
        
        .order-status.cancelled {
            background-color: #f44336;
            color: white;
        }
        
        .order-items {
            margin-bottom: 15px;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #2a2a2a;
            border-radius: 4px;
        }
        
        .item-thumbnail {
            width: 60px;
            height: 90px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }
        
        .item-details {
            flex-grow: 1;
        }
        
        .item-details h4 {
            margin: 0 0 5px 0;
            color: #fff;
        }
        
        .item-meta {
            display: flex;
            justify-content: space-between;
            color: #888;
            font-size: 0.9em;
        }
        
        .order-footer {
            display: flex;
            justify-content: flex-end;
            padding-top: 15px;
            border-top: 1px solid #333;
        }
        
        .order-total {
            font-weight: bold;
            color: #fff;
        }
        
        .order-total .amount {
            color: #4CAF50;
            margin-left: 10px;
        }
        
        .no-orders {
            text-align: center;
            color: #888;
            padding: 20px;
            background-color: #1a1a1a;
            border-radius: 8px;
        }
        
        .error {
            color: #f44336;
            text-align: center;
            padding: 20px;
            background-color: #1a1a1a;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="navbar-brand">
                <img src="assets/images/logo.png" alt="MovieFlix Logo">
            </a>
            <div class="navbar-menu">
                <a href="index.php">Home</a>
                <a href="movies.php">Movies</a>
                <a href="cart.php">Cart</a>
                <a href="profile.php" class="active">Profile</a>
            </div>
        </div>
    </nav>
    
    <div class="profile-container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($userData['name'] ?? 'U', 0, 1)); ?>
            </div>
            <div class="profile-title">
                <h1><?php echo htmlspecialchars($userData['name'] ?? 'User'); ?></h1>
                <p><?php echo htmlspecialchars($userData['email'] ?? ''); ?></p>
                <p>Member since: <?php echo date('F Y', strtotime($userData['created_at'] ?? 'now')); ?></p>
            </div>
        </div>
        
        <div class="profile-tabs">
            <div class="profile-tab active" data-tab="account">Account Information</div>
            <div class="profile-tab" data-tab="orders">Order History</div>
            <div class="profile-tab" data-tab="settings">Account Settings</div>
        </div>
        
        <div class="tab-content active" id="account">
            <div class="profile-card">
                <h2>Personal Information</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <?php if (isset($updateError)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($updateError); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="profile.php">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($userData['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" required>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn">Update Profile</button>
                </form>
            </div>
        </div>
        
        <div class="tab-content" id="orders">
            <div class="profile-card">
                <h2>Your Orders</h2>
                
                <div class="order-history">
                    <h2>Order History</h2>
                    <?php
                    try {
                        // Get user's orders
                        $stmt = $db->prepare("
                            SELECT o.*, 
                                   COUNT(oi.id) as total_items,
                                   GROUP_CONCAT(m.title) as movie_titles
                            FROM orders o
                            LEFT JOIN order_items oi ON o.id = oi.order_id
                            LEFT JOIN movies m ON oi.movie_id = m.id
                            WHERE o.user_id = ?
                            GROUP BY o.id
                            ORDER BY o.created_at DESC
                        ");
                        $stmt->execute([$_SESSION['user_id']]);
                        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($orders) > 0) {
                            foreach ($orders as $order) {
                                // Get order items
                                $stmt = $db->prepare("
                                    SELECT oi.*, m.title, m.thumbnail
                                    FROM order_items oi
                                    JOIN movies m ON oi.movie_id = m.id
                                    WHERE oi.order_id = ?
                                ");
                                $stmt->execute([$order['id']]);
                                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <div class="order-card">
                                    <div class="order-header">
                                        <div class="order-info">
                                            <span class="order-id">Order #<?php echo $order['id']; ?></span>
                                            <span class="order-date"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></span>
                                        </div>
                                        <div class="order-status <?php echo strtolower($order['status']); ?>">
                                            <?php echo $order['status']; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="order-items">
                                        <?php foreach ($items as $item): ?>
                                        <div class="order-item">
                                            <img src="<?php echo htmlspecialchars($item['thumbnail']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['title']); ?>"
                                                 class="item-thumbnail">
                                            <div class="item-details">
                                                <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                                                <div class="item-meta">
                                                    <span class="quantity">Qty: <?php echo $item['quantity']; ?></span>
                                                    <span class="price">$<?php echo number_format($item['price'], 2); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="order-footer">
                                        <div class="order-total">
                                            <span>Total:</span>
                                            <span class="amount">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<p class="no-orders">You haven\'t placed any orders yet.</p>';
                        }
                    } catch (PDOException $e) {
                        error_log("Error fetching order history: " . $e->getMessage());
                        echo '<p class="error">Error loading order history. Please try again later.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="tab-content" id="settings">
            <div class="profile-card">
                <h2>Account Settings</h2>
                
                <div class="form-group">
                    <label for="password">Change Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="New password">
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">Confirm Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="Confirm new password">
                </div>
                
                <button type="button" class="btn">Update Password</button>
                
                <hr style="margin: 30px 0; border-color: #333;">
                
                <h3 style="margin-bottom: 20px; color: #dc3545;">Danger Zone</h3>
                
                <button type="button" class="btn btn-secondary" style="background-color: #dc3545;">Delete Account</button>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer style="background: #111; color: #fff; padding: 30px 0; margin-top: 50px; text-align: center;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <p>&copy; <?php echo date('Y'); ?> MovieFlix. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        // Tab switching functionality
        document.querySelectorAll('.profile-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                document.querySelectorAll('.profile-tab').forEach(t => {
                    t.classList.remove('active');
                });
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Hide all tab content
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                // Show selected tab content
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
    </script>
</body>
</html> 