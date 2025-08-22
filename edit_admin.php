<?php
require 'config.php';
session_start();

$id = $_GET['id'];
$query = "SELECT * FROM admins WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];

    $stmt = $conn->prepare("UPDATE admins SET name=?, email=?, mobile=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $email, $mobile, $id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Admin updated successfully!');window.location.href='manage_admins.php';</script>";
    } else {
        echo "<script>alert('Update Failed!');window.location.href='manage_admins.php';</script>";
    }
}
?>
<html>
    <head>
        <title>Edit Admin</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <style>
            /* General Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .sidebar {
            width: 260px;
            background-color: #34495e;
            color: white;
            display: flex;
            flex-direction: column;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            top: 0;
            left: 0;
            overflow-y: auto;
        }

        .sidebar .logo {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 30px;
            color: #ecf0f1;
            text-align: center;
            letter-spacing: 1px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            transition: background 0.3s ease-in-out, padding-left 0.3s ease;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            padding: 12px 20px;
        }

        .sidebar ul li a i {
            margin-right: 12px;
            font-size: 1.2rem;
        }

        .sidebar ul li:hover {
            background-color: #1abc9c;
        }

        .sidebar ul li:hover a {
            padding-left: 25px;
        }

/* Form Container */
        form {
            background: white;
            padding: 20px;
            width: 350px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            text-align: center;
        }

        /* Labels */
        label {
            display: block;
            font-weight: bold;
            margin: 10px 0 5px;
            text-align: left;
        }

        /* Input Fields */
        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        /* Submit Button */
        button {
            background-color: #007BFF;
            color: white;
            font-size: 16px;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .card {
                width: 48%; /* Two cards per row */
            }
        }

        @media (max-width: 768px) {
            .card {
                width: 100%; /* One card per row */
            }

            main {
                padding: 20px;
            }
        }
        </style>
    </head>
    <body>
        <div class="sidebar">
            <div class="logo">Admin Dashboard</div>
               <ul>
                <li><a href="admindashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_admins.php"><i class="fas fa-user-shield"></i> Manage Admins</a></li>
                <li><a href="manage_teams.php"><i class="fas fa-users"></i> Manage Teams</a></li>
                <li><a href="manage_projects.php"><i class="fas fa-project-diagram"></i> Manage Projects</a></li>
                <li><a href="assign_projects.php"><i class="fas fa-tasks"></i> Assign Projects</a></li>
                <li><a href="manage_status.php"><i class="fas fa-clipboard-list"></i>  Manage Status</a></li>
                <li><a href="manage_reports.php"><i class="fas fa-clipboard-list"></i>  Manage Reports</a></li>
                <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
              </ul>
            </div>
        </div>

        <form method="POST">
            <label>Name:</label>
            <input type="text" name="name" value="<?= htmlspecialchars($admin['name']); ?>" required>
            
            <label>Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($admin['email']); ?>" required>
            
            <label>Mobile:</label>
            <input type="text" name="mobile" value="<?= htmlspecialchars($admin['mobile']); ?>"  required>
            
            <button type="submit">Update</button><br><br>
            <a href="manage_admins.php"><button type="button" style="background-color:red">Cancel</button></a>
        </form>
    </body>
</html>