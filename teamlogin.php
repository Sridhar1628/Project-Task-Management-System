<?php
session_start();
require_once "config.php"; // Include the config file

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $conn = getMySQLConnection(); // Use function from config.php

    $teamUserId = $_POST['team_user_id'];
    $teamPassword = $_POST['password'];

    // Prepare SQL
    $sql = "SELECT team_id, team_name, team_user_id, team_password FROM teams WHERE team_user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $teamUserId);
    $stmt->execute();
    $stmt->bind_result($teamId, $teamName, $dbTeamUserId, $teamPasswordHash);

    if ($stmt->fetch()) {
        if (password_verify($teamPassword, $teamPasswordHash)) {
            $_SESSION['team_id'] = $teamId;
            $_SESSION['team_name'] = $teamName;
            echo "<script>alert('Login Successful!'); window.location.href='teamdashboard.php';</script>";
            exit;
        } else {
            echo "<script>alert('Invalid password. Please try again.'); window.location.href='teamlogin.php';</script>";
        }
    } else {
        echo "<script>alert('Team not found. Please try again.'); window.location.href='teamlogin.php';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Login</title>
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
        <h2>Team Login</h2>
        <form action="" method="POST">
            <label for="team_user_id">Team User ID:</label>
            <input type="text" id="team_user_id" name="team_user_id" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Login</button><br><br>
            <a href="home.html"><button type="button" style="background-color:red;">Cancel</button></a>
            <p>Don't have an account? <a href="tereg.php">Register here</a></p>
        </form>
    </div>
</body>
</html>
