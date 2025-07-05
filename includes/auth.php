<?php
session_start();

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../public/login.php');
        exit;
    }
}

function checkRole($roles = []) {
    checkLogin();
    if (!in_array($_SESSION['role'], $roles)) {
        echo "Access denied. You do not have permission to view this page.";
        exit;
    }
}
