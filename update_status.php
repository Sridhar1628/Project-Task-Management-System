<?php
session_start();
include 'config.php'; // Include database connection

// Check if the team is logged in
if (!isset($_SESSION['team_id'])) {
    header("Location: teamlogin.php"); // Redirect if not logged in
    exit();
}

$team_id = $_SESSION['team_id']; // Get team ID from session

// Handle form submission (status update)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['project_id'], $_POST['status'])) {
    $project_id = intval($_POST['project_id']);
    $status = $_POST['status'];

    // Prevent changing if already Completed
    $checkQuery = "SELECT status FROM projects WHERE id = ?";
    $checkStmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($checkStmt, "i", $project_id);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $row = mysqli_fetch_assoc($checkResult);

    if ($row && $row['status'] !== 'Completed') {
        $project_name = $row['project_name'];

        $updateQuery = "UPDATE projects SET status = ? WHERE id = ?";
        $updateStmt = mysqli_prepare($conn, $updateQuery);
        mysqli_stmt_bind_param($updateStmt, "si", $status, $project_id);
        if (mysqli_stmt_execute($updateStmt)) {
            $message = "Project '$project_name' status changed to '$status'.";
            $insertQuery = "INSERT INTO notifications (project_id, team_id, message) VALUES (?, ?, ?)";
            $insertStmt = mysqli_prepare($conn, $insertQuery);
            mysqli_stmt_bind_param($insertStmt, "iis", $project_id, $team_id, $message);
            mysqli_stmt_execute($insertStmt);
            echo "<script>alert('Status updated successfully.');</script>";
        } else {
            echo "<script>alert('Error updating status.');</script>";
        }
    } else {
        echo "<script>alert('Cannot change status of completed projects.');</script>";
    }
    if ($row && $row['status'] !== 'Completed') {
        $updateQuery = "UPDATE assigned_projects SET status = ? WHERE id = ?";
        $updateStmt = mysqli_prepare($conn, $updateQuery);
        mysqli_stmt_bind_param($updateStmt, "si", $status, $project_id);
    }
    echo "<script>window.location.href='update_status.php';</script>"; // Redirect to avoid resubmission
    exit();
}

// Fetch projects for the team
$query = "SELECT p.id, p.project_name, p.description, p.deadline, p.status 
          FROM projects p
          JOIN assigned_projects ap ON p.id = ap.project_id
          WHERE ap.team_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $team_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Status - Team Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
    /* Sidebar */
    .sidebar {
        width: 280px;
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
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f4f4f4;
    }
    .container {
        margin-left: 270px;
        padding: 20px;
    }
    h2 {
        color: #333;
        text-align: center;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        margin-top: 20px;
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
    }
    th, td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    th {
        background-color: #007bff;
        color: white;
    }
    button {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 8px 12px;
        cursor: pointer;
        border-radius: 5px;
        transition: 0.3s;
    }
    button:hover {
        background-color: #0056b3;
    }
    select {
        padding: 5px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">Team<br> Dashboard</div>
    <ul>
        <li><a href="teamdashboard.php"><i class="fas fa-tachometer-alt"></i> Home</a></li>
        <li><a href="team_members.php"><i class="fas fa-users"></i> Team Members</a></li>
        <li><a href="update_status.php"><i class="fas fa-edit"></i> Update Status</a></li>
        <li><a href="submit_proof.php"><i class="fas fa-file-upload"></i> Submit Proof</a></li>
        <li><a href="view_comment.php"><i class="fas fa-file-upload"></i> Discussion</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="container">
    <h2>Update Project Status</h2>
    <?php if (!empty($message)) { echo "<p style='color:green; text-align:center;'>$message</p>"; } ?>
    <table>
        <tr>
            <th>Project Name</th>
            <th>Description</th>
            <th>Deadline</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <form method="POST" action="">
                    <td><?= htmlspecialchars($row['project_name']); ?></td>
                    <td><?= htmlspecialchars($row['description']); ?></td>
                    <td><?= htmlspecialchars($row['deadline']); ?></td>
                    <td>
                        <select name="status" <?= ($row['status'] == 'Completed') ? 'disabled' : ''; ?>>
                            <option value="Assigned" <?= ($row['status'] == 'Assigned') ? 'selected' : ''; ?>>Assigned</option>
                            <option value="In Progress" <?= ($row['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                        </select>
                    </td>
                    <td>
                        <?php if ($row['status'] == 'Completed') { ?>
                            <span>Completed - Cannot Change</span>
                        <?php } else { ?>
                            <input type="hidden" name="project_id" value="<?= $row['id']; ?>">
                            <button type="submit">Update</button>
                        <?php } ?>
                    </td>
                </form>
            </tr>
        <?php } ?>
    </table>
</div>

</body>
</html>
