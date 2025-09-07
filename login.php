<?php
require_once 'includes/auth.php';

// If user is already logged in, redirect to home page
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Check if user just registered
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success = 'Registration successful! Please login with your credentials.';
}

// Check if user just logged out
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $success = 'You have been successfully logged out.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    $result = loginUser($email, $password);
    if ($result === true) {
        // Successful login - redirect to home page
        header("Location: index.php");
        exit();
    } else {
        $error = $result;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EduMate</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">EduMate</div>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="register.php">Sign Up</a></li>
            </ul>
        </nav>
    </header>

    <section class="auth-container">
        <div class="auth-form">
            <h2>Login to EduMate</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo h($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo isset($_POST['email']) ? h($_POST['email']) : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn" style="width: 100%;">Login</button>
            </form>
            
            <p style="margin-top: 1rem; text-align: center;">
                Don't have an account? <a href="register.php" style="color: #6c63ff;">Register here</a>
            </p>
        </div>
    </section>

    <footer>
        <p>&copy; 2024 EduMate. All rights reserved.</p>
    </footer>

    <script>
        // Add loading state to form
        document.getElementById('loginForm').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Logging in...';
        });
    </script>
</body>

</html>
