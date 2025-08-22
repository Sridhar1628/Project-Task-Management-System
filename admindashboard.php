<?php
require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* ====== Global Reset ====== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            display: flex;
            background-color: #f4f6f9;
        }

        /* 2x2 Card Grid Layout */
        .card-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            height: calc(100vh - 150px); /* Adjust height for header space */
            align-items: stretch;
        }
        .card {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            padding: 20px;
            text-align: center;
            background-color: #ecf0f1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        .card-body h5 {
            font-size: 24px;
            color: #2980b9;
            margin-bottom: 10px;
        }
        .card-body p {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
        }

        /* ====== Sidebar ====== */
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

        /* ====== Main Content ====== */
        main {
            width: 100%;
            margin-left: 270px;
            padding: 50px;
            background-color: #ffffff;
            height: 100vh; /* Fill exactly one screen height */
            border-top-left-radius: 20px;
            border-bottom-left-radius: 20px;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            justify-content: flex-start; /* Keep items starting from top */
        }


        main h2 {
            font-size: 32px;
            color: #34495e;
            margin-bottom: 40px;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }

        /* ====== Scrollbar Styling ====== */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background-color: rgba(255, 255, 255, 0.5);
        }

        /* ====== Responsive Design ====== */
        @media (max-width: 992px) {
            .col-md-3 {
                flex: 1 1 calc(50% - 20px);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            main {
                margin-left: 200px;
                width: calc(100% - 200px);
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
                width: 100%;
            }
            .col-md-3 {
                flex: 1 1 100%;
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

    <main>
        <h2>Dashboard Overview</h2>
        <div class="card-container">
            <div class="card">
                <div class="card-body">
                    <h5>Total Teams</h5>
                    <p class="card-text">
                        <?php
                        $result = $conn->query("SELECT COUNT(*) AS total FROM teams");
                        $row = $result->fetch_assoc();
                        echo $row['total'];
                        ?>
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5>Active Projects</h5>
                    <p class="card-text">
                        <?php
                        $result = $conn->query("SELECT COUNT(*) AS total FROM projects WHERE status = 'Assigned' OR status = 'In Progress'");
                        $row = $result->fetch_assoc();
                        echo $row['total'];
                        ?>
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5>Completed Projects</h5>
                    <p class="card-text">
                        <?php
                        $result = $conn->query("SELECT COUNT(*) AS total FROM projects WHERE status = 'Completed'");
                        $row = $result->fetch_assoc();
                        echo $row['total'];
                        ?>
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5>Upcoming Deadlines</h5>
                    <p class="card-text">
                        <?php
                        $today = date('Y-m-d');
                        $result = $conn->query("SELECT COUNT(*) AS total FROM projects WHERE deadline > '$today' AND status != 'Completed'");
                        $row = $result->fetch_assoc();
                        echo $row['total'];
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>

<?php
$conn->close();
?>
