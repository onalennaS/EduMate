<?php
// register.php
require_once 'includes/auth.php'; // This should define $pdo (PDO instance)

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $user_type  = trim($_POST['user_type'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';

    // 1. Validation
    if (empty($username) || empty($email) || empty($user_type) || empty($password) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $error = "Username must be 3-20 characters and only contain letters, numbers, and underscores.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        try {
            // 2. Check if username/email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $stmt->execute(['username' => $username, 'email' => $email]);

            if ($stmt->fetch()) {
                $error = "Username or email already exists.";
            } else {
                // 3. Insert new user
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                $stmt = $pdo->prepare("INSERT INTO users (username, email, user_type, password) 
                                       VALUES (:username, :email, :user_type, :password)");
                $successInsert = $stmt->execute([
                    'username'   => $username,
                    'email'      => $email,
                    'user_type'  => $user_type,
                    'password'   => $hashedPassword
                ]);

                if ($successInsert) {
                    $success = "Account created successfully! You can now <a href='login.php'>login</a>.";
                } else {
                    $error = "Something went wrong. Please try again.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
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
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #2d3748;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 30%, #f1f5f9 100%);
            min-height: 100vh;
        }

        /* Header and Navigation */
        header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #475569 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 8px 32px rgba(30, 41, 59, 0.15);
            position: relative;
            overflow: hidden;
        }

        header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="0.5" fill="rgba(255,255,255,0.03)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 1;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, #ffffff 0%, #e2e8f0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        nav a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
        }

        nav a:hover {
            background: rgba(59, 130, 246, 0.1);
            color: #60a5fa;
            transform: translateY(-2px);
        }

        nav a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #3b82f6, #06b6d4);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        nav a:hover::after {
            width: 80%;
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 0.7rem 1.3rem;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.25);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.35);
        }

        .btn-large {
            padding: 1rem 2rem;
            font-size: 1.05rem;
            border-radius: 12px;
        }

        /* Auth Container - UPDATED */
        .auth-container {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .auth-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 2.5rem;
            border-radius: 24px;
            box-shadow: 
                0 20px 50px rgba(0, 0, 0, 0.1),
                0 2px 10px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 520px; /* Increased from 420px */
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* UPDATED: Compact header styling */
        .auth-form h2 {
            text-align: center;
            margin-bottom: 1.5rem; /* Reduced from 2.5rem */
            background: linear-gradient(135deg, #1e293b 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.8rem; /* Reduced from 2.2rem */
            font-weight: 800;
            display: inline-block; /* Makes it take only necessary width */
            width: 100%;
            white-space: nowrap; /* Keeps it on one line */
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem; /* Slightly reduced */
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #1e293b;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 1rem; /* Slightly reduced padding */
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            background: white;
        }

        .form-group small {
            display: block;
            margin-top: 0.5rem;
            color: #64748b;
            font-size: 0.85rem;
        }

        /* Messages */
        .error-message {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: #dc2626;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #ef4444;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.1);
            font-size: 0.95rem;
            font-weight: 500;
        }

        .success-message {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: #16a34a;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #22c55e;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.1);
            font-size: 0.95rem;
            font-weight: 500;
        }

        /* Footer */
        footer {
            background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #475569 100%);
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 2rem;
            font-size: 1rem;
            font-weight: 500;
            box-shadow: 0 -8px 32px rgba(30, 41, 59, 0.1);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .auth-form {
                margin: 1rem;
                padding: 2rem 1.5rem;
                max-width: 450px; /* Slightly wider on mobile too */
            }
            
            .auth-form h2 {
                font-size: 1.6rem;
                margin-bottom: 1.2rem;
            }
            
            .form-group {
                margin-bottom: 1.3rem;
            }
        }

        /* Additional styling for better layout */
        .login-link {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.95rem;
        }

        .login-link a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: #1d4ed8;
        }
    </style>
</head>
<body>

    <section class="auth-container">
        <div class="auth-form">
            <h2>Create an Account</h2>
            
            <!-- Demo error message for illustration -->
            <!-- <div class="error-message">Demo error message</div> -->
            
            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                    <small>3-20 characters, letters, numbers, and underscores only</small>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="user_type">I am a:</label>
                    <select id="user_type" name="user_type" required>
                        <option value="">Select your role</option>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
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
            
            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
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