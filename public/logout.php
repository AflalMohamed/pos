<?php
// public/logout.php
session_start();

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to login page (adjust path if needed)
header('Location: ../public/login.php');
exit;
