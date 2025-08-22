<?php
session_start();
require 'config.php';

if (!isset($_SESSION['leader_id'])) {
    header("Location: leader_login.php");
    exit();
}

$leader_id = $_SESSION['leader_id'];

// Fetch submitted proofs with project and task details
$proofs = [];
$stmt = $conn->prepare("
    SELECT p.proof_id, p.proof_file, p.description, t.task_description, pr.project_name, m.member_name
    FROM proofs1 p
    JOIN tasks1 t ON p.task_id = t.task_id
    JOIN projects pr ON p.project_id = pr.id
    JOIN team_members m ON p.member_id = m.member_id
    WHERE t.leader_id = ? ORDER BY p.uploaded_at DESC
");
$stmt->bind_param("i", $leader_id);
$stmt->execute();
$stmt->bind_result($proof_id, $proof_file, $proof_description, $task_desc, $project_name, $member_name);

while ($stmt->fetch()) {
    $proofs[] = [
        "id" => $proof_id,
        "file" => $proof_file,
        "description" => $proof_description,
        "task" => $task_desc,
        "project" => $project_name,
        "member" => $member_name
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Submitted Proofs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            background: #7fa3f1ff;
            color: #e0e0e0;
            top: 0;
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

        /* Content */
        .content {
            flex: 1;
            margin-left: 290px;
        }
        .content h2 {
            color: #00ff99;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            background: #2d2a2aff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 6px rgba(0,0,0,0.5);
        }
        table th, table td {
            padding: 12px;
            border-bottom: 1px solid #333;
        }
        table th {
            background: #00ff99;
            color: black;
        }
        table tr:hover {
            background: #2a2a2a;
        }
        table td a {
            color: #00ff99;
            font-weight: bold;
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
        <h2>Submitted Proofs</h2>
        <table>
            <tr>
                <th>Task Description</th>
                <th>Project Name</th>
                <th>Member Name</th>
                <th>Proof Description</th>
                <th>File</th>
                <th>View</th>
            </tr>
            <?php foreach ($proofs as $proof) : ?>
                <tr>
                    <td><?= htmlspecialchars($proof['task']); ?></td>
                    <td><?= htmlspecialchars($proof['project']); ?></td>
                    <td><?= htmlspecialchars($proof['member']); ?></td>
                    <td><?= htmlspecialchars($proof['description']); ?></td>
                    <td><?= htmlspecialchars($proof['file']); ?></td>
                    <td><a href="team_uploads/<?= htmlspecialchars($proof['file']); ?>" target="_blank"><button type="button" style="width :80px;background: #5da4a3ff;height :30px;color :purple;border:none; border-radius:5px; cursor:pointer;font-size :17px;">View</button></a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
