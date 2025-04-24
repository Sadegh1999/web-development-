<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if movie_id is provided
if (!isset($_POST['movie_id']) || !is_numeric($_POST['movie_id'])) {
    header('Location: watchlist.php');
    exit;
}

$movie_id = (int)$_POST['movie_id'];   
$user_id = $_SESSION['user_id'];

try {
    $db = getDBConnection();
    
    // Remove movie from watchlist
    $stmt = $db->prepare("DELETE FROM watchlist WHERE user_id = ? AND movie_id = ?");
    $stmt->execute([$user_id, $movie_id]);
    
    // Redirect back to watchlist with success message
    $_SESSION['success_message'] = 'Movie removed from your watchlist.';
    header('Location: watchlist.php');
    exit;
    
} catch (PDOException $e) {
    error_log("Error removing from watchlist: " . $e->getMessage());
    $_SESSION['error_message'] = 'Error removing movie from watchlist. Please try again.';
    header('Location: watchlist.php');
    exit;
} 