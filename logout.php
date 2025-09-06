<?php
require_once 'includes/auth.php';

// Logout user
logoutUser();

// Redirect to login page with logout confirmation
header("Location: login.php?logout=1");
exit();
?>