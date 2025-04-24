<?php
// Include configuration file
require_once 'config.php';

// Get database connection
$db = getDBConnection();

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php?redirect=purchase');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $db->beginTransaction();

        // Validate and sanitize input
        $movie_id = filter_input(INPUT_POST, 'movie_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);

        // Validate required fields
        if (!$movie_id || !$quantity || !$name || !$email || !$payment_method) {
            throw new Exception('Please fill in all required fields.');
        }

        // Get movie details
        $movie = $db->query("SELECT price, title FROM movies WHERE id = " . $movie_id)->fetch(PDO::FETCH_ASSOC);
        if (!$movie) {
            throw new Exception('Invalid movie selected.');
        }

        // Calculate total price
        $total_price = $movie['price'] * $quantity;

        // Create order
        $stmt = $db->prepare("
            INSERT INTO orders (user_id, total_amount, status, payment_method, created_at) 
            VALUES (?, ?, 'pending', ?, NOW())
        ");
        $stmt->execute([$_SESSION['user_id'], $total_price, $payment_method]);
        $order_id = $db->lastInsertId();

        // Add order items
        $stmt = $db->prepare("
            INSERT INTO order_items (order_id, movie_id, quantity, price) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$order_id, $movie_id, $quantity, $movie['price']]);

        // Commit transaction
        $db->commit();

        // Redirect to order confirmation
        header('Location: order-confirmation.php?id=' . $order_id);
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        
        // Set error message and redirect back to purchase form
        $_SESSION['error'] = $e->getMessage();
        header('Location: index.php#purchase');
        exit();
    }
} else {
    // If not POST request, redirect to index
    header('Location: index.php');
    exit();
}
?> 