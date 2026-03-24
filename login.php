<?php
session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username === "admin" && $password === "Ch@130405") {
        $_SESSION['user'] = $username;
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Inventory Management System</title>
    <link rel="stylesheet" href="login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        
        <!-- Left Branding Section -->
        <div class="branding">
            <div class="branding-content">
                <h1>Inventory Management</h1>
                <p>Professional stock, supplier & report management platform</p>
                <div class="features">
                    <div class="feature"><span>•</span> Real-time tracking</div>
                    <div class="feature"><span>•</span> Advanced analytics</div>
                    <div class="feature"><span>•</span> Secure access</div>
                </div>
            </div>
        </div>

        <!-- Right Login Section -->
        <div class="login-section">
            <div class="login-card">
                <div class="card-header">
                    <h2>Welcome Back</h2>
                    <p>Please sign in to your account</p>
                </div>

                <?php if($error != ""): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" class="login-form">
                    <div class="form-group">
                        <input type="text" name="username" required autocomplete="username">
                        <label>Username</label>
                        <span class="input-icon">👤</span>
                    </div>

                    <div class="form-group">
                        <input type="password" name="password" required autocomplete="current-password">
                        <label>Password</label>
                        <span class="input-icon">🔒</span>
                    </div>

                    <button type="submit" class="login-btn">
                        <span>Sign In</span>
                        <svg class="btn-icon" width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </form>

                <div class="login-footer">
                    <p>© 2026 Inventory Management System. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
