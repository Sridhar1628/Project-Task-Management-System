<?php
include 'config.php';
session_start();

// Ensure only admins can comment (assuming you store admin id in session)
$admin_id = $_SESSION['admin_id'] ?? 1; // replace with actual session variable

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $proof_id = intval($_POST['proof_id']);
    $team_id = intval($_POST['team_id']);
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);

    if (!empty($comment)) {
        $insert = "INSERT INTO comments (proof_id, admin_id, team_id, comment) 
                   VALUES ($proof_id, $admin_id, $team_id, '$comment')";
        mysqli_query($conn, $insert);
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);
    exit();
}

// Fetch team names
$teamQuery = "SELECT DISTINCT teams.team_id, teams.team_name 
              FROM teams JOIN proofs ON teams.team_id = proofs.team_id";
$teamResult = mysqli_query($conn, $teamQuery);

// Fetch project names
$projectQuery = "SELECT DISTINCT projects.id, projects.project_name 
                 FROM projects JOIN proofs ON projects.id = proofs.project_id";
$projectResult = mysqli_query($conn, $projectQuery);

// Apply filters
$whereClause = [];
if (isset($_GET['team_id']) && !empty($_GET['team_id'])) {
    $team_id = intval($_GET['team_id']);
    $whereClause[] = "p.team_id = $team_id";
}
if (isset($_GET['project_id']) && !empty($_GET['project_id'])) {
    $project_id = intval($_GET['project_id']);
    $whereClause[] = "p.project_id = $project_id";
}
$whereSQL = !empty($whereClause) ? "WHERE " . implode(" AND ", $whereClause) : "";

// Fetch proofs
$query = "SELECT p.id, t.team_id, t.team_name, pr.project_name, p.project_id, p.file_name, 
                 p.file_path, p.uploaded_at, p.description 
          FROM proofs p
          JOIN teams t ON p.team_id = t.team_id
          JOIN projects pr ON p.project_id = pr.id
          $whereSQL
          ORDER BY p.uploaded_at DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Reports</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
/* your existing CSS... */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
}

/* Body Layout */
body {
    display: flex;
    min-height: 100vh;
    background: #f4f6f9;
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
    padding: 20px;
    width: calc(100% - 250px);
}

.main-content h2 {
    font-size: 24px;
    margin-bottom: 20px;
    color: #1e3c72;
}

/* Filter Form */
form {
    margin-bottom: 20px;
    background: #fff;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

form label {
    margin-right: 10px;
    font-weight: 500;
}

form select {
    padding: 6px 10px;
    margin-right: 15px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

form button {
    background: #1e3c72;
    color: #fff;
    border: none;
    padding: 7px 15px;
    border-radius: 5px;
    cursor: pointer;
    transition: 0.3s;
}

form button:hover {
    background: #2a5298;
}

/* Table */
table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

table thead {
    background: #11d30bff;
    color: #fff;
}

table thead th {
    padding: 12px;
    text-align: left;
    font-size: 14px;
}

table tbody tr {
    border-bottom: 1px solid #ddd;
    transition: background 0.2s;
}

table tbody tr:hover {
    background: #f1f5fb;
}

table tbody td {
    padding: 12px;
    font-size: 14px;
}

/* Buttons */
.view-btn, .download-btn {
    padding: 6px 12px;
    text-decoration: none;
    border-radius: 5px;
    font-size: 13px;
    transition: 0.3s;
}

.view-btn {
    background: #17a2b8;
    color: #fff;
}

.view-btn:hover {
    background: #138496;
}

.download-btn {
    background: #28a745;
    color: #fff;
}

.download-btn:hover {
    background: #218838;
}

/* Scrollbar Styling (Optional) */
.sidebar::-webkit-scrollbar {
    width: 6px;
}
.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.4);
    border-radius: 10px;
}


.comment-btn {
    background: #ff9800;
    color: #fff;
    padding: 6px 12px;
    border-radius: 5px;
    text-decoration: none;
    cursor: pointer;
    font-size: 13px;
}
.comment-btn:hover {
    background: #e68900;
}

.comment-form {
    display: none;
    margin-top: 10px;
}
.comment-form textarea {
    width: 100%;
    height: 60px;
    padding: 6px;
    border-radius: 5px;
    border: 1px solid #ccc;
    resize: none;
}
.comment-form button {
    margin-top: 5px;
    padding: 5px 12px;
    border: none;
    border-radius: 5px;
    background: #1e3c72;
    color: #fff;
    cursor: pointer;
}
.comment-form button:hover {
    background: #2a5298;
}
</style>
<script>
function toggleCommentForm(id) {
    var form = document.getElementById("comment-form-" + id);
    form.style.display = (form.style.display === "block") ? "none" : "block";
}
</script>
</head>
<body>
<div class="sidebar">
  <!-- sidebar same as before -->
   <div class="logo">Admin<br> Dashboard</div>
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
    <h2>View Submitted Proofs</h2>

    <!-- Filter Form -->
    <form method="GET" action="">
        <label for="team_id">Filter by Team:</label>
        <select name="team_id" id="team_id">
            <option value="">All Teams</option>
            <?php while ($row = mysqli_fetch_assoc($teamResult)) { ?>
                <option value="<?= $row['team_id']; ?>" 
                    <?= (isset($_GET['team_id']) && $_GET['team_id'] == $row['team_id']) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($row['team_name']); ?>
                </option>
            <?php } ?>
        </select>

        <label for="project_id">Filter by Project:</label>
        <select name="project_id" id="project_id">
            <option value="">All Projects</option>
            <?php while ($row = mysqli_fetch_assoc($projectResult)) { ?>
                <option value="<?= $row['id']; ?>" 
                    <?= (isset($_GET['project_id']) && $_GET['project_id'] == $row['id']) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($row['project_name']); ?>
                </option>
            <?php } ?>
        </select>

        <button type="submit">Filter</button>
    </form>

    <!-- Display Proofs -->
    <table>
        <thead>
            <tr>
                <th>Team Name</th>
                <th>Project Name</th>
                <th>Project ID</th>
                <th>File Name</th>
                <th>Description</th>
                <th>Uploaded At</th>
                <th>View</th>
                <th>Download</th>
                <th>Comment</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?= htmlspecialchars($row['team_name']); ?></td>
                    <td><?= htmlspecialchars($row['project_name']); ?></td>
                    <td><?= htmlspecialchars($row['project_id']); ?></td>
                    <td><?= htmlspecialchars($row['file_name']); ?></td>
                    <td><?= htmlspecialchars($row['description']); ?></td>
                    <td><?= htmlspecialchars($row['uploaded_at']); ?></td>
                    <td>
                        <a href="<?= htmlspecialchars($row['file_path']); ?>" target="_blank" class="view-btn">View</a>
                    </td>
                    <td>
                        <a href="<?= htmlspecialchars($row['file_path']); ?>" download="<?= htmlspecialchars($row['file_name']); ?>" class="download-btn">Download</a>
                    </td>
                    <td>
                        <button class="comment-btn" onclick="toggleCommentForm(<?= $row['id']; ?>)">Comment</button>
                        <div class="comment-form" id="comment-form-<?= $row['id']; ?>">
                            <form method="POST" action="">
                                <textarea name="comment" placeholder="Enter your comment..."></textarea>
                                <input type="hidden" name="proof_id" value="<?= $row['id']; ?>">
                                <input type="hidden" name="team_id" value="<?= $row['team_id']; ?>">
                                <button type="submit" name="add_comment">Submit</button>
                            </form>
                        </div>

    <!-- Show existing comments + team replies -->
                        <?php
                        $proof_id = $row['id'];
                        $commentQuery = "SELECT comment_id, comment, created_at FROM comments WHERE proof_id = $proof_id";
                        $commentRes = mysqli_query($conn, $commentQuery);
                        while ($c = mysqli_fetch_assoc($commentRes)) {
                            echo "<div style='margin-top:8px; background:#f1f1f1; padding:6px; border-radius:5px;'>
                                <b>Admin:</b> " . htmlspecialchars($c['comment']) . 
                                "<br><small>" . $c['created_at'] . "</small>";

                            // Show replies
                            $replyQuery = "SELECT reply, created_at FROM comment_replies WHERE comment_id = " . $c['comment_id'];
                            $replyRes = mysqli_query($conn, $replyQuery);
                            while ($r = mysqli_fetch_assoc($replyRes)) {
                                echo "<div style='margin-left:15px; background:#eef7ff; padding:5px; border-radius:5px;'>
                                    <b>Team Reply:</b> " . htmlspecialchars($r['reply']) . 
                                    "<br><small>" . $r['created_at'] . "</small></div>";
                            }

                            echo "</div>";
                        }
                        ?>
                    </td>

                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>



