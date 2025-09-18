<?php
session_start();

$error = '';
$success = '';

include 'includes/auth.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    $error = "Connection failed: " . $e->getMessage();
}

// Handle form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$error) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $user_type = $_POST['user_type'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($username) || empty($email) || empty($user_type) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $error = "Username must be 3-20 characters, only letters, numbers, underscores.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif (strlen($password) < 8 || 
              !preg_match('/[A-Z]/', $password) ||
              !preg_match('/[0-9]/', $password) ||
              !preg_match('/[\W]/', $password)) {
        $error = "Password must be 8+ chars, include uppercase, number, and symbol.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!in_array($user_type, ['student', 'teacher'])) {
        $error = "Please select a valid role.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->rowCount() > 0) {
                $error = "Username or email already exists.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, user_type, password, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$username, $email, $user_type, $hashed_password]);
                $success = "Account created successfully! <a href='login.php'>Login</a>";
                $_POST = [];
            }
        } catch(PDOException $e) {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register - EduMate</title>
  <style>
    /* Background */
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #1e293b 0%, #334155 40%, #f1f5f9 100%);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      margin: 0;
    }

    /* Auth container */
    .auth-container {
      width: 100%;
      max-width: 380px; /* tighter width */
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(15px);
      padding: 2rem;
      border-radius: 18px;
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
    }

    .auth-container h2 {
      text-align: center;
      margin-bottom: 1.5rem;
      font-size: 1.6rem;
      font-weight: 700;
      background: linear-gradient(135deg, #3b82f6, #06b6d4);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    /* Form */
    .form-group {
      margin-bottom: 1.2rem;
    }

    .form-group label {
      font-size: 0.9rem;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 0.4rem;
      display: block;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 0.8rem;
      font-size: 0.9rem;
      border: 2px solid #e2e8f0;
      border-radius: 10px;
      background: #fff;
      transition: all 0.3s ease;
    }

    .form-group input:focus,
    .form-group select:focus {
      border-color: #3b82f6;
      outline: none;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
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
      transition: transform 0.2s ease, background 0.3s ease;
    }

    .btn:hover {
      transform: translateY(-2px);
      background: linear-gradient(135deg, #2563eb, #1e40af);
    }

    /* Messages */
    .error-message,
    .success-message {
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

    /* Login link */
    .login-link {
      margin-top: 1rem;
      text-align: center;
      font-size: 0.9rem;
    }
    .login-link a {
      color: #3b82f6;
      text-decoration: none;
      font-weight: 600;
    }
    .login-link a:hover {
      color: #1d4ed8;
    }
  </style>
</head>
<body>
  <div class="auth-container">
    <h2>Create Account</h2>

    <?php if ($error): ?>
      <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="success-message"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" 
          value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
      </div>

      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" 
          value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
      </div>

      <div class="form-group">
        <label>I am a:</label>
        <select name="user_type" required>
          <option value="">Select role</option>
          <option value="student" <?= (isset($_POST['user_type']) && $_POST['user_type']=='student')?'selected':'' ?>>Student</option>
          <option value="teacher" <?= (isset($_POST['user_type']) && $_POST['user_type']=='teacher')?'selected':'' ?>>Teacher</option>
        </select>
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" id="password" required>
      </div>

      <div class="form-group">
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" id="confirm_password" required>
      </div>

      <button type="submit" class="btn">Register</button>
    </form>

    <div class="login-link">
      Already have an account? <a href="login.php">Login here</a>
    </div>
  </div>

  <script>
    // Live password check
    document.getElementById('confirm_password').addEventListener('input', function() {
      this.setCustomValidity(this.value !== document.getElementById('password').value ? "Passwords don't match" : "");
    });
  </script>
</body>
</html>
