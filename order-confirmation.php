<?php
// Include configuration file
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=order-confirmation.php');
    exit();
}

// Get database connection
$db = getDBConnection();

// Get order ID from URL or session
$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$order_id && isset($_SESSION['last_order']['id'])) {
    $order_id = $_SESSION['last_order']['id'];
}

if (!$order_id) {
    header('Location: index.php');
    exit();
}

// Get order details
try {
    // Check if orders table exists
    $stmt = $db->query("SHOW TABLES LIKE 'orders'");
    if ($stmt->rowCount() == 0) {
        // Create orders table
        $db->exec("CREATE TABLE orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            status VARCHAR(50) NOT NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )");
    }

    // Check if order_items table exists
    $stmt = $db->query("SHOW TABLES LIKE 'order_items'");
    if ($stmt->rowCount() == 0) {
        // Create order_items table
        $db->exec("CREATE TABLE order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            movie_id INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            FOREIGN KEY (order_id) REFERENCES orders(id),
            FOREIGN KEY (movie_id) REFERENCES movies(id)
        )");
    }

    // Get order details
    $stmt = $db->prepare("
        SELECT oi.*, o.total_amount, o.created_at, o.status, m.title, m.poster_url, m.thumbnail
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN movies m ON oi.movie_id = m.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($order_items)) {
        // If no order items found, check if it's a new order from session
        if (isset($_SESSION['last_order']) && $_SESSION['last_order']['id'] == $order_id) {
            $order = [
                'id' => $order_id,
                'total_amount' => $_SESSION['last_order']['total'],
                'created_at' => $_SESSION['last_order']['date'],
                'status' => 'completed'
            ];
            
            // Get movie details for the order
            $movie_ids = [];
            foreach ($_SESSION['cart'] as $movie_id) {
                $movie_ids[] = $movie_id;
            }
            
            if (!empty($movie_ids)) {
                $placeholders = str_repeat('?,', count($movie_ids) - 1) . '?';
                $stmt = $db->prepare("SELECT id, title, price, poster_url, thumbnail FROM movies WHERE id IN ($placeholders)");
                $stmt->execute($movie_ids);
                $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $order_items = [];
                foreach ($movies as $movie) {
                    $order_items[] = [
                        'movie_id' => $movie['id'],
                        'title' => $movie['title'],
                        'price' => $movie['price'],
                        'quantity' => 1,
                        'poster_url' => $movie['poster_url'],
                        'thumbnail' => $movie['thumbnail']
                    ];
                }
            }
        } else {
            // Order not found or doesn't belong to user
            header('Location: index.php');
            exit();
        }
    } else {
        // Get order details from first item
        $order = [
            'id' => $order_items[0]['id'],
            'total_amount' => $order_items[0]['total_amount'],
            'created_at' => $order_items[0]['created_at'],
            'status' => $order_items[0]['status']
        ];
    }
} catch (PDOException $e) {
    error_log("Error fetching order details: " . $e->getMessage());
    $_SESSION['error_message'] = "An error occurred while fetching your order details.";
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - StreamFlix</title>
    
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
        
        .confirmation-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background-color: #1a1a1a;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .confirmation-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .confirmation-icon {
            font-size: 60px;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        
        .confirmation-title {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .confirmation-subtitle {
            font-size: 18px;
            color: #ccc;
            margin-bottom: 30px;
        }
        
        .order-details {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #2a2a2a;
            border-radius: 8px;
        }
        
        .order-details-title {
            font-size: 20px;
            margin-bottom: 15px;
            border-bottom: 1px solid #333;
            padding-bottom: 10px;
        }
        
        .order-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .order-info-item {
            display: flex;
            flex-direction: column;
        }
        
        .order-info-label {
            font-size: 14px;
            color: #999;
            margin-bottom: 5px;
        }
        
        .order-info-value {
            font-size: 16px;
            font-weight: 500;
        }
        
        .order-items {
            margin-bottom: 20px;
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
        
        .order-total {
            text-align: right;
            font-size: 18px;
            font-weight: 600;
            margin-top: 20px;
        }
        
        .confirmation-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: #e50914;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #f40612;
        }
        
        .btn-secondary {
            background-color: #333;
        }
        
        .btn-secondary:hover {
            background-color: #444;
        }
        
        @media (max-width: 768px) {
            .confirmation-container {
                margin: 20px;
                padding: 20px;
            }
            
            .order-info {
                grid-template-columns: 1fr;
            }
            
            .confirmation-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'header.php'; ?>
    
    <!-- Main Content -->
    <main class="confirmation-container">
        <div class="confirmation-header">
            <div class="confirmation-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="confirmation-title">Order Confirmed!</h1>
            <p class="confirmation-subtitle">Thank you for your purchase. Your order has been successfully processed.</p>
        </div>
        
        <div class="order-details">
            <h2 class="order-details-title">Order Details</h2>
            
            <div class="order-info">
                <div class="order-info-item">
                    <span class="order-info-label">Order Number</span>
                    <span class="order-info-value">#<?php echo $order['id']; ?></span>
                </div>
                <div class="order-info-item">
                    <span class="order-info-label">Order Date</span>
                    <span class="order-info-value"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="order-info-item">
                    <span class="order-info-label">Status</span>
                    <span class="order-info-value"><?php echo ucfirst($order['status']); ?></span>
                </div>
                <div class="order-info-item">
                    <span class="order-info-label">Payment Method</span>
                    <span class="order-info-value">Credit Card</span>
                </div>
            </div>
            
            <h3 class="order-details-title">Items</h3>
            
            <div class="order-items">
                <?php foreach ($order_items as $item): ?>
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
            
            <div class="order-total">
                Total: $<?php echo number_format($order['total_amount'], 2); ?>
            </div>
        </div>
        
        <div class="confirmation-actions">
            <a href="index.php" class="btn">Continue Browsing</a>
            <a href="profile.php" class="btn btn-secondary">View My Orders</a>
        </div>
    </main>
    
    <!-- Footer -->
    <?php include 'footer.php'; ?>
</body>
</html> 