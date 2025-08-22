<?php
include 'config.php';

// Fetch projects
$query = "SELECT id, project_name, description, deadline, status FROM projects";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Project Status</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
       /* Reset default browser styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
}

body {
    display: flex;
    background: #f4f6f9;
    color: #333;
    min-height: 100vh;
}

/* Sidebar */
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
main {
    margin-left: 240px;
    padding: 30px;
    flex-grow: 1;
}

main h2 {
    margin-bottom: 20px;
    color: #2f0337ff;
    font-size: 24px;
}

/* Table */
table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

table th, table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

table th {
    background: #06af15ff;
    color: #fff;
    font-size: 15px;
}

table tr:hover {
    background: #f1f5ff;
}

/* Dropdown (Status Select) */
.status-select {
    padding: 6px 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
    outline: none;
    cursor: pointer;
    transition: border 0.3s;
}

.status-select:focus {
    border-color: #0d47a1;
}

/* Update Button */
.update-status-btn {
    padding: 7px 14px;
    border: none;
    border-radius: 6px;
    background: #1e88e5;
    color: #fff;
    font-size: 14px;
    cursor: pointer;
    transition: 0.3s;
}

.update-status-btn:hover {
    background: #1565c0;
}

    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">Admin <br> Dashboard</div>
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

<main>
    <h2>Manage Project Status</h2>
    <table>
        <tr>
            <th>Project Name</th>
            <th>Description</th>
            <th>Deadline</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr id="row-<?= $row['id']; ?>">
                <td><?= htmlspecialchars($row['project_name']); ?></td>
                <td><?= htmlspecialchars($row['description']); ?></td>
                <td><?= htmlspecialchars($row['deadline']); ?></td>
                <td>
                    <select class="status-select" data-project-id="<?= $row['id']; ?>">
                        <option value="Created" <?= $row['status'] == 'Created' ? 'selected' : ''; ?>>Created</option>
                        <option value="Assigned" <?= $row['status'] == 'Assigned' ? 'selected' : ''; ?>>Assigned</option>
                        <option value="In Progress" <?= $row['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Completed" <?= $row['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </td>
                <td><button class="update-status-btn" data-project-id="<?= $row['id']; ?>">Update</button></td>
            </tr>
        <?php } ?>
    </table>
</main>

<script>
$(document).ready(function() {
    $(".update-status-btn").click(function() {
        var projectId = $(this).data("project-id");
        var newStatus = $("#row-" + projectId).find(".status-select").val();

        $.ajax({
            url: "update_status.php",
            type: "POST",
            data: { project_id: projectId, status: newStatus },
            success: function(response) {
                // Show success message without page reload
                alert("Status updated successfully to: " + newStatus);

                // Update the table cell visually
                $("#row-" + projectId).find(".status-select").val(newStatus);
            },
            error: function() {
                alert("Error updating status.");
            }
        });
    });
});
</script>

</body>
</html>
