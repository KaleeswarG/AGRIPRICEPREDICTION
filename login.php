<?php
session_start();
include 'db.php'; // This should connect to your MySQL database

// Get and trim POST data
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// === Validation ===
if (empty($username)) {
    echo "Error: Username is required.";
    exit();
}

if (empty($password)) {
    echo "Error: Password is required.";
    exit();
}

// === Query to check user ===
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    // Verify the hashed password
    if (password_verify($password, $user['password'])) {
        $_SESSION['username'] = $username;
        echo "success";
    } else {
        echo "Error: Invalid password.";
    }
} else {
    echo "Error: User not found.";
}
?>