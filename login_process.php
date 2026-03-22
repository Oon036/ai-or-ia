<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Input validation
    if (empty($username) || empty($password)) {
        header("Location: index.php?error=empty");
        exit();
    }

    // Prepare statement to prevent SQL injection
    $stmt = $pdo->prepare("SELECT id, username, password, role, reference_id FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        
        // Verify hashed password
        if (password_verify($password, $user['password'])) {
            // Password is correct, start session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['reference_id'] = $user['reference_id'];
            
            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            // Invalid password
            header("Location: index.php?error=invalid_credentials");
            exit();
        }
    } else {
        // User not found
        header("Location: index.php?error=invalid_credentials");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>
