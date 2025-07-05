<?php
session_start();

if (isset($_SESSION['user_id'])) {
    // User is logged in — redirect to dashboard
    header('Location: public/dashboard.php');
    exit;
} else {
    // Not logged in — redirect to login page
    header('Location: public/login.php');
    exit;
}
