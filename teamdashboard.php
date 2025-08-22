<?php
require 'config.php'; // Include database connection file
// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['team_id'])) {
   echo "<script>alert('You need to log in first.'); window.location.href='teamlogin.php';</script>";
    exit();
}

$team_id = $_SESSION['team_id'];
$team_name = $_SESSION['team_name']; // Stored during login

// Fetch team information
$team_info = $conn->query("SELECT team_name FROM teams WHERE team_id = $team_id")->fetch_assoc();
$team_name = $team_info['team_name'];

// Fetch assigned projects for the logged-in team (filtered by team ID)
$assigned_projects_query = "
    SELECT p.project_name, p.description, ap.status, ap.deadline 
    FROM assigned_projects ap
    JOIN projects p ON ap.project_id = p.id
    WHERE ap.team_id = $team_id
";
$assigned_projects = $conn->query($assigned_projects_query);

// Get project counts for overview
$assigned_count_query = "SELECT COUNT(*) AS total FROM assigned_projects WHERE team_id = $team_id AND status = 'Assigned' OR status='In Progress'";
$completed_count_query = "SELECT COUNT(*) AS total FROM assigned_projects WHERE team_id = $team_id AND status = 'Completed'";
$upcoming_count_query = "SELECT COUNT(*) AS total FROM assigned_projects WHERE team_id = $team_id AND deadline > CURDATE()";

$assigned_count = $conn->query($assigned_count_query)->fetch_assoc()['total'];
$completed_count = $conn->query($completed_count_query)->fetch_assoc()['total'];
$upcoming_count = $conn->query($upcoming_count_query)->fetch_assoc()['total'];
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
    font-family: "Poppins", Arial, sans-serif;
}

body {
    display: flex;
    min-height: 100vh;
    background: #f6f8fa;
    color: #222;
}

/* Sidebar */
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

/* Main */
main {
    margin-left: 280px;  /* Match sidebar width */
    padding: 40px;
    flex: 1;
}

main h2 {
    margin-bottom: 30px;
    font-size: 32px;
    font-weight: bold;
    color: #2c3e50;
}

/* Overview Cards */
.overview {
    display: flex;
    gap: 30px;
    margin-bottom: 40px;
    flex-wrap: wrap;
}

.card {
    flex: 1;
    min-width: 240px;
    border-radius: 14px;
    color: #fff;
    padding: 30px;
    text-align: center;
    font-size: 20px;
    font-weight: 600;
    box-shadow: 0 4px 14px rgba(0,0,0,0.2);
    transition: transform 0.3s;
}

.card:hover {
    transform: translateY(-6px);
}

.card-body h5 {
    font-size: 22px;
    margin-bottom: 14px;
}

.card-body p {
    font-size: 30px;   /* Bigger numbers */
    font-weight: bold;
}

/* Card Colors */
.card.assigned { background: #ff7f50; }   /* Orange */
.card.completed { background: #28a745; } /* Green */
.card.upcoming { background: #007bff; }  /* Blue */

/* Project Table */
.project-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    font-size: 18px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.15);
}

.project-table th, .project-table td {
    padding: 16px 20px;
    text-align: left;
}

.project-table thead {
    background: #2c3e50;
    color: #fff;
    font-size: 20px;
}

.project-table tbody tr:nth-child(even) {
    background: #f8f9fa;
}

.project-table tbody tr:hover {
    background: #fff4ed;
    transition: background 0.3s;
}
/* Welcome bar layout */
.welcome-bar {
    display: flex;
    justify-content: space-between; /* Pushes text left, buttons right */
    align-items: center;
    margin-bottom: 30px;
}

/* Buttons */
.btn {
    text-decoration: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    margin-left: 10px;
    transition: 0.3s;
}

.leader-btn {
    background: #11def5f7;   /* Blue */
    color: #fff;
}

.leader-btn:hover {
    background: #0056b3;
}

.team-btn {
    background: #52f60cff;   /* Green */
    color: #fff;
}

.team-btn:hover {
    background: #1e7e34;
}


/* Responsive */
@media (max-width: 768px) {
    .sidebar {
        width: 200px;
    }

    main {
        margin-left: 200px;
    }

    .overview {
        flex-direction: column;
    }
}

@media (max-width: 576px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    main {
        margin-left: 0;
    }
}

</style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">Team <br> Dashboard</div>
        <ul>
            <li><a href="teamdashboard.php"><i class="fas fa-tachometer-alt"></i> Home</a></li>
            <li><a href="team_members.php"><i class="fas fa-users"></i> Team Members</a></li>
            <li><a href="update_status.php"><i class="fas fa-edit"></i> Update Status</a></li>
            <li><a href="submit_proof.php"><i class="fas fa-file-upload"></i> Submit Proof</a></li>
            <li><a href="view_comment.php"><i class="fas fa-file-upload"></i> Discussion</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <main>
    <div class="welcome-bar">
        <h2>Welcome, <?= htmlspecialchars($team_name); ?>!</h2>
        <div class="login-buttons">
            <a href="leader_dashboard.php" class="btn leader-btn"><i class="fas fa-user-tie"></i>&nbsp;&nbsp;Leader Dashboard</a>
            <a href="member_dashboard.php" class="btn team-btn"><i class="fas fa-user"></i>&nbsp;&nbsp;Member Dashboard</a>
        </div>
    </div>

    <!-- Display overview cards -->
    <div class="overview">
        <div class="card assigned">
            <div class="card-body">
                <h5>Assigned Projects</h5>
                <p><?= $assigned_count; ?></p>
            </div>
        </div>
        <div class="card completed">
            <div class="card-body">
                <h5>Completed Projects</h5>
                <p><?= $completed_count; ?></p>
            </div>
        </div>
        <div class="card upcoming">
            <div class="card-body">
                <h5>Upcoming Deadlines</h5>
                <p><?= $upcoming_count; ?></p>
            </div>
        </div>
    </div>

    <!-- Display assigned projects -->
    <h2>Assigned Projects</h2><br>
    <table class="project-table">
        <thead>
            <tr>
                <th>Project Name</th>
                <th>Description</th>
                <th>Status</th>
                <th>Deadline</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($assigned_projects->num_rows > 0): ?>
                <?php while ($row = $assigned_projects->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['project_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td><?php echo htmlspecialchars($row['deadline']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No projects assigned yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    </main>
</body>
</html>

<?php
$conn->close();
?>
