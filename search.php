<?php
// Include configuration file
require_once 'config.php';

// Get search query
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] === '1';

// Check if search query is empty
if (empty($searchQuery)) {
    if ($isAjax) {
        // Return empty results for AJAX request
        header('Content-Type: application/json');
        echo json_encode(['movies' => []]);
        exit;
    } else {
        // Redirect to home page if no search query
        header('Location: index.php');
        exit;
    }
}

// Get database connection
try {
    $db = getDBConnection();
    
    // Prepare search query with wildcards for partial matches
    $stmt = $db->prepare("
        SELECT * FROM movies 
        WHERE title LIKE ? OR description LIKE ? 
        ORDER BY rating DESC, created_at DESC
    ");
    
    $searchPattern = "%{$searchQuery}%";
    $stmt->execute([$searchPattern, $searchPattern]);
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For AJAX requests, return JSON response
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['movies' => $movies]);
        exit;
    }
    
} catch (PDOException $e) {
    error_log("Search error: " . $e->getMessage());
    
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error occurred', 'movies' => []]);
        exit;
    }
    
    $dbError = true;
}

// Get current user
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results for "<?php echo htmlspecialchars($searchQuery); ?>" - <?php echo SITE_NAME; ?></title>
    
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
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        /* Search Results Section */
        .search-header {
            background: linear-gradient(rgba(0,0,0,0.8), rgba(0,0,0,0.6)), url('assets/images/search-bg.jpg');
            background-size: cover;
            background-position: center;
            padding: 60px 0;
            text-align: center;
        }
        
        .search-title {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .search-query {
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .search-count {
            font-size: 1.1rem;
            color: var(--text-muted);
        }
        
        .movie-card {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            max-width: 220px;
            margin: 0 auto;
        }
        
        .movie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        
        .movie-poster {
            position: relative;
            overflow: hidden;
            height: 0;
            padding-top: 150%;
        }
        
        .movie-poster img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .movie-card:hover .movie-poster img {
            transform: scale(1.05);
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
            padding: 15px;
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
            padding: 3px 8px;
            border-radius: 4px;
            color: white;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .movie-info {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            background-color: #222;
        }
        
        .movie-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: white;
            height: 40px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .movie-meta {
            display: flex;
            gap: 10px;
            margin-bottom: 5px;
            font-size: 0.8rem;
            color: #aaa;
        }
        
        .movie-meta i {
            margin-right: 3px;
        }
        
        .movie-price {
            margin-top: auto;
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .no-results {
            background-color: rgba(0,0,0,0.6);
            padding: 30px;
            border-radius: 10px;
            text-align: center;
        }
        
        .no-results i {
            font-size: 3rem;
            color: var(--text-muted);
            margin-bottom: 20px;
        }
        
        .no-results h3 {
            margin-bottom: 15px;
        }
        
        /* Footer Styles */
        footer {
            background-color: #1a1a1a;
            color: #fff;
            padding: 3rem 0;
            margin-top: 5rem;
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
            <a class="navbar-brand" href="index.php"><?php echo SITE_NAME; ?></a>
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
                    <input class="form-control me-2" type="search" name="q" placeholder="Search movies..." aria-label="Search" value="<?php echo htmlspecialchars($searchQuery); ?>">
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
        <!-- Search Header -->
        <section class="search-header">
            <div class="container">
                <h1 class="search-title">Search Results for "<span class="search-query"><?php echo htmlspecialchars($searchQuery); ?></span>"</h1>
                <?php if (isset($movies) && !empty($movies)): ?>
                    <p class="search-count"><?php echo count($movies); ?> movies found</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Search Results Section -->
        <section class="search-results py-5">
            <div class="container">
                <?php if (isset($dbError) && $dbError): ?>
                    <div class="alert alert-danger">
                        <h4 class="alert-heading">Database Error</h4>
                        <p>A database error occurred while searching. Please try again later.</p>
                    </div>
                <?php elseif (isset($movies) && !empty($movies)): ?>
                    <div class="row g-4">
                        <?php foreach ($movies as $movie): 
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
                        ?>
                            <div class="col-lg-3 col-md-4 col-sm-6">
                                <div class="movie-card">
                                    <div class="movie-poster">
                                        <img src="<?php echo htmlspecialchars($poster); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                                        <div class="movie-overlay">
                                            <div class="movie-actions">
                                                <a href="movie.php?id=<?php echo $movie['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                                                <div class="movie-rating">
                                                    <i class="fas fa-star"></i>
                                                    <span><?php echo number_format($movie['rating'], 1); ?></span>
                                                </div>
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
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>No movies found for "<?php echo htmlspecialchars($searchQuery); ?>"</h3>
                        <p class="text-muted">Try different keywords or browse our movie collection.</p>
                        <a href="movies.php" class="btn btn-primary mt-3">Browse All Movies</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
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
</body>
</html> 