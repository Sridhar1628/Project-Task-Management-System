<?php
session_start();
require 'config.php'; // DB connection

// Check if member is logged in
if (!isset($_SESSION['member_id']) || !isset($_SESSION['team_id'])) {
    header("Location: member_login.php");
    exit();
}

$member_id = $_SESSION['member_id'];
$team_id   = $_SESSION['team_id'];

// Fetch completed tasks assigned to this member
$query = "
    SELECT t.task_id, t.task_description, t.status, t.assigned_at,
           p.project_name, m.member_name
    FROM tasks1 t
    JOIN projects p ON t.project_id = p.id
    JOIN team_members m ON t.member_id = m.member_id
    WHERE t.member_id = ? AND t.status = 'Completed'
    ORDER BY t.assigned_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();
?>


<html>
<head>
    <title>Completed Tasks</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">   
    <style>
       body {
    margin: 0;
    font-family: Verdana, sans-serif;
    background: #eafbea;
}

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

.container {
    margin-left: 280px;
    padding: 30px;
}

.container h2 {
    text-align: center;
    color: #065f46;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border: 1px solid #ccc;
}

th, td {
    padding: 12px;
    border-bottom: 1px solid #ccc;
    text-align: center;
}

th {
    background: #10b981;
    color: white;
}

.status {
    color: #065f46;
    font-weight: bold;
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
    <div class="container">
        <h2>Your Completed Tasks</h2>

        <?php if ($result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Task ID</th>
                    <th>Project Name</th>
                    <th>Task Description</th>
                    <th>Status</th>
                    <th>Assigned Date</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['task_id']) ?></td>
                        <td><?= htmlspecialchars($row['project_name']) ?></td>
                        <td><?= htmlspecialchars($row['task_description']) ?></td>
                        <td class="status"><?= htmlspecialchars($row['status']) ?></td>
                        <td><?= htmlspecialchars($row['assigned_at']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p style="text-align:center; color:#666;">No completed tasks found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
