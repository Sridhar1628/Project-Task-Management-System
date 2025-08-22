<?php
session_start();
require 'config.php';

if (!isset($_SESSION['leader_id'])) {
    header("Location: leader_login.php");
    exit();
}

$leader_id = $_SESSION['leader_id'];
$team_id = $_SESSION['team_id'];
$alert_message = "";
$reload_page = false;

// Fetch team members
$stmt = $conn->prepare("SELECT member_id, member_name FROM team_members WHERE team_id = ?");
$stmt->bind_param("i", $team_id);
$stmt->execute();
$stmt->bind_result($member_id, $member_name);
$team_members = [];
while ($stmt->fetch()) {
    $team_members[] = ["id" => $member_id, "name" => $member_name];
}
$stmt->close();

// Fetch projects assigned to the team
$stmt = $conn->prepare("
    SELECT p.id, p.project_name 
    FROM assigned_projects ap
    JOIN projects p ON ap.project_id = p.id
    WHERE ap.team_id = ?
");
$stmt->bind_param("i", $team_id);
$stmt->execute();
$stmt->bind_result($project_id, $project_name);
$projects = [];
while ($stmt->fetch()) {
    $projects[] = ["id" => $project_id, "name" => $project_name];
}
$stmt->close();

// Fetch assigned tasks with project names
$stmt = $conn->prepare("
    SELECT t.task_id, t.task_description, m.member_name, t.status, p.project_name 
    FROM tasks1 t JOIN team_members m ON t.member_id = m.member_id JOIN projects p ON t.project_id = p.id WHERE t.leader_id = ? 
    AND t.project_id IN (SELECT project_id FROM assigned_projects WHERE team_id = ?) AND t.status IN ('Pending', 'In Progress')
");
$stmt->bind_param("ii", $leader_id, $team_id);
$stmt->execute();
$stmt->bind_result($task_id, $task_description, $assigned_member, $status, $task_project_name);
$tasks = [];
while ($stmt->fetch()) {
    $tasks[] = ["id" => $task_id, "description" => $task_description, "member" => $assigned_member, "status" => $status, "project" => $task_project_name];
}
$stmt->close();

$stmt = $conn->prepare("
    SELECT t.task_id, t.task_description, m.member_name, t.status, p.project_name 
    FROM tasks1 t JOIN team_members m ON t.member_id = m.member_id JOIN projects p ON t.project_id = p.id WHERE t.leader_id = ? 
    AND t.project_id IN (SELECT project_id FROM assigned_projects WHERE team_id = ?)
");
$stmt->bind_param("ii", $leader_id, $team_id);
$stmt->execute();
$stmt->bind_result($task_id, $task_description, $assigned_member, $status, $task_project_name);
$tasks1 = [];
while ($stmt->fetch()) {
    $tasks1[] = ["id" => $task_id, "description" => $task_description, "member" => $assigned_member, "status" => $status, "project" => $task_project_name];
}
$stmt->close();


// ---- Task Summary ----
$total_tasks = count($tasks1);
$completed_tasks = 0;
foreach ($tasks1 as $t) {
    if ($t['status'] === 'Completed') {
        $completed_tasks++;
    }
}
$pending_tasks = $total_tasks - $completed_tasks;

// ---- Progress by Project ----
$project_progress = [];
foreach ($projects as $proj) {
    $proj_total = 0;
    $proj_completed = 0;
    foreach ($tasks as $t) {
        if ($t['project'] == $proj['name']) {
            $proj_total++;
            if ($t['status'] === 'Completed') {
                $proj_completed++;
            }
        }
    }
    $percent = ($proj_total > 0) ? round(($proj_completed / $proj_total) * 100) : 0;
    $project_progress[$proj['name']] = $percent;
}

// Assign Task
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_task'])) {
    $task_description = trim($_POST['task_description']);
    $assigned_to = $_POST['assigned_to'];
    $project_id = $_POST['project_id'];

    // Check if task already exists
    $stmt = $conn->prepare("SELECT task_id FROM tasks1 WHERE project_id = ? AND task_description = ?");
    $stmt->bind_param("is", $project_id, $task_description);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Task already exists for this project!');</script>";
        echo "<script>window.location.href='leader_dashboard.php';</script>";
        $stmt->close();
        $reload_page = true;
    } else {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO tasks1 (project_id, leader_id, member_id, task_description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $project_id, $leader_id, $assigned_to, $task_description);
        if ($stmt->execute()) {
            echo "<script>alert('Task assigned successfully!');</script>";
            echo "<script>window.location.href='leader_dashboard.php';</script>";
            $reload_page = true;
        } else {
            echo "<script>alert('Error assigning task. Please try again.');</script>";
            echo "<script>window.location.href='leader_dashboard.php';</script>";
        }
        $stmt->close();
    }
}

// Update Task Status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $task_id = $_POST['task_id'];
    $stmt = $conn->prepare("UPDATE tasks1 SET status = 'Completed' WHERE task_id = ?");
    $stmt->bind_param("i", $task_id);
    if ($stmt->execute()) {
        echo "<script>alert('Task marked as completed!');</script>";
        echo "<script>window.location.href='leader_dashboard.php';</script>";
        $reload_page = true;
    }
    $stmt->close();
}

// Delete Task
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_task'])) {
    $task_id = $_POST['task_id'];
    $stmt = $conn->prepare("DELETE FROM tasks1 WHERE task_id = ? AND leader_id = ?");
    $stmt->bind_param("ii", $task_id, $leader_id);
    if ($stmt->execute()) {
        echo "<script>alert('Task deleted successfully!');</script>";
        echo "<script>window.location.href='leader_dashboard.php';</script>";
    } else {
        echo "<script>alert('Error deleting task.');</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Team Leader Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* General reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
}

body {
    display: flex;
    min-height: 100vh;
    background-color: #f5f6fa;
}

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

/* Content area */
.content {
    margin-left: 280px;
    padding: 30px;
    width: 100%;
}

.content h2 {
    margin-bottom: 20px;
    color: #2c3e50;
}

/* Form styling */
form {
    background: #fff;
    padding: 20px;
    margin-bottom: 30px;
    border-radius: 8px;
    box-shadow: 0px 2px 6px rgba(0,0,0,0.1);
}

form label {
    display: block;
    margin-bottom: 6px;
    font-weight: bold;
    color: #333;
}

form input[type="text"],
form select {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 6px;
    border: 1px solid #ccc;
    outline: none;
    transition: 0.2s;
}

form input[type="text"]:focus,
form select:focus {
    border-color: #2980b9;
}

/* Buttons */
button {
    background: #2980b9;
    color: #fff;
    padding: 10px 18px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    transition: 0.3s;
}

button:hover {
    background: #1f5f87;
}

/* Task table */
table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0px 2px 6px rgba(0,0,0,0.1);
}

table th,
table td {
    padding: 14px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

table th {
    background: #2980b9;
    color: #fff;
    text-transform: uppercase;
    font-size: 14px;
}

table tr:hover {
    background: #f0f8ff;
}

table td form {
    margin: 0;
    padding: 0;
    box-shadow: none;
    background: none;
}

table td button {
    background: #27ae60;
    font-size: 13px;
    padding: 6px 12px;
}

table td button:hover {
    background: #1e8449;
}
/* Cards for summary */
.cards {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
}

.card {
    flex: 1;
    background: #fff;
    padding: 20px;
    text-align: center;
    border-radius: 8px;
    box-shadow: 0px 2px 6px rgba(0,0,0,0.1);
}

.card h3 {
    font-size: 18px;
    color: #2c3e50;
    margin-bottom: 10px;
}

.card p {
    font-size: 22px;
    font-weight: bold;
    color: #2980b9;
}

/* Progress bars */
.progress-container {
    margin-bottom: 30px;
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0px 2px 6px rgba(0,0,0,0.1);
}

.progress-item {
    margin-bottom: 15px;
}

.progress-item span {
    display: block;
    margin-bottom: 6px;
    font-weight: bold;
    color: #333;
}

.progress-bar {
    background: #ecf0f1;
    height: 18px;
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill {
    background: #27ae60;
    height: 100%;
    transition: width 0.4s ease;
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
    <div class="content">
        <h2>Team Overview</h2>
<div class="cards">
    <div class="card">
        <h3>Total Tasks</h3>
        <p><?= $total_tasks; ?></p>
    </div>
    <div class="card">
        <h3>Completed</h3>
        <p><?= $completed_tasks; ?></p>
    </div>
    <div class="card">
        <h3>Pending</h3>
        <p><?= $pending_tasks; ?></p>
    </div>
</div>


        <h2>Assign Task</h2>
        <form method="post">
            <label>Task Description:</label>
            <input type="text" name="task_description" required>
            <label>Assign To:</label>
            <select name="assigned_to" required>
                <option value="">Select Member</option>
                <?php foreach ($team_members as $member) : ?>
                    <option value="<?= $member['id']; ?>"><?= $member['name']; ?></option>
                <?php endforeach; ?>
            </select>
            <label>Select Project:</label>
            <select name="project_id" required>
                <option value="">Select Project</option>
                <?php foreach ($projects as $project) : ?>
                    <option value="<?= $project['id']; ?>"><?= $project['name']; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="assign_task">Assign Task</button>
        </form>

        <h2>View Assigned Tasks</h2>
        <table>
            <tr>
                <th>Project Name</th>
                <th>Task Description</th>
                <th>Assigned To</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php foreach ($tasks as $task) : ?>
                <tr>
                    <td><?= $task['project']; ?></td>
                    <td><?= $task['description']; ?></td>
                    <td><?= $task['member']; ?></td>
                    <td><?= $task['status']; ?></td>
                    <td>
                        <!-- Mark Completed Button -->
                        <form method="post" style="display:inline-block;">
                            <input type="hidden" name="task_id" value="<?= $task['id']; ?>">
                            <button type="submit" name="update_status" onclick="return confirm('Do you sure to make this task completed');">Mark Completed</button>
                        </form>

                        <!-- Delete Task Button -->
                        <form method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this task?');">
                            <input type="hidden" name="task_id" value="<?= $task['id']; ?>">
                            <button type="submit" name="delete_task" style="background:#e74c3c;">Delete</button>
                        </form>
                    </td>

                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
