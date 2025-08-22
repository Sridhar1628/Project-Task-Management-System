<?php
session_start();
require 'config.php';


if (!isset($_SESSION['team_id']) && !isset($_SESSION['team_name'])) {
    echo "<script>alert('You need to log in first.'); window.location.href='teamlogin.html';</script>";
    exit;
}

$teamId = $_SESSION['team_id'] ?? null;
$teamName = $_SESSION['team_name'] ?? null;

// Fetch team leader details
$sql_leader = "SELECT leader_name, leader_email, leader_phone FROM team_leaders WHERE team_id = ? OR team_name = ?";
$stmt_leader = $conn->prepare($sql_leader);
$stmt_leader->bind_param("is", $teamId, $teamName);
$stmt_leader->execute();
$stmt_leader->bind_result($leaderName, $leaderEmail, $leaderPhone);
$leaderInfo = [];
if ($stmt_leader->fetch()) {
    $leaderInfo = ['name' => $leaderName, 'email' => $leaderEmail, 'phone' => $leaderPhone];
}
$stmt_leader->close();

$leader_id = $_SESSION['leader_id'];
$team_id   = $_SESSION['team_id'];

// Handle member removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['member_id'], $_POST['leader_password'])) {
    $leader_id = $_SESSION['leader_id'];
    $team_id = $_SESSION['team_id'];
    $member_id = intval($_POST['member_id']);
    $leader_password = $_POST['leader_password'];

    // ✅ Verify leader password
    $stmt = $conn->prepare("SELECT leader_password FROM team_leaders WHERE leader_id = ? AND team_id = ?");
    $stmt->bind_param("ii", $leader_id, $team_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();

    if ($hashed_password && password_verify($leader_password, $hashed_password)) {
        // ✅ Delete member by member_id + team_id
        $delete = $conn->prepare("DELETE FROM team_members WHERE member_id = ? AND team_id = ?");
        $delete->bind_param("ii", $member_id, $team_id);

        if ($delete->execute() && $delete->affected_rows > 0) {
            echo "<script>alert('Member removed successfully.'); window.location.href=window.location.href;</script>";
        } else {
            echo "<script>alert('Failed to remove member. Check if the member exists under this team.'); window.location.href=window.location.href;</script>";
        }
        $delete->close();
    } else {
        echo "<script>alert('Invalid Leader Password.'); window.location.href=window.location.href;</script>";
    }
}
// Fetch team members
// Fetch team members (now also include member_id)
$sql_members = "SELECT member_id, member_name, member_email, member_phone 
                FROM team_members 
                WHERE team_id = ? OR team_name = ?";
$stmt_members = $conn->prepare($sql_members);
$stmt_members->bind_param("is", $teamId, $teamName);
$stmt_members->execute();
$stmt_members->bind_result($memberId, $memberName, $memberEmail, $memberPhone);
$teamMembers = [];
while ($stmt_members->fetch()) {
    $teamMembers[] = [
        'id' => $memberId,
        'name' => $memberName,
        'email' => $memberEmail,
        'phone' => $memberPhone
    ];
}
$stmt_members->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
body {
    font-family: Arial, sans-serif;
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
/* Container */
.container {
    margin-left: 280px; /* same as sidebar width */
    padding: 20px;
    width: calc(100% - 280px);
}

/* Heading */
.container h1 {
    margin-bottom: 20px;
    font-size: 26px;
    color: #1e293b;
}

/* Leader Bar */
.leader-bar {
    background: linear-gradient(90deg, #17243bff, #2563eb);
    color: #fff;
    padding: 12px 20px;
    border-radius: 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}
.leader-bar span {
    font-size: 16px;
}

/* Team Members Table */
.team-members h2 {
    margin-bottom: 10px;
    color: #133a4bff;
}
.team-members table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 25px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
.team-members th, .team-members td {
    padding: 12px 15px;
    text-align: left;
}
.team-members th {
    background: #11b6f7ff;
    color: #fff;
}
.team-members tr:nth-child(even) {
    background: #f1f5f9;
}
.remove-btn {
    background: #ef4444;
    border: none;
    padding: 8px 12px;
    color: white;
    border-radius: 4px;
    cursor: pointer;
}
.remove-btn:hover {
    background: #dc2626;
}
.team-members input[type="password"] {
    padding: 6px;
    border: 1px solid #ccc;
    border-radius: 4px;
    margin-right: 8px;
}

/* Add Member Form */
.add-member {
    background: #fff;
    padding: 20px;
    border-radius: 6px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
.add-member h2 {
    margin-bottom: 15px;
    color: #1e293b;
}
.add-member input {
    width: 100%;
    padding: 10px;
    margin-bottom: 12px;
    border: 1px solid #ccc;
    border-radius: 5px;
}
.add-btn {
    background: #10b981;
    color: white;
    border: none;
    padding: 12px 18px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 15px;
}
.add-btn:hover {
    background: #059669;
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
<div class="container">
    <h1>Team Dashboard</h1>

    <!-- Leader info in one line -->
    <div class="leader-bar">
        <?php if (!empty($leaderInfo)): ?>
            <span><strong>Leader Name:</strong> <?= htmlspecialchars($leaderInfo['name']) ?></span>
            <span><strong>Email:</strong> <?= htmlspecialchars($leaderInfo['email']) ?></span>
            <span><strong>Phone:</strong> <?= htmlspecialchars($leaderInfo['phone']) ?></span>
        <?php else: ?>
            <span>No leader information found.</span>
        <?php endif; ?>
    </div>

    <!-- Team Members Table -->
    <!-- Team Members Table -->
<div class="team-members">
    <h2>Team Members</h2>
    <table>
        <tr><th>Name</th><th>Email</th><th>Phone</th><th>Action</th></tr>
        <?php foreach ($teamMembers as $member): ?>
            <tr>
                <td><?= htmlspecialchars($member['name']) ?></td>
                <td><?= htmlspecialchars($member['email']) ?></td>
                <td><?= htmlspecialchars($member['phone']) ?></td>
                <td>
                    <!-- Remove Button -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="member_id" value="<?= htmlspecialchars($member['id']) ?>">
                        <input type="password" name="leader_password" placeholder="Leader Password" required>
                        <button type="submit" class="remove-btn" onclick="return confirm('Do You Want To Remove This Member ?');">Remove</button>
                    </form>

                    
                    <!-- Edit Button -->
                    <form action="edit_member.php" method="GET" style="display:inline;">
                        <input type="hidden" name="member_id" value="<?= htmlspecialchars($member['id']) ?>">
                        <button type="submit" class="remove-btn" style="background:#2563eb;">Edit</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

</div>
</body>
</html>
