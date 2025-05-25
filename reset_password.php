<?php
include 'db.php';

$email = trim($_POST['email'] ?? '');
$new_password = trim($_POST['new_password'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f7f7f7;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .alert {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .btn {
            text-decoration: none;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border-radius: 5px;
            font-size: 16px;
            margin-top: 10px;
            display: inline-block;
        }
        .btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php
        if (empty($email) || empty($new_password)) {
            echo "<div class='alert alert-danger'>⚠️ Error: All fields are required.</div>";
            echo "<a href='index.html' class='btn'>🔙 Go to Login</a>";
            exit();
        }

        // Check if user exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update password
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update_stmt->bind_param("ss", $hashed_password, $email);

            if ($update_stmt->execute()) {
                echo "<div class='alert alert-success'>✅ Password reset successfully! Redirecting to login...</div>";
                echo "<a href='index.html' class='btn'>🔐 Go to Login</a>";
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'index.html';
                    }, 3000);
                </script>";
            } else {
                echo "<div class='alert alert-danger'>❌ Error: Failed to update password.</div>";
                echo "<a href='index.html' class='btn'>🔙 Go to Login</a>";
            }
        } else {
            echo "<div class='alert alert-danger'>❌ Error: No account found with this email.</div>";
            echo "<a href='index.html' class='btn'>🔙 Go to Login</a>";
        }
        ?>
    </div>
</body>
</html>
