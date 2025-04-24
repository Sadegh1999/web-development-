<?php
// Include configuration file
require_once '../../config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to manage your watchlist.";
    header("Location: ../../login.php?redirect=" . urlencode($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// Check if CSRF token is valid (if CSRF protection is enabled)
if (function_exists('verify_csrf_token') && isset($_POST['csrf_token'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_message'] = "Invalid request. Please try again.";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../../index.php'));
        exit;
    }
}

// Check if movie_id is provided
if (!isset($_POST['movie_id']) || !is_numeric($_POST['movie_id'])) {
    $_SESSION['error_message'] = "Invalid movie ID.";
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../../index.php'));
    exit;
}

$movie_id = (int)$_POST['movie_id'];
$user_id = $_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : 'add';

// Get database connection
try {
    $db = getDBConnection();
    
    // Check if movie exists
    $stmt = $db->prepare("SELECT id FROM movies WHERE id = ?");
    $stmt->execute([$movie_id]);
    if (!$stmt->fetch()) {
        $_SESSION['error_message'] = "Movie not found.";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../../index.php'));
        exit;
    }
    
    // Check if watchlist table exists, if not create it
    try {
        $db->query("SELECT 1 FROM watchlist LIMIT 1");
    } catch (PDOException $e) {
        // Table doesn't exist, create it
        $db->exec("CREATE TABLE IF NOT EXISTS watchlist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            movie_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_watchlist (user_id, movie_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
        )");
    }
    
    if ($action === 'add') {
        // Add to watchlist
        try {
            $stmt = $db->prepare("INSERT INTO watchlist (user_id, movie_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $movie_id]);
            $_SESSION['success_message'] = "Movie added to your watchlist.";
        } catch (PDOException $e) {
            // Check if it's a duplicate entry error
            if ($e->getCode() == 23000) {
                $_SESSION['info_message'] = "Movie is already in your watchlist.";
            } else {
                error_log("Error adding to watchlist: " . $e->getMessage());
                $_SESSION['error_message'] = "Error adding movie to watchlist. Please try again.";
            }
        }
    } else {
        // Remove from watchlist
        $stmt = $db->prepare("DELETE FROM watchlist WHERE user_id = ? AND movie_id = ?");
        $stmt->execute([$user_id, $movie_id]);
        $_SESSION['success_message'] = "Movie removed from your watchlist.";
    }
    
} catch (PDOException $e) {
    error_log("Database error in process-watchlist.php: " . $e->getMessage());
    $_SESSION['error_message'] = "A database error occurred. Please try again later.";
}

// Redirect back to the previous page
header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../../index.php'));
exit; 