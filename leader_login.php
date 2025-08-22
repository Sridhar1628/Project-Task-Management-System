<?php
session_start();
include 'config.php';  // Include database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $leader_user_id = mysqli_real_escape_string($conn, $_POST['leader_user_id']);
    $leader_password = $_POST['leader_password'];

    $query = "SELECT * FROM team_leaders WHERE leader_user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $leader_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (password_verify($leader_password, $row['leader_password'])) {
            $_SESSION['leader_id'] = $row['leader_id'];
            $_SESSION['team_id'] = $row['team_id'];
            $_SESSION['leader_user_id'] = $row['leader_user_id'];
            $_SESSION['leader_name'] = $row['leader_name'];

            echo "<script>alert('Login successful!'); window.location.href='leader_dashboard.php';</script>";
            $stmt->close();
            exit();
        } else {
            echo "<script>alert('Invalid password. Please try again.');</script>";
            echo "<script>window.location.href='leader_login.php';</script>";
            $stmt->close(); 
        }
    } else {
        echo "<script>alert('User ID not found. Please register first.');</script>";
        echo "<script>window.location.href='tereg.php';</script>";    
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leader Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #2c3e50, #3498db);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background: #fff;
            padding: 25px 35px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 350px;
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
            color: #2c3e50;
        }
        label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
            transition: 0.3s;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            border-color: #3498db;
            outline: none;
        }
        button {
            width: 100%;
            padding: 10px;
            background: #3498db;
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: #2980b9;
        }
        .error {
            color: red;
            margin-top: 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Leader Login</h2>
        <form method="POST">
            <label for="leader_user_id">User ID:</label>
            <input type="text" name="leader_user_id" required>
            
            <label for="leader_password">Password:</label>
            <input type="password" name="leader_password" required>

            <button type="submit">Login</button>
        </form>

        <?php if (isset($error_message)): ?>
            <p class="error"><?= $error_message ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
