<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get current user
$user = getCurrentUser();

// Get database connection
try {
    $db = getDBConnection();
    
    // Get movies from user's watchlist
    $stmt = $db->prepare("
        SELECT m.*, w.created_at as added_to_watchlist 
        FROM movies m
        JOIN watchlist w ON m.id = w.movie_id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $watchlistMovies = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Error fetching watchlist: " . $e->getMessage());
    $watchlistError = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Watchlist - StreamFlix</title>
    
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
        
        .watchlist-section {
            background-color: #111;
            padding: 60px 0;
        }

        .section-title {
            background: linear-gradient(45deg, #e50914, #ff0f1f);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .section-title:hover {
            transform: scale(1.02);
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.2);
        }

        .movie-card {
            background: #1a1a1a;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .movie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(229,9,20,0.3);
        }

        .movie-poster {
            position: relative;
            overflow: hidden;
            aspect-ratio: 2/3;
        }

        .movie-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .movie-card:hover .movie-poster img {
            transform: scale(1.05);
        }

        .new-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #e50914;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            z-index: 2;
        }

        .movie-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0.2));
            display: flex;
            align-items: flex-end;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .movie-card:hover .movie-overlay {
            opacity: 1;
        }

        .movie-actions {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .movie-info {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .movie-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #fff;
        }

        .movie-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: #999;
        }

        .movie-meta i {
            margin-right: 4px;
            color: #e50914;
        }

        .movie-price {
            margin-top: auto;
            font-size: 1.2rem;
            font-weight: bold;
            color: #e50914;
        }
        
        .lead {
            color: #aaa;
        }
        
        .btn-primary {
            background-color: #e50914;
            border-color: #e50914;
        }
        
        .btn-primary:hover {
            background-color: #f40612;
            border-color: #f40612;
        }
        
        .alert-danger {
            background-color: rgba(229, 9, 20, 0.1);
            border-color: rgba(229, 9, 20, 0.2);
            color: #e50914;
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
            
            .movie-card {
                margin-bottom: 20px;
            }
            
            .movie-title {
                font-size: 1rem;
            }
            
            .movie-meta {
                font-size: 0.8rem;
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
            <a href="watchlist.php" class="active">My Watchlist</a>
            <a href="orders.php">My Orders</a>
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
            <?php endif; ?>
        </div>
    </nav>

<main class="main-content">
    <!-- Watchlist Section -->
    <section class="watchlist-section py-5">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title display-4 fw-bold">My Watchlist</h2>
                <p class="lead">Your saved movies for later viewing</p>
            </div>
            
            <?php if (isset($watchlistError)): ?>
                <div class="alert alert-danger">
                    Error loading your watchlist. Please try again later.
                </div>
            <?php elseif (empty($watchlistMovies)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-bookmark fa-3x text-muted mb-3"></i>
                    <h3>Your watchlist is empty</h3>
                    <p class="text-muted">Start adding movies to your watchlist to save them for later.</p>
                    <a href="index.php" class="btn btn-primary mt-3">Browse Movies</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($watchlistMovies as $movie): ?>
                        <?php
                        $poster = 'assets/images/placeholder.jpg';
                        if (!empty($movie['poster_url'])) {
                            $poster = $movie['poster_url'];
                        } elseif (!empty($movie['thumbnail'])) {
                            $poster = $movie['thumbnail'];
                        } elseif (!empty($movie['image'])) {
                            $poster = $movie['image'];
                        } elseif (!empty($movie['poster'])) {
                            $poster = $movie['poster'];
                        }
                        
                        $isNew = (strtotime($movie['added_to_watchlist']) > strtotime('-7 days'));
                        ?>
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="movie-card">
                                <div class="movie-poster">
                                    <img src="<?php echo htmlspecialchars($poster); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                                    <?php if ($isNew): ?>
                                        <span class="new-badge">Recently Added</span>
                                    <?php endif; ?>
                                    <div class="movie-overlay">
                                        <div class="movie-actions">
                                            <a href="movie.php?id=<?php echo $movie['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                                            <form action="includes/process/process-watchlist.php" method="POST" class="d-inline">
                                                <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
                                                <input type="hidden" name="action" value="remove">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to remove this movie from your watchlist?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="movie-info">
                                    <h3 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                                    <div class="movie-meta">
                                        <span class="year"><i class="fas fa-calendar"></i> <?php echo $movie['release_year']; ?></span>
                                        <span class="duration"><i class="fas fa-clock"></i> <?php echo $movie['duration']; ?> min</span>
                                    </div>
                                    <div class="movie-price">$<?php echo number_format($movie['price'], 2); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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