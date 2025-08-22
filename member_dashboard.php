<?php
session_start();
require 'config.php'; // DB connection

// Check login
if (!isset($_SESSION['member_id'])) {
    header("Location: member_login.php");
    exit();
}

$member_id = $_SESSION['member_id'];
$team_id   = $_SESSION['team_id'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $task_id = intval($_POST['task_id']);
    $new_status = $_POST['status'];

    // Allow only 'In Progress' or 'Completed'
    if (in_array($new_status, ['In Progress', 'Pending'])) {
        $update = "UPDATE tasks1 SET status = ? WHERE task_id = ? AND member_id = ? AND status != 'Completed'";
        $stmt = $conn->prepare($update);
        $stmt->bind_param("sii", $new_status, $task_id, $member_id);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            echo "<script>alert('Task status updated successfully.');</script>";
            echo "<script>window.location.href='member_dashboard.php';</script>";
        } else {
            echo "<script>alert('Failed to update task status or no changes made.');</script>";
            echo "<script>window.location.href='member_dashboard.php';</script>";
        }
    }
}

// Get task counts
$count_query = "
    SELECT status, COUNT(*) as count 
    FROM tasks1 
    WHERE member_id = ? 
    GROUP BY status";
$stmt = $conn->prepare($count_query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();

$counts = ["Pending" => 0, "In Progress" => 0, "Completed" => 0];
while ($row = $result->fetch_assoc()) {
    $counts[$row['status']] = $row['count'];
}

// Project filter
$filter_project = isset($_GET['project_filter']) ? intval($_GET['project_filter']) : 0;

// Fetch projects for dropdown
$projects_query = "SELECT DISTINCT p.id, p.project_name 
                   FROM tasks1 t 
                   JOIN projects p ON t.project_id = p.id 
                   WHERE t.member_id = ?";
$stmt = $conn->prepare($projects_query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$projects = $stmt->get_result();

// Fetch tasks (only Pending & In Progress)
$query = "SELECT t.task_id, t.task_description, t.status, p.project_name 
          FROM tasks1 t 
          JOIN projects p ON t.project_id = p.id 
          WHERE t.member_id = ? AND t.status IN ('Pending','In Progress')";
if ($filter_project > 0) {
    $query .= " AND t.project_id = ?";
}

$stmt = $conn->prepare($query);
if ($filter_project > 0) {
    $stmt->bind_param("ii", $member_id, $filter_project);
} else {
    $stmt->bind_param("i", $member_id);
}
$stmt->execute();
$tasks = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Member Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
            body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            margin: 0;
            padding: 0;
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #2c3e50, #000);
            color: #fff;
            padding: 25px 0;
            position: fixed;
            height: 100%;
            box-shadow: 2px 0 8px rgba(0,0,0,0.25);
        }

        .sidebar .logo {
            text-align: center;
            font-size: 26px;
            font-weight: bold;
            padding: 20px;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(255,255,255,0.25);
            color: #ff7f50;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar ul li {
            margin: 12px 0;
        }

        .sidebar ul li a {
            display: flex;
            align-items: center;
            font-size: 16px;
            color: #ddd;
            padding: 12px 22px;
            text-decoration: none;
            transition: 0.3s;
            border-left: 4px solid transparent;
        }

        .sidebar ul li a i {
            margin-right: 12px;
            font-size: 18px;
        }

        .sidebar ul li a:hover {
            background: rgba(255, 127, 80, 0.2);
            border-left: 4px solid #ff7f50;
            color: #fff;
        }

        /* Content */
        .main-content {
            margin-left: 260px; /* Same as sidebar width */
            padding: 30px;
            flex: 1;
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #2c3e50;
        }

        /* Stats cards */
        .stats {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
            width: 200px;
            transition: transform 0.2s ease-in-out;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h3 {
            margin: 0;
            font-size: 18px;
        }

        .card p {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0 0;
        }

        /* Filter */
        .filter {
            margin-bottom: 20px;
            text-align: right;
        }

        .filter select {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }

        th, td {
            border: 1px solid #eee;
            padding: 12px;
            text-align: center;
            font-size: 14px;
        }

        th {
            background: #2c3e50;
            color: white;
            font-size: 15px;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        /* Buttons & Dropdowns inside table */
        select {
            padding: 6px 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 13px;
        }

        button {
            padding: 6px 12px;
            border-radius: 5px;
            background: #3498db;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 13px;
            transition: 0.3s;
        }

        button:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">Member<br>Dashboard</div>
                <ul>
                    <li><a href="member_dashboard.php"><i class="fas fa-tachometer-alt"></i> Home</a></li>
                    <li><a href="team_submit_proof.php"><i class="fas fa-users"></i>Submit Proofs</a></li>
                    <li><a href="mem_completed_tasks.php"><i class="fas fa-check-circle"></i> Completed Tasks</a></li>
                    <li><a href="teamdashboard.php"><i class="fas fa-home"></i>Team Dashboard</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
        </div>
    </div>
    <div class="main-content">

    <h2>Team Member Dashboard</h2>

    <!-- Task Counts -->
    <div class="stats">
        <div class="card" style="background:#f39c12; color:white;">
            <h3>Pending Tasks</h3>
            <p><?= $counts['Pending']; ?></p>
        </div>
        <div class="card" style="background:#3498db; color:white;">
            <h3>In Progress</h3>
            <p><?= $counts['In Progress']; ?></p>
        </div>
        <div class="card" style="background:#27ae60; color:white;">
            <h3>Completed</h3>
            <p><?= $counts['Completed']; ?></p>
        </div>
    </div>

    <!-- Project Filter -->
    <div class="filter">
        <form method="get" action="">
            <label><strong>Filter by Project:</strong></label>
            <select name="project_filter" onchange="this.form.submit()">
                <option value="0">All Projects</option>
                <?php while($proj = $projects->fetch_assoc()): ?>
                    <option value="<?= $proj['id']; ?>" <?= ($filter_project == $proj['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($proj['project_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>
    </div>

    <!-- Task Table -->
    <table>
        <tr>
            <th>Project Name</th>
            <th>Task Description</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php if ($tasks->num_rows > 0): ?>
            <?php while($task = $tasks->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($task['project_name']); ?></td>
                    <td><?= htmlspecialchars($task['task_description']); ?></td>
                    <td><?= htmlspecialchars($task['status']); ?></td>
                    <td>
                        <form method="post" action="">
                            <input type="hidden" name="task_id" value="<?= $task['task_id']; ?>">
                            <select name="status">
                                <option value="Pending" <?= ($task['status']=="Pending")?'selected':''; ?>>Pending</option>
                                <option value="In Progress" <?= ($task['status']=="In Progress")?'selected':''; ?>>In Progress</option>
                            </select>
                            <button type="submit" name="update_status">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4">No Pending or In-Progress tasks found.</td></tr>
        <?php endif; ?>
    </table>
</div>

</body>
</html>
