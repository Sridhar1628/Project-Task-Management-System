<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sridhar";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add new project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_project'])) {
    $project_name = $_POST['project_name'];
    $description = $_POST['description'];
    $deadline = $_POST['deadline'];
    $status = 'Created';

    $stmt = $conn->prepare("INSERT INTO projects (project_name, description, deadline, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $project_name, $description, $deadline, $status);
    $stmt->execute();
    $stmt->close();

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Delete project
if (isset($_GET['delete_project']) && is_numeric($_GET['delete_project'])) {
    $project_id = intval($_GET['delete_project']);

    // Step 1: Delete related records from assigned_projects
    $stmt = $conn->prepare("DELETE FROM assigned_projects WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $stmt->close();

    // Step 2: Delete the project from projects table
    $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->bind_param("i", $project_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Project deleted successfully!'); window.location.href='".$_SERVER['PHP_SELF']."';</script>";
    }
}


// Fetch all projects
$result = $conn->query("SELECT * FROM projects ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Projects</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
    /* Reset some default styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body {
            margin: 0;
            padding: 0;
            height: 100vh; /* Full viewport height */
            display: flex;
            justify-content: center; /* Horizontal center */
            align-items: center;     /* Vertical center */
            background-color: #f4f4f4; /* Optional */
            font-family: Arial, sans-serif;
        }

        /* Adjust sidebar so it doesn't get pushed into center alignment */
        .sidebar {
            flex-shrink: 0; /* prevent sidebar from resizing */
            position: fixed; /* stays on left */
        }

        /* Adjust main content */
        .main-content {
    margin-left: 260px; /* keep space for sidebar */
    padding: 20px;
    width: calc(100% - 260px); /* take up all available space */
    max-width: none; /* remove restriction */
}

/* Table styling for full stretch */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    background: white;
    box-shadow: 0px 2px 6px rgba(0,0,0,0.1);
}


        /* Sidebar styling */
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
        /* Main content */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            justify-content: center;
            align-items: center;
        }

        .main-content h1 {
            margin-bottom: 20px;
            color: #333;
        }

        /* Form styling */
        form {
            background: #f9f9f9;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0px 2px 6px rgba(0,0,0,0.1);
            max-width: 1000px;
        }

        form h2 {
            margin-bottom: 15px;
            color: #444;
        }

        form label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
        }

        form input, 
        form textarea, 
        form button {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        form button {
            background-color: #1abc9c;
            color: white;
            border: none;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        form button:hover {
            background-color: #16a085;
        }

        /* Table styling */
        

        table th, table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        table th {
            background-color: #34495e;
            color: white;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }

        table a {
            color: red;
            text-decoration: none;
        }

        table a:hover {
            text-decoration: underline;
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
            <h1>Manage Projects</h1>

                <!-- Add Project Form -->
                <form method="POST">
                    <h2>Add New Project</h2>
                    <label>Project Name:</label>
                    <input type="text" name="project_name" required>
                    
                    <label>Description:</label>
                    <textarea name="description" required></textarea>
                    
                    <label>Deadline:</label>
                    <input type="date" name="deadline" required>
                    
                    <button type="submit" name="add_project">Add Project</button><br>
                    <button type="reset" style="background-color:red;">Reset</button>
                </form>

    <!-- Existing Projects Table -->
                <center><h1 style="color:brown;">Existing Projects</h1></center>
                <table border="1">
                    <thead>
                        <tr>
                            <th>Project Name</th>
                            <th>Description</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Remove</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['project_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                <td><?php echo htmlspecialchars($row['deadline']); ?></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                <td>
                                    <a href="?delete_project=<?php echo $row['id']; ?>" 
                                    onclick="return confirm('Are you sure you want to remove this project?');">Remove</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
        </div>
    </body>
</html>

<?php
$conn->close();
?>
