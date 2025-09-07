<?php
require_once 'includes/auth.php';

// If user is already logged in, redirect to home page
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $user_type = $_POST['user_type'];
    
    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        $result = registerUser($username, $email, $password, $user_type);
        if ($result === true) {
            // Redirect to login page after successful registration
            header("Location: login.php?registered=1");
            exit();
        } else {
            $error = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - EduMate</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">EduMate</div>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </nav>
    </header>

    <section class="auth-container">
        <div class="auth-form">
            <h2>Create an EduMate Account</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo h($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo isset($_POST['username']) ? h($_POST['username']) : ''; ?>" 
                           required>
                    <small>3-20 characters, letters, numbers, and underscores only</small>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo isset($_POST['email']) ? h($_POST['email']) : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="user_type">I am a:</label>
                    <select id="user_type" name="user_type" required>
                        <option value="">Select your role</option>
                        <option value="student" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'student') ? 'selected' : ''; ?>>Student</option>
                        <option value="teacher" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <small>Password must be at least 6 characters long</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn" style="width: 100%;">Register</button>
            </form>
            
            <p style="margin-top: 1rem; text-align: center;">
                Already have an account? <a href="login.php" style="color: #6c63ff;">Login here</a>
            </p>
        </div>
    </section>

    <footer>
        <p>&copy; 2024 EduMate. All rights reserved.</p>
    </footer>

    <script>
        // Client-side password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword && confirmPassword.length > 0) {
                this.setCustomValidity('Passwords do not match');
                this.style.borderColor = '#c62828';
            } else {
                this.setCustomValidity('');
                this.style.borderColor = '';
            }
        });
        
        // Username validation
        document.getElementById('username').addEventListener('input', function() {
            const username = this.value;
            const regex = /^[a-zA-Z0-9_]{3,20}$/;
            
            if (username.length > 0 && !regex.test(username)) {
                this.setCustomValidity('Username must be 3-20 characters long and contain only letters, numbers, and underscores');
                this.style.borderColor = '#c62828';
            } else {
                this.setCustomValidity('');
                this.style.borderColor = '';
            }
        });
        
        // Add loading state to form
        document.getElementById('registerForm').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creating Account...';
        });
    </script>
</body>

</html>
