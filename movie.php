<?php
session_start();
require_once 'config.php';

// Get database connection
try {
    $db = getDBConnection();
    error_log("Database connection successful in movie.php");
} catch (Exception $e) {
    error_log("Database connection error in movie.php: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
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

// Get movie ID from URL
$movie_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$errors = [];
$movie = null;
$in_watchlist = false;

// Fetch movie details
try {
    $stmt = $db->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->execute([$movie_id]);
    $movie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$movie) {
        // Movie not found
        header("Location: index.php");
        exit;
    }
    
    // Fetch movie categories - with error handling for missing table
    $categories = [];
    try {
        $stmt = $db->prepare("
            SELECT c.* FROM categories c
            JOIN movie_categories mc ON c.id = mc.category_id
            WHERE mc.movie_id = ?
        ");
        $stmt->execute([$movie_id]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log the error but continue execution
        error_log("Error fetching movie categories: " . $e->getMessage());
        // Set empty categories array as fallback
        $categories = [];
    }
    
    // Fetch related movies (same categories) - with error handling
    $related_movies = [];
    if (!empty($categories)) {
        try {
            $category_ids = array_column($categories, 'id');
            $placeholders = str_repeat('?,', count($category_ids) - 1) . '?';
            
            $stmt = $db->prepare("
                SELECT DISTINCT m.* FROM movies m
                JOIN movie_categories mc ON m.id = mc.movie_id
                WHERE mc.category_id IN ($placeholders)
                AND m.id != ?
                LIMIT 4
            ");
            
            $params = array_merge($category_ids, [$movie_id]);
            $stmt->execute($params);
            $related_movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Log the error but continue execution
            error_log("Error fetching related movies: " . $e->getMessage());
            // Set empty related movies array as fallback
            $related_movies = [];
        }
    }
    
    // Check if movie is in user's watchlist
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $db->prepare("SELECT id FROM watchlist WHERE user_id = ? AND movie_id = ?");
            $stmt->execute([$_SESSION['user_id'], $movie_id]);
            $in_watchlist = $stmt->fetch() !== false;
        } catch (PDOException $e) {
            // Log the error but continue execution
            error_log("Error checking watchlist: " . $e->getMessage());
            // Set default value as fallback
            $in_watchlist = false;
        }
    }
} catch (PDOException $e) {
    error_log("Database error in movie.php: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movie['title']); ?> - StreamFlix</title>
    
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
        
        /* Movie styles */
        .movie-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .movie-header {
            display: flex;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .movie-poster {
            flex: 0 0 300px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .movie-poster img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .movie-info {
            flex: 1;
        }
        
        .movie-title {
            color: #fff;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .movie-meta {
            color: #999;
            margin-bottom: 20px;
        }
        
        .movie-meta span {
            margin-right: 20px;
        }
        
        .movie-genres {
            margin-bottom: 20px;
        }
        
        .genre-tag {
            display: inline-block;
            background-color: rgba(229, 9, 20, 0.1);
            color: #fff;
            padding: 6px 12px;
            border-radius: 20px;
            margin-right: 10px;
            margin-bottom: 10px;
            font-size: 14px;
            border: 1px solid rgba(229, 9, 20, 0.3);
        }
        
        .movie-description {
            color: #e5e5e5;
            line-height: 1.8;
            margin-bottom: 30px;
            font-size: 16px;
        }
        
        .movie-price {
            color: #e50914;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .movie-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background-color: #e50914;
            color: #fff;
        }
        
        .btn-primary:hover {
            background-color: #f40612;
            transform: translateY(-2px);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background-color: #333;
            color: #fff;
        }
        
        .btn-secondary:hover {
            background-color: #444;
            transform: translateY(-2px);
        }
        
        .btn i {
            font-size: 18px;
        }
        
        .error-message {
            color: #e50914;
            margin-bottom: 15px;
            padding: 15px;
            background-color: rgba(229, 9, 20, 0.1);
            border-radius: 5px;
            border: 1px solid rgba(229, 9, 20, 0.3);
        }
        
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #333;
            color: #fff;
            padding: 15px 25px;
            border-radius: 5px;
            display: none;
            z-index: 1000;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .toast.show {
            display: block;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 5px;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
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
            
            .movie-header {
                flex-direction: column;
            }
            
            .movie-poster {
                flex: 0 0 auto;
                max-width: 300px;
                margin: 0 auto 30px;
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
            }
            
            .navbar-menu {
                display: none;
            }
            
            .movie-actions {
                flex-direction: column;
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
            <a href="movies.php" class="active">Movies</a>
            <a href="categories.php">Categories</a>
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

    <!-- Movie Details -->
    <div class="movie-container">
        <?php if (!empty($errors)): ?>
        <div class="error-message">
            <?php foreach ($errors as $error): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            <?php unset($_SESSION['error_message']); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['info_message'])): ?>
        <div class="alert alert-info">
            <?php echo htmlspecialchars($_SESSION['info_message']); ?>
            <?php unset($_SESSION['info_message']); ?>
        </div>
        <?php endif; ?>
        
        <div class="movie-header">
            <div class="movie-poster">
                <img src="<?php echo htmlspecialchars($movie['poster_url'] ?? $movie['thumbnail'] ?? 'assets/images/placeholder.jpg'); ?>" 
                     alt="<?php echo htmlspecialchars($movie['title']); ?>">
            </div>
            
            <div class="movie-info">
                <h1 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h1>
                
                <div class="movie-meta">
                    <span><i class="fas fa-calendar"></i> <?php echo $movie['release_year'] ?? $movie['release_date']; ?></span>
                    <span><i class="fas fa-clock"></i> <?php echo $movie['duration']; ?> min</span>
                    <span><i class="fas fa-star"></i> <?php echo number_format($movie['rating'], 1); ?></span>
                </div>
                
                <div class="movie-genres">
                    <?php 
                    // Handle genres which might be in different formats
                    $genres = [];
                    if (isset($movie['genres']) && !empty($movie['genres'])) {
                        $genres = explode(',', $movie['genres']);
                    } elseif (!empty($categories)) {
                        // Use category names as genres if available
                        $genres = array_column($categories, 'name');
                    }
                    
                    foreach ($genres as $genre): 
                    ?>
                    <span class="genre-tag"><?php echo htmlspecialchars(trim($genre)); ?></span>
                    <?php endforeach; ?>
                </div>
                
                <p class="movie-description"><?php echo nl2br(htmlspecialchars($movie['description'])); ?></p>
                
                <div class="movie-price">$<?php echo number_format($movie['price'], 2); ?></div>
                
                <div class="movie-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="checkout.php?movie_id=<?php echo $movie['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-shopping-cart"></i> Rent Now
                    </a>
                    <?php else: ?>
                    <a href="login.php?redirect=movie.php?id=<?php echo $movie['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Login to Rent
                    </a>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <button class="btn btn-secondary watchlist-btn" data-movie-id="<?php echo $movie['id']; ?>">
                        <i class="fas <?php echo $in_watchlist ? 'fa-check' : 'fa-plus'; ?>"></i>
                        <span><?php echo $in_watchlist ? 'In Watchlist' : 'Add to Watchlist'; ?></span>
                    </button>
                    <?php else: ?>
                    <a href="login.php?redirect=movie.php?id=<?php echo $movie['id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i> Login to Add to Watchlist
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast"></div>

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
        // Watchlist functionality
        document.querySelectorAll('.watchlist-btn').forEach(button => {
            button.addEventListener('click', async () => {
                const movieId = button.dataset.movieId;
                const isInWatchlist = button.querySelector('i').classList.contains('fa-check');
                const method = isInWatchlist ? 'DELETE' : 'POST';
                
                try {
                    // Create a form to submit the request
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'includes/process/process-watchlist.php';
                    
                    // Add movie_id field
                    const movieIdInput = document.createElement('input');
                    movieIdInput.type = 'hidden';
                    movieIdInput.name = 'movie_id';
                    movieIdInput.value = movieId;
                    form.appendChild(movieIdInput);
                    
                    // Add action field
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = isInWatchlist ? 'remove' : 'add';
                    form.appendChild(actionInput);
                    
                    // Add CSRF token if available
                    if (typeof csrf_token !== 'undefined') {
                        const csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = 'csrf_token';
                        csrfInput.value = csrf_token;
                        form.appendChild(csrfInput);
                    }
                    
                    // Submit the form
                    document.body.appendChild(form);
                    form.submit();
                } catch (error) {
                    console.error('Watchlist error:', error);
                    alert('Error updating watchlist. Please try again.');
                }
            });
        });
        
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