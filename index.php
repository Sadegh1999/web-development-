<?php
// Include configuration file
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get current user
$user = getCurrentUser();

// Get database connection with detailed error logging
try {
    $db = getDBConnection();
    error_log("Database connection successful in index.php");
} catch (Exception $e) {
    error_log("Database connection error in index.php: " . $e->getMessage());
    $dbError = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Messina - Watch and Stream Movies Online</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #e50914;
            --secondary-color: #141414;
            --text-color: #ffffff;
            --text-muted: #8c8c8c;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--secondary-color);
            color: var(--text-color);
        }
        
        .navbar {
            background-color: rgba(0, 0, 0, 0.9);
            padding: 1rem 0;
        }
        
        .navbar-brand {
            color: var(--primary-color) !important;
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .nav-link {
            color: var(--text-color) !important;
            margin: 0 0.5rem;
            transition: color 0.3s ease;
        }
        
        .nav-link:hover {
            color: var(--primary-color) !important;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #c4070f;
            border-color: #c4070f;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('assets/images/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            min-height: 75vh;
            display: flex;
            align-items: center;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
        }

        /* Movies Available Section Styles */
        .movies-available {
            background-color: #f8f9fa;
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

        .section-header p {
            color: #666;
            font-size: 1.2rem;
            margin-bottom: 30px;
        }

        .filter-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
        }

        .filter-buttons .btn {
            padding: 8px 20px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .filter-buttons .btn.active {
            background-color: #007bff;
            color: white;
        }

        .movie-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .movie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
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

        .new-badge, .popular-badge {
            position: absolute;
            top: 10px;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            z-index: 2;
        }

        .new-badge {
            right: 10px;
            background: #ff4d4d;
            color: white;
        }

        .popular-badge {
            right: 80px;
            background: #28a745;
            color: white;
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

        .movie-rating {
            background: rgba(255,255,255,0.2);
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
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
            color: #333;
        }

        .movie-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: #666;
        }

        .movie-meta i {
            margin-right: 4px;
        }

        .movie-price {
            margin-top: auto;
            font-size: 1.2rem;
            font-weight: bold;
            color: #007bff;
        }

        @media (max-width: 768px) {
            .filter-buttons {
                flex-wrap: wrap;
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
        }

        /* Footer Styles */
        footer {
            background-color: #1a1a1a;
            color: #fff;
            padding: 3rem 0;
        }

        footer h5 {
            color: #e50914;
            margin-bottom: 1.5rem;
        }

        footer .text-muted {
            color: #8c8c8c !important;
        }

        footer a {
            color: #8c8c8c;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        footer a:hover {
            color: #fff;
        }

        .social-icons a {
            font-size: 1.2rem;
            margin-right: 1rem;
        }

        footer hr {
            border-color: #333;
            margin: 2rem 0;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Movie Messina</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="movies.php">Movies</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">Categories</a>
                    </li>
                </ul>
                <form class="d-flex me-3" action="search.php" method="GET">
                    <input class="form-control me-2" type="search" name="q" placeholder="Search movies..." aria-label="Search">
                    <button class="btn btn-outline-light" type="submit"><i class="fas fa-search"></i></button>
                </form>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php if (!empty($user['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" class="user-avatar">
                                <?php else: ?>
                                    <i class="fas fa-user-circle"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                                <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                                <?php if (isAdmin()): ?>
                                    <li><a class="dropdown-item" href="admin/dashboard.php">Admin Dashboard</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <!-- Hero Section -->
        <section class="hero-section position-relative">
            <div class="hero-overlay"></div>
            <div class="container position-relative">
                <div class="row min-vh-75 align-items-center">
                    <div class="col-lg-8">
                        <h1 class="display-4 text-white mb-4">Welcome to Movie Messina</h1>
                        <p class="lead text-white mb-5">Welcome to Movie Messina, a platform created by Sadegh to provide a seamless experience for buying and selling movies. Browse through our extensive collection and find your next favorite film. We hope you enjoy your time here!</p>
                        <div class="hero-buttons">
                            <a href="watchlist.php" class="btn btn-primary btn-lg me-3">
                                <i class="fas fa-bookmark"></i> Watchlist
                            </a>
                            <a href="orders.php" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-shopping-bag"></i> My Orders
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php if (isset($dbError) && $dbError): ?>
        <!-- Database Error Message -->
        <section class="py-5">
            <div class="container">
                <div class="alert alert-danger">
                    <h4 class="alert-heading">Database Connection Error</h4>
                    <p>A database error occurred. Please check the following:</p>
                    <ol>
                        <li>Make sure your MySQL server is running</li>
                        <li>Verify that the database '<?php echo DB_NAME; ?>' exists</li>
                        <li>Check that the database user has the correct permissions</li>
                        <li>Ensure the database configuration in config.php is correct</li>
                    </ol>
                </div>
            </div>
        </section>
        <?php else: ?>

        <!-- Movies Available Section -->
        <section class="movies-available py-5">
            <div class="container">
                <div class="section-header text-center mb-5">
                    <h2 class="section-title display-4 fw-bold">Movies Available</h2>
                    <p class="lead text-muted">Discover our latest and most popular movies</p>
                    <div class="filter-buttons mt-4">
                        <button class="btn btn-outline-primary active" data-filter="all">All Movies</button>
                        <button class="btn btn-outline-primary" data-filter="latest">Latest</button>
                        <button class="btn btn-outline-primary" data-filter="popular">Popular</button>
                    </div>
                </div>
                <div class="row g-4">
                    <?php
                    try {
                        // Get both latest and popular movies
                        $latestMovies = $db->query("SELECT * FROM movies ORDER BY created_at DESC LIMIT 8");
                        $popularMovies = $db->query("SELECT * FROM movies ORDER BY rating DESC LIMIT 8");
                        
                        $allMovies = [];
                        
                        // Combine and deduplicate movies
                        while ($movie = $latestMovies->fetch(PDO::FETCH_ASSOC)) {
                            $movie['type'] = 'latest';
                            $allMovies[$movie['id']] = $movie;
                        }
                        
                        while ($movie = $popularMovies->fetch(PDO::FETCH_ASSOC)) {
                            if (!isset($allMovies[$movie['id']])) {
                                $movie['type'] = 'popular';
                                $allMovies[$movie['id']] = $movie;
                            }
                        }
                        
                        if (!empty($allMovies)) {
                            foreach ($allMovies as $movie) {
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
                                
                                $isNew = (strtotime($movie['created_at']) > strtotime('-7 days'));
                                $isPopular = $movie['rating'] >= 4.0;
                                
                                echo '<div class="col-lg-3 col-md-4 col-sm-6" data-type="' . $movie['type'] . '">
                                    <div class="movie-card">
                                        <div class="movie-poster">
                                            <img src="' . htmlspecialchars($poster) . '" alt="' . htmlspecialchars($movie['title']) . '">
                                            ' . ($isNew ? '<span class="new-badge">New</span>' : '') . '
                                            ' . ($isPopular ? '<span class="popular-badge">Popular</span>' : '') . '
                                            <div class="movie-overlay">
                                                <div class="movie-actions">
                                                    <a href="movie.php?id=' . $movie['id'] . '" class="btn btn-primary btn-sm">View Details</a>
                                                    <div class="movie-rating">
                                                        <i class="fas fa-star"></i>
                                                        <span>' . number_format($movie['rating'], 1) . '</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="movie-info">
                                            <h3 class="movie-title">' . htmlspecialchars($movie['title']) . '</h3>
                                            <div class="movie-meta">
                                                <span class="year"><i class="fas fa-calendar"></i> ' . $movie['release_year'] . '</span>
                                                <span class="duration"><i class="fas fa-clock"></i> ' . $movie['duration'] . ' min</span>
                                            </div>
                                            <div class="movie-price">$' . number_format($movie['price'], 2) . '</div>
                                        </div>
                                    </div>
                                </div>';
                            }
                        } else {
                            echo '<div class="col-12"><div class="alert alert-info">No movies available at the moment.</div></div>';
                        }
                    } catch (PDOException $e) {
                        error_log("Database error in movies section: " . $e->getMessage());
                        echo '<div class="col-12"><div class="alert alert-danger">Error loading movies. Please try again later.</div></div>';
                    }
                    ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="text-primary mb-3"><?php echo SITE_NAME; ?></h5>
                    <p class="text-muted">Your ultimate destination for streaming the latest and greatest movies. Watch anytime, anywhere.</p>
                    <div class="social-icons mt-3">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="text-white mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php" class="text-muted text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="movies.php" class="text-muted text-decoration-none">Movies</a></li>
                        <li class="mb-2"><a href="categories.php" class="text-muted text-decoration-none">Categories</a></li>
                        <li class="mb-2"><a href="about.php" class="text-muted text-decoration-none">About Us</a></li>
                        <li class="mb-2"><a href="contact.php" class="text-muted text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="text-white mb-3">Categories</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="category.php?slug=action" class="text-muted text-decoration-none">Action</a></li>
                        <li class="mb-2"><a href="category.php?slug=comedy" class="text-muted text-decoration-none">Comedy</a></li>
                        <li class="mb-2"><a href="category.php?slug=drama" class="text-muted text-decoration-none">Drama</a></li>
                        <li class="mb-2"><a href="category.php?slug=horror" class="text-muted text-decoration-none">Horror</a></li>
                        <li class="mb-2"><a href="category.php?slug=sci-fi" class="text-muted text-decoration-none">Sci-Fi</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4 bg-secondary">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="privacy.php" class="text-muted text-decoration-none me-3">Privacy Policy</a>
                    <a href="terms.php" class="text-muted text-decoration-none">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/search.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Initialize popovers
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });

            // Movie filtering functionality
            const filterButtons = document.querySelectorAll('.filter-buttons .btn');
            const movieCards = document.querySelectorAll('.movie-card');

            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');

                    const filter = this.getAttribute('data-filter');

                    movieCards.forEach(card => {
                        const cardType = card.parentElement.getAttribute('data-type');
                        
                        if (filter === 'all' || filter === cardType) {
                            card.parentElement.style.display = '';
                        } else {
                            card.parentElement.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>