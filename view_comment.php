<?php
include 'config.php';
session_start();
$team_id = $_SESSION['team_id'];

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_reply'])) {
    $comment_id = intval($_POST['comment_id']);
    $reply = mysqli_real_escape_string($conn, $_POST['reply']);

    if (!empty($reply)) {
        $insert = "INSERT INTO comment_replies (comment_id, team_id, reply) 
                   VALUES ($comment_id, $team_id, '$reply')";
        mysqli_query($conn, $insert);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch team’s proofs with comments + replies
$query = "SELECT c.comment_id, p.file_name, c.comment, c.created_at AS comment_time 
          FROM comments c 
          JOIN proofs p ON c.proof_id = p.id 
          WHERE c.team_id = $team_id 
          ORDER BY c.created_at DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html>
<head>
<title>Team Dashboard - Comments</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
  /* Reset & Base */
/* Reset & Base */
body {
    margin: 0;
    font-family: "Segoe UI", sans-serif;
    background: #f8faff;
    color: #333;
    display: flex;
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


/* Main Content */
main {
    flex: 1;
    padding: 30px;
    margin-left: 280px;  /* Match sidebar width */
}
main h2 {
    font-size: 30px;
    margin-bottom: 25px;
    color: #860151ff;
}

/* Comment Box */
.comment-box {
    background: #ffffff;
    border: 1px solid #e0e6f1;
    padding: 18px;
    margin-bottom: 22px;
    border-radius: 10px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.comment-box:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 14px rgba(0,0,0,0.15);
}
.comment-box strong {
    color: #2c3e50;
}
.comment-box small {
    color: #777;
    font-size: 13px;
}

/* Highlight Proof Name */
.comment-box strong:first-child {
    color: #8e2de2;
    font-weight: bold;
}

/* Replies */
.reply {
    margin-left: 25px;
    background: #f1f4ff;
    padding: 12px;
    border-radius: 8px;
    margin-top: 10px;
    font-size: 14px;
    border-left: 4px solid #4a00e0;
}

/* Reply Form */
.reply-form textarea {
    width: 100%;
    height: 65px;
    margin-top: 10px;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
    resize: none;
    font-size: 14px;
    font-family: "Segoe UI", sans-serif;
}
.reply-form button {
    margin-top: 10px;
    background: #4a00e0;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 500;
    transition: background 0.3s;
}
.reply-form button:hover {
    background: #3710b7;
}
.view-btn {
    display: inline-block;
    margin-left: 10px;
    background: #28a745;
    color: white;
    padding: 5px 12px;
    font-size: 14px;
    border-radius: 5px;
    text-decoration: none;
    transition: background 0.3s ease;
}
.view-btn:hover {
    background: #1e7e34;
}


</style>
</head>
<body>
    <div class="sidebar">
<div class="logo">Team <br>Dashboard</div>
        <ul>
            <li><a href="teamdashboard.php"><i class="fas fa-tachometer-alt"></i> Home</a></li>
            <li><a href="team_members.php"><i class="fas fa-users"></i> Team Members</a></li>
            <li><a href="update_status.php"><i class="fas fa-edit"></i> Update Status</a></li>
            <li><a href="submit_proof.php"><i class="fas fa-file-upload"></i> Submit Proof</a></li>
            <li><a href="view_comment.php"><i class="fas fa-file-upload"></i> Discussion</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
</div>
</div>
<main>
<h2>Admin Comments & Your Responses</h2>

<?php while ($row = mysqli_fetch_assoc($result)) { ?>
    <div class="comment-box">
        <strong>Proof:</strong> <?= htmlspecialchars($row['file_name']); ?>
        <!-- View Proof Button -->
        <a class="view-btn" href="uploads/<?= urlencode($row['file_name']); ?>" target="_blank">View Proof</a>
        <br>

        <strong>Admin Comment:</strong> <?= htmlspecialchars($row['comment']); ?><br>
        <small>At: <?= htmlspecialchars($row['comment_time']); ?></small>

        <!-- Fetch replies for this comment -->
        <?php
        $comment_id = $row['comment_id'];
        $repliesQuery = "SELECT reply, created_at FROM comment_replies 
                     WHERE comment_id = $comment_id ORDER BY created_at ASC";
        $repliesResult = mysqli_query($conn, $repliesQuery);
        while ($reply = mysqli_fetch_assoc($repliesResult)) {
            echo "<div class='reply'><b>Your Reply:</b> " . htmlspecialchars($reply['reply']) . 
                 "<br><small>At: " . htmlspecialchars($reply['created_at']) . "</small></div>";
        }
        ?>

        <!-- Reply form -->
        <form method="POST" class="reply-form">
            <textarea name="reply" style="width:98%;" placeholder="Write your response..."></textarea>
            <input type="hidden" name="comment_id" value="<?= $comment_id; ?>">
            <button type="submit" name="add_reply">Send Reply</button>
        </form>
    </div>

<?php } ?>
</main>
</body>
</html>
