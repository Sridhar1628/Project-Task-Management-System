<?php
// Database connection
$servername = "localhost";
$username = "root"; // Replace with your database username
$password = ""; // Replace with your database password
$dbname = "sridhar"; // Replace with your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle team removal
if (isset($_POST['remove_team'])) {
    $team_id = $_POST['team_id'];

    // Remove from `team_leaders` table first
    $remove_leader_query = "DELETE FROM team_leaders WHERE team_id = ?";
    $stmt = $conn->prepare($remove_leader_query);
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $stmt->close();

    // Remove from `teams` table
    $remove_team_query = "DELETE FROM teams WHERE team_id = ?";
    $stmt = $conn->prepare($remove_team_query);
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $stmt->close();

    // Reset auto-increment values
    $reset_auto_increment_query = "
        SET @count = 0;
        UPDATE teams SET team_id = (@count := @count + 1);
        ALTER TABLE teams AUTO_INCREMENT = 1;
    ";
    if ($conn->multi_query($reset_auto_increment_query)) {
        do {
            // Continue looping through results if there are multiple queries
        } while ($conn->next_result());
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch teams and their leaders
$teams_query = "
    SELECT 
        t.team_id,
        t.team_name,
        tl.leader_name,
        tl.leader_email,
        tl.leader_phone
    FROM teams t
    JOIN team_leaders tl ON t.team_id = tl.team_id";
$teams_result = $conn->query($teams_query);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Dashboard</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <style>
                    /* General Reset */
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f9;
                display: flex;
                height: 100vh;
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
                margin-left: 260px;
                flex: 1;
                padding: 20px;
                background: #ecf0f1;
                overflow-y: auto;
            }

            .main-content .header {
                font-size: 2rem;
                font-weight: bold;
                margin-bottom: 20px;
                color: #2c3e50;
            }

            /* Table Styles */
            table {
                width: 100%;
                border-collapse: collapse;
                background: #fff;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                border-radius: 5px;
                overflow: hidden;
            }

            table thead {
                background: #2c3e50;
                color: white;
            }

            table thead th {
                padding: 15px;
                text-align: left;
                font-size: 1rem;
            }

            table tbody tr:nth-child(even) {
                background: #f9f9f9;
            }

            table tbody tr:hover {
                background: #f1f1f1;
            }

            table tbody td {
                padding: 15px;
                border-bottom: 1px solid #ddd;
                font-size: 0.9rem;
            }

            table tbody td:last-child {
                text-align: center;
            }

            .remove-button {
                background: #e74c3c;
                color: white;
                border: none;
                border-radius: 3px;
                padding: 8px 12px;
                cursor: pointer;
                transition: background 0.3s;
            }

            .remove-button:hover {
                background: #c0392b;
            }

            /* Button Styles */
            blockquote a button {
                background-color: green;
                color: white;
                width: 140px;
                padding: 10px 15px;
                border: none;
                border-radius: 5px;
                font-size: 1rem;
                cursor: pointer;
                transition: background 0.3s;
            }

            blockquote a button:hover {
                background: darkgreen;
            }

            /* Responsive Design */
            @media screen and (max-width: 768px) {
                .sidebar {
                    width: 60px;
                }

                .sidebar .logo {
                    font-size: 1.5rem;
                    margin-bottom: 20px;
                }

                .sidebar ul li {
                    padding: 10px;
                }

                .sidebar ul li a {
                    font-size: 0.9rem;
                }

                .main-content {
                    margin-left: 60px;
                    padding: 10px;
                }

                table thead th, table tbody td {
                    font-size: 0.8rem;
                    padding: 10px;
                }

                blockquote a button {
                    width: 100px;
                    font-size: 0.9rem;
                }
            }
        </style>
    </head>
    <body>
        <div class="sidebar">
            <div class="logo">Admin Dashboard</div>
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
            <div class="header">Manage Teams</div>
            <h2>Add Teams</h2><br>
            <blockquote>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="adtereg.php"><button style="width:100%;">Add Team</button></a></blockquote><br>
            <h2>Teams List</h2>
            <table>
                <thead>
                    <tr>
                        <th>Team ID</th>
                        <th>Team Name</th>
                        <th>Leader Name</th>
                        <th>Leader Phone</th>
                        <th>Leader Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($teams_result->num_rows > 0): ?>
                        <?php while ($team = $teams_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $team['team_id']; ?></td>
                                <td><?= htmlspecialchars($team['team_name']); ?></td>
                                <td><?= htmlspecialchars($team['leader_name']); ?></td>
                                <td><?= htmlspecialchars($team['leader_phone']); ?></td>
                                <td><?= htmlspecialchars($team['leader_email']); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="team_id" value="<?= $team['team_id']; ?>">
                                        <button type="submit" name="remove_team" class="remove-button btn" onclick="confirm('Are you sure to remove the team ?')">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No teams available.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </body>
</html>

<?php
$conn->close();
?>
