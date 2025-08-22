<?php
session_start();
require 'config.php'; // DB connection

// Check login
if (!isset($_SESSION['leader_id'])) {
    header("Location: leader_login.php");
    exit();
}

$leader_id = $_SESSION['leader_id'];

// Get selected filter values
$filter_project = isset($_GET['project']) ? trim($_GET['project']) : '';
$filter_member  = isset($_GET['member']) ? trim($_GET['member']) : '';

// --- Fetch distinct project names ---
$project_query = "SELECT DISTINCT p.project_name 
                  FROM tasks1 t
                  JOIN projects p ON t.project_id = p.id
                  WHERE t.leader_id = ? AND t.status = 'Completed'";
$stmt1 = $conn->prepare($project_query);
$stmt1->bind_param("i", $leader_id);
$stmt1->execute();
$projects_result = $stmt1->get_result();

// --- Fetch distinct member names ---
$member_query = "SELECT DISTINCT m.member_name 
                 FROM tasks1 t
                 JOIN team_members m ON t.member_id = m.member_id
                 WHERE t.leader_id = ? AND t.status = 'Completed'";
$stmt2 = $conn->prepare($member_query);
$stmt2->bind_param("i", $leader_id);
$stmt2->execute();
$members_result = $stmt2->get_result();

// --- Base query for completed tasks ---
$query = "SELECT t.task_id, t.task_description, t.status, 
                 m.member_name, p.project_name 
          FROM tasks1 t
          JOIN team_members m ON t.member_id = m.member_id
          JOIN projects p ON t.project_id = p.id
          WHERE t.leader_id = ? AND t.status = 'Completed'";

// Add filters
$params = [$leader_id];
$types  = "i";

if ($filter_project !== '') {
    $query .= " AND p.project_name = ?";
    $params[] = $filter_project;
    $types   .= "s";
}

if ($filter_member !== '') {
    $query .= " AND m.member_name = ?";
    $params[] = $filter_member;
    $types   .= "s";
}

$query .= " ORDER BY t.assigned_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Completed Tasks</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
            display: flex;
        }
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
        .sidebar ul { list-style: none; padding: 0; }
        .sidebar ul li { margin: 14px 0; }
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
        .sidebar ul li a i { margin-right: 12px; font-size: 20px; }
        .sidebar ul li a:hover {
            background: rgba(255, 127, 80, 0.2);
            border-left: 4px solid #ff7f50;
            color: #fff;
        }
        .container {
            margin-left: 280px;
            padding: 30px;
            width: calc(100% - 280px);
        }
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 26px;
        }
        /* Filter Form */
        .filter-form {
            width: 95%;
            margin: 0 auto 20px;
            background: #fff;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0px 2px 5px rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .filter-form select {
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 40%;
        }
        .filter-form button {
            padding: 8px 16px;
            background: #34495e;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .filter-form button:hover {
            background: #ff7f50;
        }
        /* Table */
        table {
            width: 95%;
            margin: 0 auto;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0px 2px 5px rgba(0,0,0,0.2);
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 14px;
            border: 1px solid #ddd;
            text-align: left;
            font-size: 15px;
        }
        th {
            background: #34495e;
            color: #fff;
            text-transform: uppercase;
        }
        tr:nth-child(even) { background: #f9f9f9; }
        .status { color: green; font-weight: bold; }
        .no-task {
            text-align: center;
            color: red;
            font-size: 16px;
            font-weight: bold;
            padding: 20px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
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

    <!-- Main Container -->
    <div class="container">
        <h2>✅ Completed Tasks</h2>

        <!-- Filter Form -->
        <form method="GET" class="filter-form">
            <select name="project">
                <option value="">-- All Projects --</option>
                <?php while ($p = $projects_result->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($p['project_name']) ?>" 
                        <?= ($filter_project === $p['project_name']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['project_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <select name="member">
                <option value="">-- All Members --</option>
                <?php while ($m = $members_result->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($m['member_name']) ?>" 
                        <?= ($filter_member === $m['member_name']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['member_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <button type="submit"><i class="fas fa-filter"></i> Apply Filter</button>
        </form>

        <!-- Task Table -->
        <table>
            <tr>
                <th>Task ID</th>
                <th>Project Name</th>
                <th>Member Name</th>
                <th>Task Description</th>
                <th>Status</th>
            </tr>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['task_id'] ?></td>
                        <td><?= htmlspecialchars($row['project_name']) ?></td>
                        <td><?= htmlspecialchars($row['member_name']) ?></td>
                        <td><?= htmlspecialchars($row['task_description']) ?></td>
                        <td class="status"><?= $row['status'] ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" class="no-task">No completed tasks found</td></tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>
