<?php
include 'config.php';

// Mark as read logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id'])) {
    $id = intval($_POST['mark_read_id']);
    $updateQuery = "UPDATE notifications SET status = 'Read' WHERE id = ?";
    $stmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    header("Location: notifications.php");
    exit();
}

// Fetch unread notifications
$unreadQuery = "SELECT id, message, created_at, status FROM notifications WHERE status = 'Unread' ORDER BY created_at DESC";
$unreadResult = mysqli_query($conn, $unreadQuery);

// Fetch read notifications
$readQuery = "SELECT id, message, created_at, status FROM notifications WHERE status = 'Read' ORDER BY created_at DESC";
$readResult = mysqli_query($conn, $readQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Notifications</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
        }
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
        .main-content {
            margin-left: 260px;
            padding: 20px;
            width: calc(100% - 260px);
            background: #ffffff;
            min-height: 100vh;
        }
        .main-content h2 {
            color: #333;
            border-bottom: 2px solid #1abc9c;
            padding-bottom: 10px;
            margin-top: 40px;
        }
        ul.notification-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        ul.notification-list li {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        ul.notification-list li.unread {
            background: #eaf6ff;
            border-left: 5px solid #007bff;
        }
        ul.notification-list li.read {
            background: #f1f1f1;
            border-left: 5px solid #ccc;
        }
        .mark-read-btn, .delete-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: white;
        }
        .mark-read-btn {
            background: #28a745;
        }
        .delete-btn {
            background: #dc3545;
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

<div class="main-content">
    <h2>New Messages</h2>
    <ul class="notification-list" id="new-messages">
        <?php while ($row = mysqli_fetch_assoc($unreadResult)) { ?>
            <li class="unread" data-id="<?= $row['id']; ?>">
                <div><?= htmlspecialchars($row['message']); ?> <small>(<?= $row['created_at']; ?>)</small></div>
                <div>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="mark_read_id" value="<?= $row['id']; ?>">
                        <button type="submit" class="mark-read-btn">Mark as Read</button>
                    </form>
                    <button type="button" class="delete-btn">Delete</button>
                </div>
            </li>
        <?php } ?>
    </ul>

    <h2>Old Messages</h2>
    <ul class="notification-list" id="old-messages">
        <?php while ($row = mysqli_fetch_assoc($readResult)) { ?>
            <li class="read" data-id="<?= $row['id']; ?>">
                <div><?= htmlspecialchars($row['message']); ?> <small>(<?= $row['created_at']; ?>)</small></div>
                <div>
                    <button type="button" class="delete-btn">Delete</button>
                </div>
            </li>
        <?php } ?>
    </ul>
</div>

<script>
    // Delete from UI only
    document.querySelectorAll(".delete-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            this.closest("li").remove();
        });
    });
</script>
</body>
</html>
