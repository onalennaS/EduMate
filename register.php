<?php
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$error) {
    // Get form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $user_type = 'student'; // Only students can register themselves
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    }
    
    // Username validation
    elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $error = "Username must be 3-20 characters long and contain only letters, numbers, and underscores.";
    }
    
    // Email validation
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    }
    
    // Password validation
    elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    }
    
    // Password confirmation
    elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    }
    
    else {
        $result = registerUser($username, $email, $password, $user_type);
        if ($result === true) {
            $success = "Account created successfully! You can now <a href='login.php'>login</a>.";
            // Clear form data on success
            $_POST = array();
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
    <style>
 /* Reset */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: linear-gradient(135deg, #1e293b 0%, #334155 40%, #f1f5f9 100%);
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
}

.auth-container {
  width: 100%;
  max-width: 420px;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(15px);
  padding: 2rem;
  border-radius: 18px;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
}

.auth-form h2 {
  text-align: center;
  margin-bottom: 1.5rem;
  font-size: 1.6rem;
  font-weight: 700;
  background: linear-gradient(135deg, #3b82f6, #06b6d4);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.form-group { margin-bottom: 1.2rem; }

.form-group label {
  display: block;
  font-size: 0.9rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 0.4rem;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 0.9rem;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.9rem;
  transition: 0.3s ease;
  background: #fff;
}

.form-group input:focus,
.form-group select:focus {
  border-color: #3b82f6;
  outline: none;
  box-shadow: 0 0 0 3px rgba(59,130,246,0.2);
}

.btn {
  width: 100%;
  padding: 0.9rem;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  border: none;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
  cursor: pointer;
  transition: 0.2s ease;
}

.btn:hover {
  transform: translateY(-2px);
  background: linear-gradient(135deg, #2563eb, #1e40af);
}

.error-message, .success-message {
  margin-bottom: 1rem;
  padding: 0.9rem 1rem;
  border-radius: 10px;
  font-size: 0.9rem;
}
.error-message {
  background: #fee2e2;
  color: #dc2626;
  border-left: 4px solid #ef4444;
}
.success-message {
  background: #dcfce7;
  color: #16a34a;
  border-left: 4px solid #22c55e;
}

.login-link {
  margin-top: 1rem;
  text-align: center;
  font-size: 0.9rem;
}
.login-link a {
  color: #3b82f6;
  font-weight: 600;
  text-decoration: none;
}
.login-link a:hover { color: #1d4ed8; }

.info-box {
  background: #e0f2fe;
  border-left: 4px solid #0ea5e9;
  color: #0369a1;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  font-size: 0.9rem;
}

    </style>
</head>
<body>
    <section class="auth-container">
        <div class="auth-form">
            <h2>Student Registration</h2>
            
            <div class="info-box">
                <strong>Note:</strong> Only students can register themselves. Teachers are added by the IT Administrator.
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                           required>
                    <small>3-20 characters, letters, numbers, and underscores only</small>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                           required>
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
            
            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </section>


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