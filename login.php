<?php
require_once 'auth.php';

// If user is already logged in, redirect to dashboard
redirectIfLoggedIn();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    $result = loginUser($email, $password);
    if ($result === true) {
        header("Location: dashboard.php");
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
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>
    

    <section class="auth-container">
        <div class="auth-form">
            <h2>Login to EduMate</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo h($error); ?></div>
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

    

    <script>
        function fillDemo(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
        }
        
        // Add loading state to form
        document.getElementById('loginForm').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Logging in...';
        });
    </script>
</body>
</html>