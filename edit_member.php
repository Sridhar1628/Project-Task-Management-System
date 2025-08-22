<?php
require 'config.php';
session_start();

if (!isset($_SESSION['team_id'])) {
    echo "<script>alert('Login required'); window.location.href='teamlogin.php';</script>";
    exit;
}

$teamId = $_SESSION['team_id'];
$memberId = $_GET['member_id'] ?? ($_POST['member_id'] ?? null);

if (!$memberId) {
    die("Invalid member ID");
}

/* ---------------------------
   PROCESS UPDATE FORM SUBMIT
---------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $memberName = $_POST['member_name'] ?? '';
    $memberEmail = $_POST['member_email'] ?? '';
    $memberPhone = $_POST['member_phone'] ?? '';
    $leaderPassword = $_POST['leader_password'] ?? '';

    // Verify leader password
    $stmt = $conn->prepare("SELECT leader_password FROM team_leaders WHERE team_id=?");
    $stmt->bind_param("i", $teamId);
    $stmt->execute();
    $stmt->bind_result($hashedPassword);
    $stmt->fetch();
    $stmt->close();

    if (!$hashedPassword || !password_verify($leaderPassword, $hashedPassword)) {
        echo "<script>alert('Invalid leader password'); window.history.back();</script>";
        exit;
    }

    // Update member info
    $stmt = $conn->prepare("UPDATE team_members SET member_name=?, member_email=?, member_phone=? WHERE member_id=? AND team_id=?");
    $stmt->bind_param("sssii", $memberName, $memberEmail, $memberPhone, $memberId, $teamId);

    if ($stmt->execute()) {
        echo "<script>alert('Member updated successfully'); window.location.href='teamdashboard.php';</script>";
    } else {
        echo "<script>alert('Error updating member'); window.history.back();</script>";
    }
    $stmt->close();
    $conn->close();
    exit;
}

/* ---------------------------
   FETCH MEMBER DETAILS
---------------------------- */
$stmt = $conn->prepare("SELECT member_name, member_email, member_phone FROM team_members WHERE member_id=? AND team_id=?");
$stmt->bind_param("ii", $memberId, $teamId);
$stmt->execute();
$stmt->bind_result($memberName, $memberEmail, $memberPhone);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Member</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Reset and base styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f6f9;
    color: #333;
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

/* Main content area */
.edit-member {
    margin-left: 280px; /* equal to sidebar width */
    flex: 1;
    padding: 50px;
}
.edit-member h2 {
    font-size: 24px;
    margin-bottom: 20px;
    color: #1e293b;
}

/* Form styling */
.edit-member form {
    background: #fff;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    max-width: 1000px;
    max-height: 800px;
}
.edit-member input {
    width: 100%;
    padding: 12px;
    margin: 10px 0;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 14px;
}
.edit-member input:focus {
    border-color: #2563eb;
    outline: none;
    box-shadow: 0 0 5px rgba(37,99,235,0.3);
}
.save-btn {
    width: 100%;
    background: #2563eb;
    color: #fff;
    border: none;
    padding: 12px;
    font-size: 15px;
    border-radius: 6px;
    cursor: pointer;
    margin-top: 10px;
    transition: 0.3s;
}
.save-btn:hover {
    background: #1e40af;
}
.can-btn {
    width: 100%;
    background: #ee0a0aff;
    color: #fff;
    border: none;
    padding: 12px;
    font-size: 15px;
    border-radius: 6px;
    cursor: pointer;
    margin-top: 10px;
    transition: 0.3s;
}
.can-btn:hover {
    background: #dc2626;
}

    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">Team <br>Dashboard</div>
                <ul>
                    <li><a href="teamdashboard.php"><i class="fas fa-tachometer-alt"></i> Home</a></li>
                    <li><a href="team_members.php"><i class="fas fa-users"></i> Team Members</a></li>
                    <li><a href="update_status.php"><i class="fas fa-edit"></i> Update Status</a></li>
                    <li><a href="submit_proof.php"><i class="fas fa-file-upload"></i> Submit Proof</a></li>
                    <li><a href="view_comment.php"><i class="fas fa-file-upload"></i> Discussion</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
        </div>
    </div>

    <div class="edit-member">
        <h2>Edit Member</h2>
        <form method="POST">
            <input type="hidden" name="member_id" value="<?= htmlspecialchars($memberId) ?>">
            <input type="text" name="member_name" value="<?= htmlspecialchars($memberName) ?>" required>
            <input type="email" name="member_email" value="<?= htmlspecialchars($memberEmail) ?>" required>
            <input type="text" name="member_phone" value="<?= htmlspecialchars($memberPhone) ?>" required>
            <input type="password" name="leader_password" placeholder="Leader Password" required>
            <button type="submit" class="save-btn">Save Changes</button>
            <button type="button" class="can-btn"  onclick="window.location.href='team_members.php'">Cancel</button>
        </form>
    </div>
</body>
</html>
