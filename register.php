<?php
session_start();
require_once 'config/database.php';

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'student';

    // Validate
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    } elseif (!in_array($user_type, ['student','teacher','admin'])) {
        $errors[] = "Invalid user type.";
    }

    if (empty($errors)) {
        // Check for existing username/email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = "Username or email already taken.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, user_type, created_at) VALUES (?, ?, ?, ?, NOW())");
            if ($stmt->execute([$username, $email, $hashedPassword, $user_type])) {
                $success_message = "Registration successful! You can now <a href='login.php'>login</a>.";
            } else {
                $errors[] = "Failed to register. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - EduMate</title>
    <style>
        body { margin:0; font-family: Arial, sans-serif; background:#f1f5f9; display:flex; align-items:center; justify-content:center; height:100vh; }
        .card { background:#fff; width:400px; padding:2rem; border-radius:10px; box-shadow:0 4px 15px rgba(0,0,0,0.08); }
        h2 { color:#1d4ed8; text-align:center; margin:0 0 1rem; }
        .alert { padding:.75rem; border-radius:6px; margin-bottom:1rem; font-size:.95rem; }
        .alert-danger { background:#fee2e2; color:#991b1b; }
        .alert-success { background:#dcfce7; color:#065f46; }
        label { display:block; font-size:.9rem; margin-bottom:.25rem; color:#374151; }
        input, select { width:100%; padding:.6rem; border:1px solid #cbd5e1; border-radius:6px; font-size:.95rem; margin-bottom:.8rem; }
        input:focus, select:focus { outline:none; border-color:#2563eb; box-shadow:0 0 6px rgba(37,99,235,0.2); }
        button { width:100%; padding:.7rem; background:#2563eb; color:#fff; border:none; border-radius:6px; font-weight:700; cursor:pointer; }
        .footer { text-align:center; margin-top:.8rem; font-size:.9rem; color:#374151; }
        .footer a { color:#2563eb; text-decoration:none; font-weight:600; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Register</h2>

        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $err): ?><div><?= htmlspecialchars($err) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <label>Username</label>
            <input type="text" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

            <label>Email</label>
            <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

            <label>Password</label>
            <input type="password" name="password" required>

            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required>

            <label>Register As</label>
            <select name="user_type" required>
                <option value="student" <?= ($_POST['user_type'] ?? '') === 'student' ? 'selected' : '' ?>>Student</option>
                <option value="teacher" <?= ($_POST['user_type'] ?? '') === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                <option value="admin" <?= ($_POST['user_type'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>

            <button type="submit">Register</button>
        </form>

        <div class="footer">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</body>
</html>
