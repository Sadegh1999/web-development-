<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate username
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long';
    } elseif (strlen($username) > 50) {
        $errors[] = 'Username must be less than 50 characters';
    }
    
    // Validate email
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    // Validate password
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = 'Username or email already exists';
        }
    }
    
    // Create user if no errors
    if (empty($errors)) {
        try {
            $db = getDBConnection();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$username, $email, $hashed_password]);
            
            $success = true;
            
            // Log the user in
            $_SESSION['user_id'] = $db->lastInsertId();
            $_SESSION['username'] = $username;
            
            // Redirect to home page
            header('Location: index.php');
            exit;
            
        } catch (PDOException $e) {
            $errors[] = 'Registration failed. Please try again.';
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - StreamFlix</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
            min-height: 100vh;
        }
        
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
        
        .navbar-end {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .signin-btn {
            background-color: transparent;
            color: white;
            padding: 9px 19px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s, transform 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 2px solid white;
        }
        
        .signin-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        
        .help-btn {
            color: #e5e5e5;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s;
        }
        
        .help-btn i {
            font-size: 20px;
        }
        
        .help-btn:hover {
            color: #fff;
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
            }
            
            .navbar-menu {
                gap: 15px;
            }
            
            .help-text {
                display: none;
            }
        }
        
        .footer {
            background: linear-gradient(to top, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0.7) 100%);
            padding: 60px 40px 30px;
            margin-top: 60px;
            backdrop-filter: blur(10px);
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px;
        }
        
        .footer-section h3 {
            color: #fff;
            margin-bottom: 20px;
            font-size: 1.5em;
        }
        
        .footer-section h4 {
            color: #fff;
            margin-bottom: 15px;
            font-size: 1.1em;
        }
        
        .footer-section p {
            color: #999;
            margin-bottom: 20px;
        }
        
        .footer-section ul {
            list-style: none;
            padding: 0;
        }
        
        .footer-section ul li {
            margin-bottom: 10px;
        }
        
        .footer-section ul a {
            color: #999;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-section ul a:hover {
            color: #e50914;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
        }
        
        .social-links a {
            color: #fff;
            font-size: 1.2em;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            color: #e50914;
            transform: translateY(-2px);
        }
        
        .footer-bottom {
            max-width: 1200px;
            margin: 40px auto 0;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #999;
        }
        
        .language-selector {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .language-selector select {
            background: transparent;
            color: #fff;
            border: 1px solid #666;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .language-selector i {
            color: #999;
        }
        
        @media (max-width: 480px) {
            .footer-content {
                grid-template-columns: 1fr;
            }
        }
        
        .register-container {
            max-width: 400px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .register-box {
            background-color: #1a1a1a;
            border-radius: 8px;
            padding: 30px;
        }
        
        .register-title {
            text-align: center;
            margin-bottom: 30px;
            color: #fff;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #fff;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            background-color: #333;
            border: 1px solid #444;
            border-radius: 4px;
            color: #fff;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #e50914;
        }
        
        .register-btn {
            width: 100%;
            padding: 12px;
            background-color: #e50914;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        
        .register-btn:hover {
            background-color: #f40612;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #fff;
        }
        
        .login-link a {
            color: #e50914;
            text-decoration: none;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            color: #e50914;
            margin-bottom: 15px;
            padding: 10px;
            background-color: rgba(229, 9, 20, 0.1);
            border-radius: 4px;
        }
        
        .success-message {
            color: #28a745;
            margin-bottom: 15px;
            padding: 10px;
            background-color: rgba(40, 167, 69, 0.1);
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="index.php">
                <img src="assets/images/logo.png" alt="StreamFlix Logo">
            </a>
        </div>
        
        <div class="navbar-menu">
            <a href="index.php">Home</a>
            <a href="categories.php">Movies</a>
            <a href="about.php">About</a>
        </div>
        
        <div class="navbar-end">
            <a href="login.php" class="signin-btn">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </a>
            <a href="#" class="help-btn">
                <i class="fas fa-question-circle"></i>
                <span class="help-text">Help</span>
            </a>
        </div>
    </nav>

    <!-- Register Section -->
    <div class="register-container">
        <div class="register-box">
            <h1 class="register-title">Create Account</h1>
            
            <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="success-message">
                <p>Registration successful! Redirecting...</p>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="register-btn">Register</button>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>StreamFlix</h3>
                <p>Questions? Call 1-800-STREAMFLIX</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Navigation</h4>
                <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="#">Movies</a></li>
                    <li><a href="#">TV Shows</a></li>
                    <li><a href="#">New & Popular</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Help Center</h4>
                <ul>
                    <li><a href="#">Account</a></li>
                    <li><a href="#">Ways to Watch</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">Help Center</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Legal</h4>
                <ul>
                    <li><a href="#">Privacy</a></li>
                    <li><a href="#">Terms of Use</a></li>
                    <li><a href="#">Cookie Preferences</a></li>
                    <li><a href="#">Corporate Information</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2024 StreamFlix. All rights reserved.</p>
            <div class="language-selector">
                <i class="fas fa-globe"></i>
                <select>
                    <option value="en">English</option>
                    <option value="es">Español</option>
                    <option value="fr">Français</option>
                </select>
            </div>
        </div>
    </footer>
</body>
</html>
