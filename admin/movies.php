<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 1) {
    header("Location: ../login.php");
    exit();
}

// Initialize database connection
try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle movie deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $movie_id = $_GET['delete'];
    
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // Delete movie-category relationships
        $stmt = $db->prepare("DELETE FROM movie_categories WHERE movie_id = ?");
        $stmt->execute([$movie_id]);
        
        // Delete the movie
        $stmt = $db->prepare("DELETE FROM movies WHERE id = ?");
        $stmt->execute([$movie_id]);
        
        // Commit transaction
        $db->commit();
        
        $_SESSION['success'] = "Movie deleted successfully!";
    } catch (PDOException $e) {
        // Rollback transaction on error
        $db->rollBack();
        $_SESSION['error'] = "Failed to delete movie: " . $e->getMessage();
    }
    
    header("Location: movies.php");
    exit();
}

// Fetch all movies
try {
    $query = "SELECT m.*, 
              GROUP_CONCAT(c.name SEPARATOR ', ') as categories
              FROM movies m
              LEFT JOIN movie_categories mc ON m.id = mc.movie_id
              LEFT JOIN categories c ON mc.category_id = c.id
              GROUP BY m.id
              ORDER BY m.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Movies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --background-light: #f8f9fa;
            --text-dark: #343a40;
            --text-light: #f8f9fa;
        }
        
        body {
            background-color: var(--background-light);
            color: var(--text-dark);
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--secondary-color), #1a252f);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .movie-image {
            height: 100px;
            width: auto;
            object-fit: cover;
        }
        
        .table-responsive {
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-danger {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .alert {
            border-radius: 8px;
            font-weight: 500;
        }
        
        .admin-header {
            background-color: var(--secondary-color);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="../index.php">MovieHub Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="movies.php">Movies</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">Orders</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="../logout.php" class="btn btn-outline-light">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="admin-header d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-film me-2"></i>Manage Movies</h2>
            <a href="add_movie.php" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Add New Movie
            </a>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive bg-white">
            <table class="table table-hover table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Price</th>
                        <th>Release Year</th>
                        <th>Categories</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($movies) > 0): ?>
                        <?php foreach ($movies as $movie): ?>
                            <tr>
                                <td><?php echo $movie['id']; ?></td>
                                <td>
                                    <?php if (!empty($movie['poster'])): ?>
                                        <img src="../uploads/posters/<?php echo $movie['poster']; ?>" class="movie-image img-thumbnail">
                                    <?php else: ?>
                                        <div class="bg-secondary text-white movie-image d-flex align-items-center justify-content-center">No Image</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($movie['title']); ?></strong>
                                    <div class="small text-muted">Duration: <?php echo $movie['duration']; ?> min</div>
                                </td>
                                <td>$<?php echo number_format($movie['price'], 2); ?></td>
                                <td><?php echo $movie['release_year']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($movie['categories'] ?: 'None'); ?>
                                </td>
                                <td>
                                    <?php if ($movie['is_popular']): ?>
                                        <span class="badge bg-warning">Popular</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($movie['is_new']): ?>
                                        <span class="badge bg-info">New</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="../movie.php?slug=<?php echo $movie['slug']; ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_movie.php?id=<?php echo $movie['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $movie['id']; ?>, '<?php echo addslashes($movie['title']); ?>')" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">No movies found. <a href="add_movie.php">Add a movie</a> to get started.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id, title) {
            if (confirm(`Are you sure you want to delete "${title}"? This action cannot be undone.`)) {
                window.location.href = `movies.php?delete=${id}`;
            }
        }
    </script>
</body>
</html> 