<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    header('Location: ../login.php?redirect=admin/dashboard.php');
    exit;
}

// Get database connection
try {
    $db = getDBConnection();
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get current admin user
$userId = $_SESSION['user_id'];
$adminUser = null;

try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role_id = 1");
    $stmt->execute([$userId]);
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$adminUser) {
        header('Location: ../index.php');
        exit;
    }
} catch (PDOException $e) {
    die("Error fetching admin data: " . $e->getMessage());
}

// Process action requests (delete, status change, etc.)
$message = '';
$alertType = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $movieId = (int)$_GET['id'];
    
    try {
        switch ($action) {
            case 'delete':
                // First check if the movie is used in any orders
                $stmt = $db->prepare("SELECT COUNT(*) FROM order_items WHERE movie_id = ?");
                $stmt->execute([$movieId]);
                $usageCount = $stmt->fetchColumn();
                
                if ($usageCount > 0) {
                    $message = "Cannot delete movie ID $movieId because it's used in $usageCount orders. Consider disabling it instead.";
                    $alertType = 'danger';
                } else {
                    // Delete movie
                    $stmt = $db->prepare("DELETE FROM movies WHERE id = ?");
                    $stmt->execute([$movieId]);
                    $message = "Movie ID $movieId deleted successfully.";
                    $alertType = 'success';
                }
                break;
                
            case 'toggle':
                // Toggle movie status (active/inactive)
                $stmt = $db->prepare("SELECT status FROM movies WHERE id = ?");
                $stmt->execute([$movieId]);
                $currentStatus = $stmt->fetchColumn();
                
                $newStatus = ($currentStatus == 'active') ? 'inactive' : 'active';
                
                $stmt = $db->prepare("UPDATE movies SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $movieId]);
                $message = "Movie ID $movieId status changed to $newStatus.";
                $alertType = 'success';
                break;
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $alertType = 'danger';
    }
}

// Get all movies for the dashboard
$movies = [];
try {
    $stmt = $db->query("
        SELECT m.*, 
               COUNT(oi.id) as order_count,
               (SELECT COUNT(*) FROM watchlist w WHERE w.movie_id = m.id) as watchlist_count
        FROM movies m
        LEFT JOIN order_items oi ON m.id = oi.movie_id
        GROUP BY m.id
        ORDER BY m.created_at DESC
    ");
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error fetching movies: " . $e->getMessage();
    $alertType = 'danger';
}

// Get dashboard stats
$stats = [
    'total_movies' => 0,
    'active_movies' => 0,
    'total_orders' => 0,
    'total_revenue' => 0,
    'total_users' => 0
];

try {
    // Total movies
    $stmt = $db->query("SELECT COUNT(*) FROM movies");
    $stats['total_movies'] = $stmt->fetchColumn();
    
    // Active movies
    $stmt = $db->query("SELECT COUNT(*) FROM movies WHERE status = 'active'");
    $stats['active_movies'] = $stmt->fetchColumn();
    
    // Total orders
    $stmt = $db->query("SELECT COUNT(*) FROM orders");
    $stats['total_orders'] = $stmt->fetchColumn();
    
    // Total revenue
    $stmt = $db->query("SELECT SUM(total_amount) FROM orders WHERE status = 'completed'");
    $stats['total_revenue'] = $stmt->fetchColumn() ?? 0;
    
    // Total users
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role_id = 2");
    $stats['total_users'] = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Silently fail stats collection
    error_log("Error fetching dashboard stats: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #e50914;
            --dark-color: #141414;
            --light-gray: #f8f9fa;
            --admin-sidebar: #212529;
        }
        
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-sidebar {
            background-color: var(--admin-sidebar);
            min-height: 100vh;
            color: white;
            position: fixed;
            width: 260px;
            left: 0;
            top: 0;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .admin-sidebar-header {
            padding: 20px;
            background-color: rgba(0, 0, 0, 0.2);
        }
        
        .admin-sidebar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .admin-sidebar-menu {
            padding: 0;
            list-style: none;
        }
        
        .admin-sidebar-item {
            margin: 0;
            padding: 0;
        }
        
        .admin-sidebar-link {
            padding: 15px 20px;
            color: #adb5bd;
            display: block;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .admin-sidebar-link:hover,
        .admin-sidebar-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: var(--primary-color);
        }
        
        .admin-sidebar-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .admin-sidebar-user {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
        }
        
        .admin-sidebar-user-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .admin-sidebar-user-role {
            color: #adb5bd;
            font-size: 0.9rem;
        }
        
        .admin-content {
            margin-left: 260px;
            padding: 30px;
            transition: all 0.3s;
        }
        
        .admin-header {
            background-color: white;
            border-bottom: 1px solid #dee2e6;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .admin-header-title h1 {
            font-size: 1.5rem;
            margin: 0;
            font-weight: 600;
        }
        
        .admin-header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: rgba(229, 9, 20, 0.1);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        
        .stat-card-value {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-card-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .movie-card {
            position: relative;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            height: 100%;
        }
        
        .movie-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .movie-card-status {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            z-index: 2;
        }
        
        .movie-card-status.active {
            background-color: #28a745;
            color: white;
        }
        
        .movie-card-status.inactive {
            background-color: #dc3545;
            color: white;
        }
        
        .movie-card-image {
            position: relative;
            overflow: hidden;
            height: 220px;
        }
        
        .movie-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .movie-card:hover .movie-card-image img {
            transform: scale(1.05);
        }
        
        .movie-card-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0.3));
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 15px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .movie-card:hover .movie-card-overlay {
            opacity: 1;
        }
        
        .movie-card-body {
            padding: 15px;
        }
        
        .movie-card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
            height: 48px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .movie-card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .movie-card-meta-item {
            display: flex;
            align-items: center;
        }
        
        .movie-card-meta-item i {
            margin-right: 5px;
        }
        
        .movie-card-price {
            font-weight: bold;
            color: var(--primary-color);
            font-size: 1.2rem;
        }
        
        .movie-card-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        .action-button {
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background-color: rgba(0, 0, 0, 0.1);
            color: #444;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .action-button:hover {
            background-color: #f8f9fa;
        }
        
        .action-button.edit:hover {
            background-color: #007bff;
            color: white;
        }
        
        .action-button.toggle:hover {
            background-color: #28a745;
            color: white;
        }
        
        .action-button.delete:hover {
            background-color: #dc3545;
            color: white;
        }
        
        .movie-table {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .movie-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #555;
        }
        
        .movie-thumbnail {
            width: 60px;
            height: 90px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .badge.bg-success {
            background-color: #28a745 !important;
        }
        
        .badge.bg-danger {
            background-color: #dc3545 !important;
        }
        
        @media (max-width: 992px) {
            .admin-sidebar {
                left: -260px;
            }
            
            .admin-content {
                margin-left: 0;
            }
            
            .admin-sidebar.show {
                left: 0;
            }
            
            .admin-content.push {
                margin-left: 260px;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Sidebar -->
    <div class="admin-sidebar">
        <div class="admin-sidebar-header">
            <a href="dashboard.php" class="admin-sidebar-brand"><?php echo SITE_NAME; ?> Admin</a>
        </div>
        
        <ul class="admin-sidebar-menu mt-4">
            <li class="admin-sidebar-item">
                <a href="dashboard.php" class="admin-sidebar-link active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="admin-sidebar-item">
                <a href="movies.php" class="admin-sidebar-link">
                    <i class="fas fa-film"></i> Movies
                </a>
            </li>
            <li class="admin-sidebar-item">
                <a href="add_movie.php" class="admin-sidebar-link">
                    <i class="fas fa-plus"></i> Add Movie
                </a>
            </li>
            <li class="admin-sidebar-item">
                <a href="categories.php" class="admin-sidebar-link">
                    <i class="fas fa-tags"></i> Categories
                </a>
            </li>
            <li class="admin-sidebar-item">
                <a href="orders.php" class="admin-sidebar-link">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
            </li>
            <li class="admin-sidebar-item">
                <a href="users.php" class="admin-sidebar-link">
                    <i class="fas fa-users"></i> Users
                </a>
            </li>
            <li class="admin-sidebar-item">
                <a href="settings.php" class="admin-sidebar-link">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
            <li class="admin-sidebar-item">
                <a href="../index.php" class="admin-sidebar-link">
                    <i class="fas fa-home"></i> Back to Site
                </a>
            </li>
            <li class="admin-sidebar-item">
                <a href="../logout.php" class="admin-sidebar-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
        
        <div class="admin-sidebar-user mt-auto">
            <div class="admin-sidebar-user-name">
                <?php echo htmlspecialchars(
                    (isset($adminUser['first_name']) && !empty($adminUser['first_name'])) ? 
                    $adminUser['first_name'] . ' ' . ($adminUser['last_name'] ?? '') : 
                    $adminUser['username']
                ); ?>
            </div>
            <div class="admin-sidebar-user-role">Administrator</div>
        </div>
    </div>
    
    <!-- Admin Content -->
    <div class="admin-content">
        <!-- Admin Header -->
        <div class="admin-header mb-4">
            <div class="admin-header-title">
                <h1>Dashboard</h1>
            </div>
            <div class="admin-header-actions">
                <button id="sidebarToggle" class="btn btn-sm btn-outline-secondary d-block d-lg-none">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="add_movie.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> Add New Movie
                </a>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-card-icon">
                        <i class="fas fa-film"></i>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($stats['total_movies']); ?></div>
                    <div class="stat-card-label">Total Movies</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-card-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($stats['active_movies']); ?></div>
                    <div class="stat-card-label">Active Movies</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-card-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($stats['total_orders']); ?></div>
                    <div class="stat-card-label">Total Orders</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-card-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-card-value">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
                    <div class="stat-card-label">Total Revenue</div>
                </div>
            </div>
        </div>
        
        <!-- Recent Movies -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 mb-0">Movie Management</h2>
                <a href="movies.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            
            <div class="movie-table">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th width="70">Image</th>
                                <th>Title</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Orders</th>
                                <th>Date Added</th>
                                <th width="100">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($movies)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">No movies found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (array_slice($movies, 0, 10) as $movie): ?>
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
                                    ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo htmlspecialchars('../' . $poster); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="movie-thumbnail">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($movie['title']); ?></strong>
                                        </td>
                                        <td>$<?php echo number_format($movie['price'], 2); ?></td>
                                        <td>
                                            <?php if (isset($movie['status']) && $movie['status'] == 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo number_format($movie['order_count']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($movie['created_at'])); ?></td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="edit_movie.php?id=<?php echo $movie['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="dashboard.php?action=toggle&id=<?php echo $movie['id']; ?>" class="btn btn-sm btn-outline-warning" title="Toggle Status">
                                                    <i class="fas fa-power-off"></i>
                                                </a>
                                                <?php if ($movie['order_count'] == 0): ?>
                                                    <a href="dashboard.php?action=delete&id=<?php echo $movie['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this movie?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Featured Movies Grid -->
        <div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 mb-0">Featured Movies</h2>
                <a href="featured.php" class="btn btn-sm btn-outline-primary">Manage Featured</a>
            </div>
            
            <div class="row g-4">
                <?php 
                // Get 4 most ordered movies as "featured"
                $featuredMovies = array_slice(array_filter($movies, function($movie) {
                    return isset($movie['status']) && $movie['status'] == 'active';
                }), 0, 4);
                
                if (empty($featuredMovies)):
                ?>
                    <div class="col-12">
                        <div class="alert alert-info">No active movies to feature.</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($featuredMovies as $movie): ?>
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
                        ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="movie-card">
                                <div class="movie-card-status <?php echo isset($movie['status']) ? $movie['status'] : 'inactive'; ?>"><?php echo isset($movie['status']) ? ucfirst($movie['status']) : 'Inactive'; ?></div>
                                <div class="movie-card-image">
                                    <img src="<?php echo htmlspecialchars('../' . $poster); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                                    <div class="movie-card-overlay">
                                        <div class="movie-card-actions">
                                            <a href="edit_movie.php?id=<?php echo $movie['id']; ?>" class="btn btn-sm btn-primary w-100 mb-2">Edit Movie</a>
                                            <a href="../movie.php?id=<?php echo $movie['id']; ?>" class="btn btn-sm btn-outline-light w-100" target="_blank">View on Site</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="movie-card-body">
                                    <h3 class="movie-card-title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                                    <div class="movie-card-meta">
                                        <div class="movie-card-meta-item">
                                            <i class="fas fa-calendar"></i> <?php echo $movie['release_year']; ?>
                                        </div>
                                        <div class="movie-card-meta-item">
                                            <i class="fas fa-star"></i> <?php echo number_format($movie['rating'], 1); ?>
                                        </div>
                                        <div class="movie-card-meta-item">
                                            <i class="fas fa-shopping-cart"></i> <?php echo number_format($movie['order_count']); ?> orders
                                        </div>
                                    </div>
                                    <div class="movie-card-price">$<?php echo number_format($movie['price'], 2); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle for mobile
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.admin-sidebar');
            const content = document.querySelector('.admin-content');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    content.classList.toggle('push');
                });
            }
        });
    </script>
</body>
</html> 