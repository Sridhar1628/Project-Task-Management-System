<?php
// Database connection
require_once 'config.php';

// Handle form submission for project assignment
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_project'])) {
    $project_id = $_POST['project_id'];
    $team_id = $_POST['team_id'];

    if (!empty($project_id) && !empty($team_id)) {
        // Check if the project is already assigned to a team
        $check_stmt = $conn->prepare("SELECT id FROM assigned_projects WHERE project_id = ?");
        $check_stmt->bind_param("i", $project_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            echo "<script>alert('This project has already been assigned to a team.');</script>";
        } else {
            // Insert into assigned_projects with deadline
            $stmt = $conn->prepare("
            INSERT INTO assigned_projects (project_id, team_id, status, deadline, assigned_date)
            SELECT ?, ?, 'Assigned', p.deadline, NOW()
            FROM projects p
            WHERE p.id = ?
        ");

            $stmt->bind_param("iii", $project_id, $team_id, $project_id);

            if ($stmt->execute()) {
                // ✅ Update status in projects table
                $update_stmt = $conn->prepare("UPDATE projects SET status = 'Assigned' WHERE id = ?");
                $update_stmt->bind_param("i", $project_id);
                $update_stmt->execute();
                $update_stmt->close();

                echo "<script>alert('This project successfully assigned to a team.');</script>";

            } else {
                $message = "Error: " . $conn->error;
            }

            $stmt->close();
        }

        $check_stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
       echo "<script>alert('All fields are required');</script>";

    }
}

// Handle delete request for project assignment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_project'])) {
    $assigned_id = $_POST['assigned_id'];

    // Find the project_id linked to this assignment
    $proj_stmt = $conn->prepare("SELECT project_id FROM assigned_projects WHERE id = ?");
    $proj_stmt->bind_param("i", $assigned_id);
    $proj_stmt->execute();
    $proj_stmt->bind_result($project_id);
    $proj_stmt->fetch();
    $proj_stmt->close();

    $stmt = $conn->prepare("DELETE FROM assigned_projects WHERE id = ?");
    $stmt->bind_param("i", $assigned_id);

    if ($stmt->execute()) {
        // Reset auto-increment value after deletion
        // ✅ Reset project status back to Created
        if ($project_id) {
            $update_stmt = $conn->prepare("UPDATE projects SET status = 'Created' WHERE id = ?");
            $update_stmt->bind_param("i", $project_id);
            $update_stmt->execute();
            $update_stmt->close();
        }

        echo "<script>alert('This project assignment remeved successfully !!');</script>";

    } else {
        $message = "Error: " . $conn->error;
    }

    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch projects and teams
$projects = $conn->query("SELECT id, project_name FROM projects");
$teams = $conn->query("SELECT team_id, team_name FROM teams");

// Fetch assigned projects with the deadline from the projects table
$assigned_projects = $conn->query("
    SELECT ap.id, p.project_name, t.team_name, ap.assigned_date, ap.deadline, ap.status
    FROM assigned_projects ap
    JOIN projects p ON ap.project_id = p.id
    JOIN teams t ON ap.team_id = t.team_id
");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Projects</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
  background: #f5f6fa;
  color: #333;
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
.main-content {
  margin-left: 250px;
  padding: 30px;
  width: calc(100% - 250px);
}

.container {
  background: #fff;
  padding: 25px;
  border-radius: 10px;
  box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
}

h1, h2 {
  margin-bottom: 20px;
  color: #2c3e50;
}

/* Forms */
form {
  margin-bottom: 25px;
}

label {
  display: block;
  margin: 10px 0 6px;
  font-weight: 600;
}

select, input[type="text"], input[type="date"] {
  width: 100%;
  padding: 10px;
  border: 1px solid #bdc3c7;
  border-radius: 6px;
  margin-bottom: 15px;
  font-size: 14px;
}

button {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  background: #1abc9c;
  color: white;
  font-size: 14px;
  cursor: pointer;
  transition: 0.3s;
}

button:hover {
  background: #16a085;
}

button.remove-button {
  background: #e74c3c;
}

button.remove-button:hover {
  background: #c0392b;
}

.message {
  background: #dff0d8;
  color: #3c763d;
  padding: 12px;
  border-radius: 6px;
  margin: 15px 0;
  font-size: 14px;
}

/* Tables */
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 15px;
}

table th, table td {
  border: 1px solid #ddd;
  padding: 12px;
  text-align: center;
  font-size: 14px;
}

table th {
  background: #2c3e50;
  color: #fff;
}

table tr:nth-child(even) {
  background: #f9f9f9;
}

table tr:hover {
  background: #f1f1f1;
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
</div>
<div class="main-content">
    <div class="container">
        <h1>Assign Projects to Teams</h1>
        <form method="POST" action="">
            <label for="project">Select Project:</label>
            <select name="project_id" id="project" required>
                <option value="">-- Select Project --</option>
                <?php while ($row = $projects->fetch_assoc()): ?>
                    <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['project_name']) ?></option>
                <?php endwhile; ?>
            </select>

            <label for="team">Select Team:</label>
            <select name="team_id" id="team" required>
                <option value="">-- Select Team --</option>
                <?php while ($row = $teams->fetch_assoc()): ?>
                    <option value="<?= $row['team_id'] ?>"><?= htmlspecialchars($row['team_name']) ?></option>
                <?php endwhile; ?>
            </select>

            <button type="submit" name="assign_project">Assign Project</button>

            <!-- Cancel Button -->
            <button type="button" onclick="window.location.href='poi.php';" style="background-color: #f0ad4e; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                Cancel
            </button>
        </form>
        <?php if (!empty($message)): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <h2>Assigned Projects</h2>
        <?php if ($assigned_projects->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Project Name</th>
                        <th>Team Name</th>
                        <th>Assigned Date</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $assigned_projects->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['project_name']) ?></td>
                            <td><?= htmlspecialchars($row['team_name']) ?></td>
                            <td><?= htmlspecialchars($row['assigned_date']) ?></td> <!-- ✅ Added -->
                            <td><?= htmlspecialchars($row['deadline']) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="assigned_id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="remove_project" class="remove-button" onclick="return confirm('Are you sure you want to remove this project?');"> Remove</button>

                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

        <?php else: ?>
            <p>No projects have been assigned yet.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
