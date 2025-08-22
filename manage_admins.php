<?php
session_start();
require 'config.php'; // Include your database connection

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminlogin.php");
    exit();
}

// Fetch all admins
$query = "SELECT id, admin_id, name, email, mobile, created_at FROM admins";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
/* General Styles */
body {
    font-family: Arial, sans-serif;
    background-color: #f8f9fa;
    margin: 0;
    padding: 0;
    display: flex;
}

/* Sidebar Styling */
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

/* Main Content */
.main-content {
    margin-left: 250px;
    padding: 20px;
    width: calc(100% - 250px);
}

/* Heading */
h2 {
    background-color: #007BFF;
    color: white;
    padding: 15px;
    margin: 0;
    text-align: center;
    border-radius: 5px;
}

/* Table Styling */
table {
    width: 90%;
    margin: 20px auto;
    border-collapse: collapse;
    background: white;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
    border-radius: 8px;
    overflow: hidden;
}

th, td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: center;
}

th {
    background-color:rgb(249, 69, 4);
    color: white;
    font-weight: bold;
}

td {
    background-color: #f9f9f9;
}

/* Action Links */
a {
    text-decoration: none;
    font-weight: bold;
    padding: 5px 10px;
    border-radius: 5px;
}

a[href*="edit_admin.php"] {
    color: #ffc107;
    background-color: #212529;
    padding: 5px 10px;
}

a[href*="delete_admin.php"] {
    color: white;
    background-color: #dc3545;
    padding: 5px 10px;
}

a:hover {
    opacity: 0.8;
}

/* Add Admin Button */
#b1 {
    display: inline-block;
    background-color: #28a745;
    width:88%;
    color: white;
    font-size: 16px;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s ease;
    margin-top: 15px;
}

#b1:hover {
    background-color: #218838;
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
<div class="main-content">
    <h2>Admin Management</h2>
    <center><br><br><a href="add_admins.php"><button id="b1">Add Admin</button></a><br><br></center>
    <table border="1">
        <thead>
            <tr>
                <th>Admin ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Mobile</th>
                <th>Created At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($admin = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($admin['admin_id']); ?></td>
                    <td><?= htmlspecialchars($admin['name']); ?></td>
                    <td><?= htmlspecialchars($admin['email']); ?></td>
                    <td><?= htmlspecialchars($admin['mobile']); ?></td>
                    <td><?= htmlspecialchars($admin['created_at']); ?></td>
                    <td>
                        <a href="edit_admin.php?id=<?= $admin['id']; ?>">Edit</a> | 
                        <a href="delete_admin.php?id=<?= $admin['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
