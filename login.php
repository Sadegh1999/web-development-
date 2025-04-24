<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    // Authenticate user if no validation errors
    if (empty($errors)) {
        try {
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT id, username, password, role_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role_id'] = $user['role_id'];
                
                // Redirect to home page or requested page
                $redirect = $_GET['redirect'] ?? 'index.php';
                header("Location: $redirect");
                exit;
            } else {
                $errors[] = 'Invalid email or password';
            }
        } catch (PDOException $e) {
            $errors[] = 'Login failed. Please try again.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - StreamFlix</title>
    
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
        
        .login-container {
            max-width: 450px;
            margin: 60px auto;
            padding: 20px;
        }
        
        .login-box {
            background-color: rgba(26, 26, 26, 0.95);
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .login-title {
            text-align: center;
            margin-bottom: 35px;
            color: #fff;
            font-size: 2.2em;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #fff;
            font-size: 0.95em;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            background-color: rgba(51, 51, 51, 0.8);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #fff;
            font-size: 1em;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #e50914;
            background-color: rgba(51, 51, 51, 0.95);
            box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.2);
        }
        
        .form-group i {
            position: absolute;
            right: 15px;
            top: 42px;
            color: #666;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .form-group i:hover {
            color: #fff;
        }
        
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(45deg, #e50914, #ff0f1f);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            margin-top: 25px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .login-btn:hover {
            background: linear-gradient(45deg, #ff0f1f, #e50914);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(229, 9, 20, 0.4);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .register-link {
            text-align: center;
            margin-top: 25px;
            color: #ccc;
            font-size: 0.95em;
        }
        
        .register-link a {
            color: #e50914;
            text-decoration: none;
            font-weight: 600;
            margin-left: 5px;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            color: #ff3b3b;
            margin-bottom: 20px;
            padding: 12px 15px;
            background-color: rgba(255, 59, 59, 0.1);
            border-radius: 8px;
            border-left: 4px solid #ff3b3b;
            font-size: 0.9em;
        }
        
        .forgot-password {
            text-align: right;
            margin: -10px 0 20px;
        }
        
        .forgot-password a {
            color: #ccc;
            text-decoration: none;
            font-size: 0.9em;
            transition: color 0.3s ease;
        }
        
        .forgot-password a:hover {
            color: #e50914;
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 25px 0;
            color: #666;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #333;
        }
        
        .divider span {
            padding: 0 10px;
            font-size: 0.9em;
        }
        
        .social-login {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .social-btn {
            flex: 1;
            padding: 12px;
            border: 1px solid #333;
            border-radius: 8px;
            background: transparent;
            color: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .social-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: #666;
        }
        
        .social-btn i {
            font-size: 1.2em;
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
        
        .signup-btn {
            background-color: #e50914;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s, transform 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .signup-btn:hover {
            background-color: #f40612;
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
            <a href="register.php" class="signup-btn">
                <i class="fas fa-user-plus"></i> Sign Up
            </a>
            <a href="#" class="help-btn">
                <i class="fas fa-question-circle"></i>
                <span class="help-text">Help</span>
            </a>
        </div>
    </nav>

    <!-- Login Section -->
    <div class="login-container">
        <div class="login-box">
            <h1 class="login-title">Welcome Back</h1>
            
            <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    <i class="fas fa-envelope"></i>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <i class="fas fa-eye" id="togglePassword"></i>
                </div>
                
                <div class="forgot-password">
                    <a href="forgot-password.php">Forgot your password?</a>
                </div>
                
                <button type="submit" class="login-btn">Sign In</button>
                
                <div class="divider">
                    <span>or continue with</span>
                </div>
                
                <div class="social-login">
                    <button type="button" class="social-btn">
                        <i class="fab fa-google"></i>
                        Google
                    </button>
                    <button type="button" class="social-btn">
                        <i class="fab fa-facebook-f"></i>
                        Facebook
                    </button>
                </div>
            </form>
            
            <div class="register-link">
                New to StreamFlix?<a href="register.php">Create an account</a>
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
    
    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
    </script>
</body>
</html>
