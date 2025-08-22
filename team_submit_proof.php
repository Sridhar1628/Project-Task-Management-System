<?php
session_start();
require 'config.php'; // DB connection

// Check login
if (!isset($_SESSION['member_id']) || !isset($_SESSION['team_id'])) {
    header("Location: member_login.php");
    exit();
}

$member_id = $_SESSION['member_id'];
$team_id   = $_SESSION['team_id'];
$message   = "";

// Fetch tasks assigned to this member
$query = "SELECT t.task_id, t.task_description, p.id AS project_id, p.project_name
          FROM tasks1 t
          JOIN projects p ON t.project_id = p.id
          WHERE t.member_id = ? AND t.status IN ('In Progress')";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$tasks = $stmt->get_result();

// Handle proof upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_proof'])) {
    $task_id = intval($_POST['task_id']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    // Get project_id from task
    $projQuery = "SELECT project_id FROM tasks1 WHERE task_id = ? AND member_id = ?";
    $projStmt = $conn->prepare($projQuery);
    $projStmt->bind_param("ii", $task_id, $member_id);
    $projStmt->execute();
    $projResult = $projStmt->get_result();
    $projRow = $projResult->fetch_assoc();
    $project_id = $projRow['project_id'];

    // File upload handling
    if (!empty($_FILES['proof_file']['name'])) {
        $allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        $fileExt = strtolower(pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION));

        // File size in bytes (8 MB = 8 * 1024 * 1024)
        $maxSize = 8 * 1024 * 1024;

        if (!in_array($fileExt, $allowedTypes)) {
            echo "<script>alert('❌ Invalid file type! Only PDF, DOC, DOCX, JPG, JPEG, PNG allowed.');</script>";
            echo "<script>window.location.href = 'team_submit_proof.php';</script>";
        } elseif ($_FILES['proof_file']['size'] > $maxSize) {
            echo "<script>alert('❌ File size exceeds 8 MB limit!');</script>";
            echo "<script>window.location.href = 'team_submit_proof.php';</script>";
        } else {
        // Path to your folder inside htdocs
            $targetDir = __DIR__ . "/team_uploads/";  
            
            // Create folder if not exists
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $fileName = basename($_FILES["proof_file"]["name"]);
            $targetFilePath = $targetDir . $fileName;

            if (move_uploaded_file($_FILES["proof_file"]["tmp_name"], $targetFilePath)) {
                // Insert into proofs1 table
                $insert = "INSERT INTO proofs1 (member_id, task_id, project_id, proof_file, description) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert);
                $stmt->bind_param("iiiss", $member_id, $task_id, $project_id, $fileName, $description);
                if ($stmt->execute()) {
                    echo "<script>alert('✅ Proof uploaded successfully!');</script>";
                    echo "<script>window.location.href = 'team_submit_proof.php';</script>";
                } else {
                    echo "<script>alert('❌ Error uploading proof. Please try again.');</script>";
                    echo "<script>window.location.href = 'team_submit_proof.php';</script>";
                }
            } else {
                echo "<script>alert('❌ Error upload file. Please try again.');</script>";
                echo "<script>window.location.href = 'team_submit_proof.php';</script>";
                exit();
            }
        }
    }
    else {
        echo "<script>alert('❌ Please select a file to upload.');</script>";
        echo "<script>window.location.href = 'team_submit_proof.php';</script>";    
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Proof</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* Reset styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
}

/* Sidebar */
.sidebar {
    width: 260px;
    background: linear-gradient(180deg, #2c3e50, #000);
    color: #fff;
    padding: 25px 0;
    position: fixed;
    height: 100%;
    box-shadow: 2px 0 8px rgba(0,0,0,0.25);
}

.sidebar .logo {
    text-align: center;
    font-size: 26px;
    font-weight: bold;
    padding: 20px;
    margin-bottom: 30px;
    border-bottom: 1px solid rgba(255,255,255,0.25);
    color: #ff7f50;
}

.sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar ul li {
    margin: 12px 0;
}

.sidebar ul li a {
    display: flex;
    align-items: center;
    font-size: 16px;
    color: #ddd;
    padding: 12px 22px;
    text-decoration: none;
    transition: 0.3s;
    border-left: 4px solid transparent;
}

.sidebar ul li a i {
    margin-right: 12px;
    font-size: 18px;
}

.sidebar ul li a:hover {
    background: rgba(255, 127, 80, 0.2);
    border-left: 4px solid #ff7f50;
    color: #fff;
}

/* Main container */
.container {
    margin-left: 280px; /* match sidebar width */
    padding: 40px;
    max-width: calc(100% - 280px); /* full width minus sidebar */
}

.container h2 {
    text-align: center;
    color: #62ff72ff;
    margin-bottom: 30px;
    font-size: 28px;
    font-weight: bold;
}

/* Form */
form {
    background: #99e4efff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0px 4px 12px rgba(0,0,0,0.15);
    width: 100%;   /* make form stretch */
}

label {
    display: block;
    font-weight: 600;
    margin: 14px 0 6px;
    color: #444;
    font-size: 15px;
}

select, input[type="file"], textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    margin-bottom: 18px;
    font-size: 15px;
    transition: border 0.3s, box-shadow 0.3s;
}

select:focus, input[type="file"]:focus, textarea:focus {
    border-color: #3d36a4;
    outline: none;
    box-shadow: 0px 0px 6px rgba(61, 54, 164, 0.4);
}

/* Button */
button {
    display: inline-block;
    background: #3d36a4;
    color: white;
    border: none;
    padding: 14px 20px;
    font-size: 17px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.3s ease;
    width: 100%;
    font-weight: bold;
    letter-spacing: 0.5px;
}

button:hover {
    background: #292381;
}

/* Messages */
.message {
    text-align: center;
    margin-bottom: 20px;
    font-weight: bold;
    color: green;
    font-size: 16px;
}

/* Responsive */
@media (max-width: 900px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }
    .container {
        margin-left: 0;
        max-width: 100%;
        padding: 20px;
    }
}

    </style>
</head>
<body style="background: linear-gradient(to right, #2c3e50, #3498db);" >
    <div class="sidebar">
        <div class="logo">Member<br>Dashboard</div>
                <ul>
                    <li><a href="member_dashboard.php"><i class="fas fa-tachometer-alt"></i> Home</a></li>
                    <li><a href="team_submit_proof.php"><i class="fas fa-users"></i>Submit Proofs</a></li>
                    <li><a href="mem_completed_tasks.php"><i class="fas fa-check-circle"></i> Completed Tasks</a></li>
                    <li><a href="teamdashboard.php"><i class="fas fa-home"></i>Team Dashboard</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
        </div>
    </div>
    <div class="container">
        <h2>Upload Task Proof</h2>
        <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>

        <form method="POST" enctype="multipart/form-data">
            <label for="task_id">Select Task:</label>
            <select name="task_id" required>
                <option value="">-- Select Task --</option>
                <?php while($row = $tasks->fetch_assoc()) { ?>
                    <option value="<?= $row['task_id'] ?>">
                        <?= htmlspecialchars($row['project_name']) ?> - <?= htmlspecialchars($row['task_description']) ?>
                    </option>
                <?php } ?>
            </select>

            <label for="proof_file">Upload File:</label>
            <input type="file" name="proof_file" required>

            <label for="description">Description:</label>
            <textarea name="description" placeholder="Add a short note..." required></textarea>

            <button type="submit" name="upload_proof">Upload</button>
        </form>
    </div>
</body>
</html>
