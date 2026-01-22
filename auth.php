<?php
require_once 'config.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Get current user
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    global $conn;
    $user_id = $_SESSION['user_id'];
    $result = $conn->query("SELECT * FROM users WHERE id = $user_id");
    return $result->fetch_assoc();
}

// Login function
function login($username, $password) {
    global $conn;
    
    $username = $conn->real_escape_string($username);
    $result = $conn->query("SELECT * FROM users WHERE username = '$username'");
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
    }
    return false;
}

// Logout function
function logout() {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Register user
function registerUser($username, $email, $password, $role = 'student') {
    global $conn;
    
    $username = $conn->real_escape_string($username);
    $email = $conn->real_escape_string($email);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = $conn->real_escape_string($role);
    
    $sql = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$hashed_password', '$role')";
    
    if ($conn->query($sql) === TRUE) {
        return $conn->insert_id;
    }
    return false;
}

?>