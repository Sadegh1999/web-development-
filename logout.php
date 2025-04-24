<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_logout'])) {
    // Clear all session variables
    $_SESSION = array();

    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Destroy the session
    session_destroy();

    // Redirect to home page
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - StreamFlix</title>
    
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
        
        .logout-container {
            max-width: 450px;
            margin: 60px auto;
            padding: 20px;
            text-align: center;
        }
        
        .logout-box {
            background-color: rgba(26, 26, 26, 0.95);
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .logout-icon {
            font-size: 48px;
            color: #e50914;
            margin-bottom: 20px;
        }
        
        .logout-title {
            color: #fff;
            font-size: 2em;
            margin-bottom: 15px;
        }
        
        .logout-message {
            color: #ccc;
            margin-bottom: 30px;
            font-size: 1.1em;
            line-height: 1.5;
        }
        
        .countdown {
            color: #e50914;
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 25px;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-confirm {
            background: linear-gradient(45deg, #e50914, #ff0f1f);
            color: #fff;
            border: none;
        }
        
        .btn-confirm:hover {
            background: linear-gradient(45deg, #ff0f1f, #e50914);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(229, 9, 20, 0.4);
        }
        
        .btn-cancel {
            background: transparent;
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="index.php">
                <img src="assets/images/logo.png" alt="StreamFlix" class="logo">
            </a>
        </div>
    </nav>

    <!-- Logout Section -->
    <div class="logout-container">
        <div class="logout-box">
            <div class="logout-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h1 class="logout-title">Sign Out</h1>
            <p class="logout-message">Are you sure you want to sign out?<br>You'll need to sign in again to access your account.</p>
            
            <div class="countdown" id="countdown"></div>
            
            <div class="btn-group">
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="confirm_logout" value="1">
                    <button type="submit" class="btn btn-confirm">Sign Out</button>
                </form>
                <a href="index.php" class="btn btn-cancel">Cancel</a>
            </div>
        </div>
    </div>

    <script>
        // Auto-logout countdown
        let timeLeft = 10;
        const countdownElement = document.getElementById('countdown');
        
        function updateCountdown() {
            countdownElement.textContent = `Automatic sign out in ${timeLeft} seconds`;
            if (timeLeft === 0) {
                document.querySelector('form').submit();
            } else {
                timeLeft--;
                setTimeout(updateCountdown, 1000);
            }
        }
        
        updateCountdown();
    </script>
</body>
</html> 