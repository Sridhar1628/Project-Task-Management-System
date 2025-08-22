<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = getMySQLConnection();

        // Retrieve form data
        $name = isset($_POST['name']) ? trim($_POST['name']) : null;
        $admin_Id = isset($_POST['admin_id']) ? trim($_POST['admin_id']) : null;
        $email = isset($_POST['email']) ? trim($_POST['email']) : null;
        $mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : null;
        $password = isset($_POST['password']) ? trim($_POST['password']) : null;
        $admin_Password = isset($_POST['admin_password']) ? trim($_POST['admin_password']) : null;

        // Validate all input fields
        if (empty($name) || empty($admin_Id) || empty($email) || empty($mobile) || empty($password) || empty($admin_Password)) {
            die("<script>alert('Error: All fields are required.'); window.history.back();</script>");
        }

        // Validate admin secret password
        if ($admin_Password !== ADMIN_SECRET) {
            die("<script>alert('Error: Invalid admin secret password.'); window.history.back();</script>");
        }

        // Encrypt the admin's password
        $encrypted_Password = password_hash($password, PASSWORD_BCRYPT);

        // Prepare the SQL query
        $sql = "INSERT INTO admins (admin_id, name, email, mobile, password) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Error preparing SQL statement: " . $conn->error);
        }

        // Bind parameters and execute
        $stmt->bind_param("sssss", $admin_Id, $name, $email, $mobile, $encrypted_Password);

        if ($stmt->execute()) {
            echo "<script>alert('Admin registered successfully!');window.location.href='adminlogin.php';</script>";
        } else {
            die("Error executing SQL query: " . $stmt->error);
        }

        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        echo "An error occurred: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: purple;
            display: flex;
            justify-content: center;
            align-items: center;
            block-size: 100vh;
            margin: 0;
            color: white;
        }
        .login-container {
            background: white;
            color: black;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            width : 500px;
        }
        .login-container h2 {
            text-align: center;
            margin-block-end: 20px;
            color: #2575fc;
        }
        .login-container label {
            display: block;
            margin-block-end: 5px;
            font-weight: bold;
        }
        .login-container input {
            inline-size: 100%;
            padding: 10px;
            margin-block-end: 15px;
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
        #can {
            background-color: red;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Registration</h2>
        <form method="POST">
            <label for="name">Admin Name</label>
            <input type="text" id="name" name="name" placeholder="Enter admin name" required>

            <label for="mobile">Mobile Number</label>
            <input type="text" id="mobile" name="mobile" placeholder="Enter mobile number" pattern="[0-9]{10}" title="Enter a valid 10-digit mobile number" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Enter email" required>

            <label for="admin_id">Admin User ID</label>
            <input type="text" id="admin_id" name="admin_id" placeholder="Enter admin user ID" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter password" pattern="^(?=.*[A-Z])(?=.*[@$!%*?&])(?=.*[0-9]).{8,}$" title="Password must contain at least one uppercase letter, one special character, one number, and be at least 8 characters long" required>

            <label for="admin_password">Admin Secret Password</label>
            <input type="password" id="admin_password" name="admin_password" placeholder="Confirm password" required>

            <button type="submit">Register</button><br><br>
            <a href="home.html"><button type="button" id="can">Cancel</button></a>
        </form>
        <p>Already have an account? <a href="adminlogin.php">Login here</a></p>
    </div>
</body>
</html>
