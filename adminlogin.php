<?php
session_start();
require_once 'config.php';

$conn = getMySQLConnection();

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminUsername = $_POST['username'];
    $adminPassword = $_POST['password'];

    // Query to check if the admin exists
    $sql = "SELECT * FROM admins WHERE admin_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $adminUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        // Verify password
        if (password_verify($adminPassword, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['admin_id'];
             echo "<script>alert('Login Successful !'); window.location.href='admindashboard.php';</script>";
            exit;
        } else {
            echo "<script>alert('Invalid password. Please try again.'); window.location.href='adminlogin.php';</script>";
        }
    } else {
        echo "<script>alert('Admin not found. Please try again.'); window.location.href='adminlogin.php';</script>";
    }

    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background: linear-gradient(135deg, #6a11cb, #2575fc);
        display: flex;
        justify-content: center;
        align-items: center;
        height : 100vh;
        margin: 0;
        color: white;
    }

    .login-container {
        background: white;
        color: black;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        width : 300px;
    }

    .login-container h2 {
        text-align: center;
        margin-bottom : 20px;
        color: #2575fc;
    }

    .login-container label {
        display: block;
        margin-bottom : 5px;
        font-weight: bold;
    }

    .login-container input {
        width : 90%;
        padding: 10px;
        margin-bottom : 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }

    .login-container button {
        width : 100%;
        padding: 10px;
        background: #2575fc;
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
    }

    .login-container button:hover {
        background: #6a11cb;
    }
     .login-container p {
            text-align: center;
            margin-top : 10px;
     }
     .login-container a {
            color: #2575fc;
            text-decoration: none;
            font-weight: bold;
        }
        .login-container a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <form method="POST">
            <label for="adminUsername">Admin User ID:</label>
            <input type="text" id="adminUsername" name="username" required>
            <label for="adminPassword">Password:</label>
            <input type="password" id="adminPassword" name="password" required>
            <button type="submit">Login</button><br><br>
            <a href="home.html"><button type="button" style="background-color:red;">Cancel</button></a>
            <p>Doesn't have an account? <a href="adreg.php">Register here</a></p>
        </form>
    </div>
</body>
</html>
