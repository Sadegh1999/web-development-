<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 1) {
    header('Location: ../login.php?redirect=admin/add_movie.php');
    exit;
}

// Get database connection
try {
    $db = getDBConnection();
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get categories for the dropdown
$categories = [];
try {
    $stmt = $db->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}

// Handle form submission
$message = '';
$alertType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_movie'])) {
    try {
        // Validate required fields
        $requiredFields = ['title', 'description', 'price', 'release_year', 'duration', 'rating'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            throw new Exception("Missing required fields: " . implode(', ', $missingFields));
        }
        
        // Validate numeric fields
        if (!is_numeric($_POST['price']) || $_POST['price'] <= 0) {
            throw new Exception("Price must be a positive number.");
        }
        
        if (!is_numeric($_POST['release_year']) || $_POST['release_year'] < 1900 || $_POST['release_year'] > date('Y') + 5) {
            throw new Exception("Release year must be a valid year.");
        }
        
        if (!is_numeric($_POST['duration']) || $_POST['duration'] <= 0) {
            throw new Exception("Duration must be a positive number.");
        }
        
        if (!is_numeric($_POST['rating']) || $_POST['rating'] < 0 || $_POST['rating'] > 10) {
            throw new Exception("Rating must be between 0 and 10.");
        }
        
        // Clean and prepare data
        $title = trim($_POST['title']);
        $slug = createSlug($title);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $release_year = intval($_POST['release_year']);
        $duration = intval($_POST['duration']);
        $rating = floatval($_POST['rating']);
        $trailer_url = !empty($_POST['trailer_url']) ? trim($_POST['trailer_url']) : null;
        $status = isset($_POST['status']) ? 'active' : 'inactive';
        
        // Handle poster image upload
        $poster_url = ''; // Default placeholder
        
        if (!empty($_FILES['poster']['name'])) {
            $targetDir = "../assets/images/movies/";
            
            // Create directory if it doesn't exist
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            $fileName = basename($_FILES["poster"]["name"]);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newFileName = $slug . '-' . time() . '.' . $fileExt;
            $targetFile = $targetDir . $newFileName;
            
            // Check file type
            $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($fileExt, $allowedTypes)) {
                throw new Exception("Only JPG, JPEG, PNG, and WEBP files are allowed.");
            }
            
            // Check file size (5MB max)
            if ($_FILES["poster"]["size"] > 5000000) {
                throw new Exception("File is too large. Maximum size is 5MB.");
            }
            
            // Upload file
            if (move_uploaded_file($_FILES["poster"]["tmp_name"], $targetFile)) {
                $poster_url = 'assets/images/movies/' . $newFileName;
            } else {
                throw new Exception("Failed to upload poster image.");
            }
        } else if (!empty($_POST['poster_url'])) {
            // Use external URL
            $poster_url = trim($_POST['poster_url']);
        }
        
        // Insert movie into database
        $stmt = $db->prepare("
            INSERT INTO movies (
                title, slug, description, price, release_year, 
                duration, rating, poster_url, trailer_url, status, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, NOW()
            )
        ");
        
        $stmt->execute([
            $title, $slug, $description, $price, $release_year,
            $duration, $rating, $poster_url, $trailer_url, $status
        ]);
        
        $movieId = $db->lastInsertId();
        
        // Handle categories (if selected)
        if (!empty($_POST['categories'])) {
            $categories = $_POST['categories'];
            $stmt = $db->prepare("INSERT INTO movie_categories (movie_id, category_id) VALUES (?, ?)");
            
            foreach ($categories as $categoryId) {
                $stmt->execute([$movieId, $categoryId]);
            }
        }
        
        $message = "Movie \"$title\" has been added successfully!";
        $alertType = 'success';
        
        // Redirect to edit page or clear form
        header("Location: edit_movie.php?id=$movieId&success=added");
        exit;
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $alertType = 'danger';
    }
}

// Helper function to create a URL-friendly slug
function createSlug($string) {
    $string = preg_replace('/[^a-zA-Z0-9\s]/', '', $string);
    $string = trim($string);
    $string = str_replace(' ', '-', $string);
    $string = strtolower($string);
    return $string;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Movie - <?php echo SITE_NAME; ?> Admin</title>
    
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
        
        .form-card {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #555;
        }
        
        .form-check-label {
            font-weight: normal;
        }
        
        .image-preview {
            width: 100%;
            max-width: 250px;
            height: 300px;
            border-radius: 8px;
            overflow: hidden;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }
        
        .image-preview-placeholder {
            color: #adb5bd;
            font-size: 4rem;
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
                <a href="dashboard.php" class="admin-sidebar-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="admin-sidebar-item">
                <a href="movies.php" class="admin-sidebar-link">
                    <i class="fas fa-film"></i> Movies
                </a>
            </li>
            <li class="admin-sidebar-item">
                <a href="add_movie.php" class="admin-sidebar-link active">
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
    </div>
    
    <!-- Admin Content -->
    <div class="admin-content">
        <!-- Admin Header -->
        <div class="admin-header mb-4">
            <div class="admin-header-title">
                <h1>Add New Movie</h1>
            </div>
            <div class="admin-header-actions">
                <button id="sidebarToggle" class="btn btn-sm btn-outline-secondary d-block d-lg-none">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="movies.php" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-list"></i> All Movies
                </a>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="form-card">
            <form method="POST" action="add_movie.php" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <!-- Basic Information -->
                        <div class="form-group">
                            <label for="title">Movie Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="release_year">Release Year <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="release_year" name="release_year" min="1900" max="<?php echo date('Y') + 5; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="duration">Duration (min) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="duration" name="duration" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="rating">Rating (0-10) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="rating" name="rating" min="0" max="10" step="0.1" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="trailer_url">Trailer URL (YouTube or Vimeo)</label>
                            <input type="url" class="form-control" id="trailer_url" name="trailer_url">
                            <small class="text-muted">Enter the full URL to the trailer video.</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price">Price ($) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="price" name="price" min="0.01" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="status" name="status" checked>
                                        <label class="form-check-label" for="status">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Categories</label>
                            <div class="row">
                                <?php foreach ($categories as $category): ?>
                                    <div class="col-md-4">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>" id="category_<?php echo $category['id']; ?>">
                                            <label class="form-check-label" for="category_<?php echo $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <!-- Poster Image -->
                        <div class="form-group">
                            <label>Poster Image</label>
                            <div class="image-preview mb-3">
                                <div class="image-preview-placeholder">
                                    <i class="fas fa-film"></i>
                                </div>
                                <img src="" id="poster-preview">
                            </div>
                            
                            <div class="mb-3">
                                <label for="poster" class="form-label">Upload Poster</label>
                                <input class="form-control" type="file" id="poster" name="poster" accept="image/*">
                                <small class="text-muted">Recommended size: 300x450 pixels (2:3 ratio)</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="poster_url" class="form-label">Or Enter Poster URL</label>
                                <input type="url" class="form-control" id="poster_url" name="poster_url">
                                <small class="text-muted">Enter a direct link to an image (JPG, PNG, WEBP)</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-flex justify-content-between">
                    <button type="reset" class="btn btn-outline-secondary">Reset Form</button>
                    <button type="submit" name="add_movie" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Movie
                    </button>
                </div>
            </form>
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
            
            // Image preview functionality
            const posterInput = document.getElementById('poster');
            const posterUrlInput = document.getElementById('poster_url');
            const posterPreview = document.getElementById('poster-preview');
            const previewPlaceholder = document.querySelector('.image-preview-placeholder');
            
            posterInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        posterPreview.src = e.target.result;
                        posterPreview.style.display = 'block';
                        previewPlaceholder.style.display = 'none';
                        posterUrlInput.value = ''; // Clear URL input when file is selected
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                }
            });
            
            posterUrlInput.addEventListener('input', function() {
                if (this.value) {
                    posterPreview.src = this.value;
                    posterPreview.style.display = 'block';
                    previewPlaceholder.style.display = 'none';
                    posterInput.value = ''; // Clear file input when URL is entered
                    
                    // Handle image load error
                    posterPreview.onerror = function() {
                        posterPreview.style.display = 'none';
                        previewPlaceholder.style.display = 'flex';
                    };
                } else {
                    posterPreview.style.display = 'none';
                    previewPlaceholder.style.display = 'flex';
                }
            });
        });
    </script>
</body>
</html> 