<?php
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}
///
$error = '';
$success = '';

// Success message if redirected after registration
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success = 'Registration successful! Please login with your credentials.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $user_type = trim($_POST['user_type']);

    // --- VALIDATIONS ---
    if (empty($user_type) || !in_array($user_type, ['student','teacher'])) {
        $error = "Please select a valid user type.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Attempt login
        $result = loginUser($email, $password, $user_type);
        if ($result === true) {
            // Redirect based on role
            if ($user_type === 'student') {
                header("Location: student_dashboard/student_dashboard.php");
            } elseif ($user_type === 'teacher') {
                header("Location: teacher_dashboard/teacher_dashboard.php");
            } else {
                header("Location: index.php");
            }
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
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login - EduMate</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #1e293b 0%, #334155 40%, #f1f5f9 100%);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      margin: 0;
    }
    .auth-container {
      width: 100%;
      max-width: 380px;
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(15px);
      padding: 2rem;
      border-radius: 18px;
      box-shadow: 0 12px 40px rgba(0,0,0,0.2);
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
    .form-group { margin-bottom: 1.2rem; }
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
      transition: transform 0.2s ease, background 0.3s ease;
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
      text-decoration: none;
      font-weight: 600;
    }
    .login-link a:hover { color: #1d4ed8; }
  </style>
</head>
<body>
  <div class="auth-container">
    <h2>Login</h2>

    <?php if ($error): ?>
      <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" id="loginForm">
      <div class="form-group">
        <label for="user_type">Login as</label>
        <select name="user_type" id="user_type" required>
          <option value="">Select role</option>
          <option value="student" <?php echo (isset($_POST['user_type']) && $_POST['user_type']=='student')?'selected':''; ?>>Student</option>
          <option value="teacher" <?php echo (isset($_POST['user_type']) && $_POST['user_type']=='teacher')?'selected':''; ?>>Teacher</option>
        </select>
      </div>

      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email"
          value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
          required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" minlength="6" required>
      </div>

      <button type="submit" class="btn">Login</button>
    </form>

    <div class="login-link">
      Don’t have an account? <a href="register.php">Register here</a>
    </div>
  </div>

  <script>
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      const email = document.getElementById('email').value.trim();
      const password = document.getElementById('password').value.trim();
      const userType = document.getElementById('user_type').value;
      let errors = [];

      if (!userType) errors.push("Please select a user type.");
      if (!/^[^@]+@[^@]+\.[^@]+$/.test(email)) errors.push("Invalid email format.");
      if (password.length < 6) errors.push("Password must be at least 6 characters.");

      if (errors.length > 0) {
        e.preventDefault();
        alert(errors.join("\n"));
      } else {
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Logging in...';
      }
    });
  </script>
</body>
</html>
