<?php
session_start();
include 'db_connection.php';

$message = '';
$alertType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input and trim any unwanted spaces
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format!";
        $alertType = "error";
    } else {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $username, $hashed_password);
            $stmt->fetch();

            // Verify password
            if (password_verify($password, $hashed_password)) {
                // Store user session data
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username; 
                $_SESSION['email'] = $email;
                
                // Success message
                $message = "Login successful! Redirecting...";
                $alertType = "success";
            } else {
                // Invalid password
                $message = "Invalid email or password!";
                $alertType = "error";
            }
        } else {
            // User not found
            $message = "User not found!";
            $alertType = "error";
        }

        $stmt->close();
    }
    
    // Close the connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(to right, #6a11cb, #2575fc);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            max-width: 400px;
            width: 100%;
            padding: 40px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            background: #ffffff;
            z-index: 10;
        }

        h3 {
            font-size: 26px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-label {
            font-size: 14px;
            font-weight: 500;
        }

        .form-control {
            font-size: 16px;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        .btn {
            font-size: 16px;
            font-weight: 600;
            padding: 12px;
            background-color: #2575fc;
            border: none;
            border-radius: 8px;
            color: white;
            width: 100%;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #6a11cb;
        }

        .form-links {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }

        .form-links a {
            text-decoration: none;
            font-weight: 500;
            color: #2575fc;
            transition: color 0.3s ease-in-out;
        }

        .form-links a:hover {
            color: #6a11cb;
        }

        /* Loading Spinner */
        .overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(255, 255, 255, 0.7);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
            color: #2575fc;
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

    <div class="card">
        <h3>User Login</h3>
        <form method="POST" action="" onsubmit="showLoading()">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" placeholder="Enter your email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" placeholder="Enter your password" class="form-control" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>

        <div class="form-links">
            <a href="forgot_password.php" class="text-danger">Forgot Password?</a>
        </div>
        <div class="form-links">
            <a href="home.php" class="text-muted">Back to Home</a> | 
            <a href="register.php" class="text-success">Register</a>
        </div>
    </div>

    <script>
        // Show loading spinner
        function showLoading() {
            document.getElementById("loadingSpinner").style.display = "flex";
        }

        // SweetAlert2 Notifications
        <?php if (!empty($message)) : ?>
            Swal.fire({
                icon: '<?php echo $alertType; ?>',
                title: '<?php echo ucfirst($alertType); ?>',
                text: '<?php echo $message; ?>',
                confirmButtonText: 'Okay',
                confirmButtonColor: '#2575fc',
                willClose: () => {
                    document.getElementById("loadingSpinner").style.display = "none";
                }
            }).then((result) => {
                // Redirect on success
                if (<?php echo json_encode($alertType); ?> === 'success') {
                    setTimeout(() => {
                        window.location.href = "user_dashboard.php";
                    }, 2000);
                }
            });
        <?php endif; ?>
    </script>

</body>
</html>
