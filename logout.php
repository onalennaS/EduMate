<?php
require_once 'auth.php';

// Logout user
logoutUser();

// Redirect to home page
header("Location: index.php?logout=1");
exit();
?>