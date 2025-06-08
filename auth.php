<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define constants for role IDs
define('ROLE_ADMIN', 1); 
define('ROLE_STAFF', 2); // Adding staff role constant

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if the current page is the login page
function isLoginPage() {
    $current_page = basename($_SERVER['PHP_SELF']);
    return ($current_page === 'index.php' || $current_page === 'signup.php');
}

// Function to check if the user is an admin
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role_id']) && $_SESSION['role_id'] == ROLE_ADMIN;
}

// Function to check if the user is staff
function isStaff() {
    return isLoggedIn() && isset($_SESSION['role_id']) && $_SESSION['role_id'] == ROLE_STAFF;
}

// Function to redirect to login page if not logged in
function checkLogin() {
    if (!isLoggedIn() && !isLoginPage()) {
        header("Location: index.php");
        exit();
    }
}

// Function to check if user is admin, redirect if not
function checkAdmin() {
    // First check if logged in
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit();
    }
    
    // Then check if admin
    if (!isAdmin()) {
        // Display access denied message with a link back to the dashboard
        echo '<div class="container">';
        echo '<div class="error-message">Access Denied: You need administrator privileges to access this page.</div>';
        echo '<a href="dashboard.php" class="btn-back">Back to Dashboard</a>';
        echo '</div>';
        exit();
    }
}

// Function to check if user is either admin or staff
function checkStaffOrAdmin() {
    // First check if logged in
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit();
    }
    
    // Then check if admin or staff
    if (!isAdmin() && !isStaff()) {
        // Display access denied message
        echo '<div class="container">';
        echo '<div class="error-message">Access Denied: You need staff or administrator privileges to access this page.</div>';
        echo '<a href="dashboard.php" class="btn-back">Back to Dashboard</a>';
        echo '</div>';
        exit();
    }
}

// Only run automatic login check if this file is included in a non-login page
if (!isLoginPage()) {
    checkLogin();
}
?>