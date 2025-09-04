<?php
session_start();
include 'db_connection.php';

$message = '';
$alertType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

    // Validation for username, email, and password
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "All fields are required.";
        $alertType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format!";
        $alertType = "error";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $alertType = "error";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match!";
        $alertType = "error";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Username or Email already exists!";
            $alertType = "error";
        } else {
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            if ($stmt->execute()) {
                $message = "Registration successful!";
                $alertType = "success";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Registration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap & SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(120deg, #6a5acd, #8a2be2);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .card {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
        }

        .card h3 {
            text-align: center;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .form-label {
            font-size: 14px;
            font-weight: 500;
        }

        .form-control {
            font-size: 14px;
            border-radius: 8px;
            padding: 10px;
        }

        .btn-primary {
            background: #6a5acd;
            border: none;
            border-radius: 8px;
            width: 100%;
            padding: 12px;
            font-size: 16px;
            transition: background 0.3s ease;
        }

        .btn-primary:hover {
            background: #8a2be2;
        }

        .form-links {
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
        }

        .form-links a {
            color: #6a5acd;
            text-decoration: none;
            font-weight: 500;
        }

        .form-links a:hover {
            text-decoration: underline;
        }

        .overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: rgba(255, 255, 255, 0.7);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
            color: #6a5acd;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>

<!-- Loading Spinner -->
<div class="overlay" id="loadingSpinner">
    <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<div class="container">
    <div class="card">
        <h3>Create Your Account</h3>
        <form method="POST" action="" onsubmit="showLoading()">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Choose a username" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter email" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Create password" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
        <div class="form-links">
            <a href="home.php">← Back to Home</a> |
            <a href="login.php">Already registered? Login</a>
        </div>
    </div>
</div>

<script>
    // Show loading animation
    function showLoading() {
        document.getElementById("loadingSpinner").style.display = "flex";
    }

    // SweetAlert message
    <?php if (!empty($message)) : ?>
        Swal.fire({
            icon: '<?php echo $alertType; ?>',
            title: '<?php echo ucfirst($alertType); ?>',
            text: '<?php echo $message; ?>',
            confirmButtonColor: '#6a5acd',
            willClose: () => {
                document.getElementById("loadingSpinner").style.display = "none";
            }
        });
    <?php endif; ?>
</script>

</body>
</html>
