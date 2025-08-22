<?php
session_start();
require 'config.php'; // Your DB connection file

// Ensure leader is logged in
if (!isset($_SESSION['leader_id']) || !isset($_SESSION['team_id'])) {
    header("Location: leader_login.php");
    exit();
}

$leader_id = $_SESSION['leader_id'];
$team_id   = $_SESSION['team_id'];
$team_name = $_SESSION['team_name'];

$message = "";

// ✅ Handle Add Member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    $member_name  = trim($_POST['member_name']);
    $member_email = trim($_POST['member_email']);
    $member_phone = trim($_POST['member_phone']);
    $member_password = password_hash($_POST['member_password'], PASSWORD_DEFAULT);

    if (!empty($member_name) && !empty($member_email) && !empty($member_phone) && !empty($_POST['member_password'])) {
        // Check if email already exists
        $check = $conn->prepare("SELECT * FROM team_members WHERE member_email = ?");
        $check->bind_param("s", $member_email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            echo "<script>alert('❌ Member with this email already exists.'); </script>";
            echo "<script>window.location.href = 'add_team_members.php';</script>";
        } else {
            $insert = $conn->prepare("INSERT INTO team_members (team_id, team_name, member_name, member_email, member_phone, member_password) VALUES (?, ?, ?, ?, ?, ?)");
            $insert->bind_param("isssss", $team_id, $team_name, $member_name, $member_email, $member_phone, $member_password);
            if ($insert->execute()) {
                echo "<script>alert('✅ Member added successfully'); </script>";
                echo "<script>window.location.href = 'add_team_members.php';</script>";
            } else {
                echo "<script>alert('❌ Failed to add member.'); </script>";
                echo "<script>window.location.href = 'add_team_members.php';</script>";
            }
        }
    } else {
        echo "<script>alert('❌ All fields are required.'); </script>";
        echo "<script>window.location.href = 'add_team_members.php';</script>";
    }
}

// ✅ Handle Remove Member
if (isset($_GET['remove_id'])) {
    $remove_id = intval($_GET['remove_id']);

    // Ensure member belongs to leader's team
    $check = $conn->prepare("SELECT * FROM team_members WHERE member_id = ? AND team_id = ?");
    $check->bind_param("ii", $remove_id, $team_id);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        $delete = $conn->prepare("DELETE FROM team_members WHERE member_id = ?");
        $delete->bind_param("i", $remove_id);
        if ($delete->execute()) {
            echo "<script>alert('✅ Member removed successfully.'); window.location.href= 'add_team_members.php';</script>";
        } else {
            echo "<script>alert('❌ Failed to remove member.'); window.location.href= 'add_team_members.php';</script>";    
        }
    } else {
        echo "<script>alert('❌ Member not found or does not belong to your team.'); window.location.href= 'add_team_members.php';</script>";
    }
}

// ✅ Fetch Team Members
$members = $conn->prepare("SELECT * FROM team_members WHERE team_id = ?");
$members->bind_param("i", $team_id);
$members->execute();
$member_list = $members->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Team Members</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Segoe UI", Arial, sans-serif;
}

body {
    background: #f4f7fa;
    display: flex;
}

/* Sidebar */
.sidebar {
    width: 280px;  /* Increased width */
    background: linear-gradient(180deg, #2c3e50, #000);
    color: #fff;
    padding: 25px 0;
    position: fixed;
    height: 100%;
    box-shadow: 2px 0 8px rgba(0,0,0,0.25);
}

.sidebar .logo {
    text-align: center;
    font-size: 28px;
    font-weight: bold;
    padding: 20px;
    margin-bottom: 35px;
    border-bottom: 1px solid rgba(255,255,255,0.25);
    color: #ff7f50;
}


.sidebar ul {
    list-style: none;
    padding: 0;
}

.sidebar ul li {
    margin: 14px 0;
}

.sidebar ul li a {
    display: flex;
    align-items: center;
    font-size: 18px;
    color: #ddd;
    padding: 15px 22px;
    text-decoration: none;
    transition: 0.3s;
    border-left: 4px solid transparent;
}

.sidebar ul li a i {
    margin-right: 12px;
    font-size: 20px;
}

.sidebar ul li a:hover {
    background: rgba(255, 127, 80, 0.2);
    border-left: 4px solid #ff7f50;
    color: #fff;
}

/* Container */
.container {
    margin-left: 280px; /* Push content right of sidebar */
    padding: 30px;
    width: calc(100% - 280px);
}

h2 {
    font-size: 26px;
    margin-bottom: 10px;
    color: #2c3e50;
}

h3 {
    margin: 20px 0 10px;
    color: #34495e;
}

p {
    margin-bottom: 15px;
    color: #555;
}

/* Table */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0px 4px 8px rgba(0,0,0,0.05);
}

table th, table td {
    padding: 12px 15px;
    text-align: center;
    border-bottom: 1px solid #ddd;
}

table th {
    background: #34495e;
    color: white;
    font-weight: 600;
}

table tr:hover {
    background: #f1f1f1;
}

/* Form Box */
.form-box {
    margin-top: 30px;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0px 4px 8px rgba(0,0,0,0.05);
}

.form-box h3 {
    margin-bottom: 15px;
}

.form-box input {
    width: 100%;
    padding: 10px;
    margin: 8px 0;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 15px;
}

.form-box button {
    margin-top: 10px;
    width: 100%;
    padding: 10px;
    background: #27ae60;
    border: none;
    color: white;
    font-size: 16px;
    border-radius: 6px;
    cursor: pointer;
    transition: 0.3s;
}

.form-box button:hover {
    background: #219150;
}

/* Remove button */
.remove-btn {
    background: #e74c3c;
    border: none;
    padding: 8px 14px;
    color: white;
    font-size: 14px;
    border-radius: 5px;
    cursor: pointer;
    transition: 0.3s;
}

.remove-btn:hover {
    background: #c0392b;
}

    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">Leader<br>Dashboard</div>
                <ul>
                    <li><a href="leader_dashboard.php"><i class="fas fa-tachometer-alt"></i> Home</a></li>
                    <li><a href="add_team_members.php"><i class="fas fa-users"></i>Add Team Members</a></li>
                    <li><a href="lead_view_proofs.php"><i class="fas fa-file-upload"></i> View Proof</a></li>
                    <li><a href="completed_tasks.php"><i class="fas fa-check-circle"></i> Completed Tasks</a></li>
                    <li><a href="teamdashboard.php"><i class="fas fa-home"></i> Team Dashboard</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
        </div>
    </div>

    <div class="container">
        <h2>👨‍💼 Team Leader Dashboard</h2>
        <p><b>Team:</b> <?= htmlspecialchars($team_name) ?> | <b>Leader ID:</b> <?= htmlspecialchars($leader_id) ?></p>

        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>

        <h3>📋 Team Members</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Action</th>
            </tr>
            <?php while ($row = $member_list->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['member_id'] ?></td>
                    <td><?= htmlspecialchars($row['member_name']) ?></td>
                    <td><?= htmlspecialchars($row['member_email']) ?></td>
                    <td><?= htmlspecialchars($row['member_phone']) ?></td>
                    <td>
                        <a href="?remove_id=<?= $row['member_id'] ?>" onclick="return confirm('Do you want to remove this member?')">
                            <button class="remove-btn">Remove</button>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>

        <div class="form-box">
            <h3>➕ Add New Member</h3>
            <form method="POST">
                <input type="text" name="member_name" placeholder="Member Name" required>
                <input type="email" name="member_email" placeholder="Member Email" required>
                <input type="text" name="member_phone" placeholder="Member Phone" required>
                <input type="password" name="member_password" placeholder="Member Password" required>
                <button type="submit" name="add_member">Add Member</button>
            </form>
        </div>
    </div>
</body>
</html>
