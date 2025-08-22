<?php
require'config.php';

// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['team_id'])) {
    header("Location: teamlogin.php");
    exit();
}

$team_id = $_SESSION['team_id'];
$team_name = $_SESSION['team_name']; // Stored during login
$uploadError = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_proof'])) {
    $project_id = $_POST['project_id'];
    $description = $_POST['description'];
    $uploadDir = "uploads/";
    $file = $_FILES['proof_file'];

    // Validate file size (limit: 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        $uploadError = "File size must be less than 2MB.";
    } else {
        $fileName = basename($file['name']);
        $filePath = $uploadDir . $fileName;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Insert data into the database
            $stmt = $conn->prepare("INSERT INTO proofs (team_id, project_id, file_name, file_path, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisss", $team_id, $project_id, $fileName, $filePath, $description);

            if ($stmt->execute()) {
                // Redirect to prevent form resubmission
                echo "<script>alert('Proof submitted successfully!'); window.location.href='submit_proof.php';</script>";
                $stmt->close();
                exit();
            } else {
                $uploadError = "Failed to save proof in the database.";
            }
            $stmt->close();
        } else {
            $uploadError = "Failed to upload the file.";
        }
    }
}

// Handle row deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    // Delete file entry and its uploaded file
    $query = $conn->prepare("SELECT file_path FROM proofs WHERE id = ?");
    $query->bind_param("i", $delete_id);
    $query->execute();
    $query->bind_result($filePath);
    if ($query->fetch()) {
        unlink($filePath); // Delete the file from the server
    }
    $query->close();

    // Remove from database
    $deleteStmt = $conn->prepare("DELETE FROM proofs WHERE id = ?");
    $deleteStmt->bind_param("i", $delete_id);
    $deleteStmt->execute();
    $deleteStmt->close();

    // Redirect to avoid resubmission
    header("Location: submit_proof.php");
    exit();
}

// Fetch assigned projects for the logged-in team
$projectsQuery = $conn->prepare("
    SELECT p.id, p.project_name 
    FROM projects p
    JOIN assigned_projects ap ON p.id = ap.project_id
    WHERE ap.team_id = ?
");
$projectsQuery->bind_param("i", $team_id);
$projectsQuery->execute();
$projectsResult = $projectsQuery->get_result();
$projectsQuery->close();

// Fetch submitted proofs
$proofsQuery = $conn->prepare("
    SELECT pr.id, p.project_name, pr.file_name, pr.description, pr.uploaded_at, pr.file_path
    FROM proofs pr
    JOIN projects p ON pr.project_id = p.id
    WHERE pr.team_id = ?
");
$proofsQuery->bind_param("i", $team_id);
$proofsQuery->execute();
$proofsResult = $proofsQuery->get_result();
$proofsQuery->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Submit Proof</title>
    <style>
       /* General Styles */
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f4;
}

/* Sidebar Styles */
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


/* Main Container */
/* General Styles */
body {
    font-family: "Segoe UI", Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #eef2f7;
    color: #333;
}

/* Main Container */
.container {
    margin-left: 280px; /* same width as sidebar */
    padding: 25px;
}

.container h2 {
    font-size: 26px;
    margin-bottom: 20px;
    color: #2c3e50;
}

.container h3 {
    font-size: 22px;
    margin-top: 40px;
    margin-bottom: 15px;
    color: #34495e;
}

/* Form Styles */
form {
    background: #fff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0px 2px 10px rgba(0,0,0,0.08);
    max-width: 950px;
}

form div {
    margin-bottom: 18px;
}

form label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    color: #2c3e50;
}

form select,
form textarea,
form input[type="file"] {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccd1d9;
    border-radius: 6px;
    font-size: 15px;
    background-color: #fafafa;
    transition: border 0.3s;
}

form select:focus,
form textarea:focus,
form input[type="file"]:focus {
    outline: none;
    border-color: #2980b9;
}

/* Button Styles */
button {
    display: inline-block;
    width: 100%;
    padding: 12px;
    background: #2980b9;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease;
}

button:hover {
    background: #1a5276;
}

button[type="button"] {
    background-color: #e74c3c;
    margin-top: 12px;
}

button[type="button"]:hover {
    background-color: #c0392b;
}

/* Error Message */
.error {
    color: #e74c3c;
    font-weight: bold;
    margin-top: 5px;
}

/* Table Styles */
table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    margin-top: 20px;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0px 2px 8px rgba(0,0,0,0.08);
}

th, td {
    padding: 12px 15px;
    text-align: center;
    border-bottom: 1px solid #e0e0e0;
    font-size: 15px;
}

th {
    background: #34495e;
    color: #fff;
    font-size: 16px;
    font-weight: 600;
}

tr:hover {
    background-color: #f9f9f9;
}

td a {
    color: #2980b9;
    text-decoration: none;
    font-weight: 500;
}

td a:hover {
    text-decoration: underline;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        margin-left: 0;
        padding: 15px;
    }

    table, th, td {
        font-size: 13px;
    }

    form {
        max-width: 100%;
    }
}
    </style>
</head>
<body>
<div class="sidebar">
<div class="logo">Team<br> Dashboard</div>
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
    <div class="container">
        <h2>Submit Proof for Projects</h2>

        <!-- Form for submitting proof -->
        <form action="submit_proof.php" method="POST" enctype="multipart/form-data">
            <div>
                <label for="project_id">Project Name:</label>
                <select name="project_id" id="project_id" required>
                    <option value="">Select a project</option>
                    <?php while ($project = $projectsResult->fetch_assoc()): ?>
                        <option value="<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['project_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label for="description">Description:</label>
                <textarea name="description" id="description" rows="3" required></textarea>
            </div>

            <div>
                <label for="proof_file">Upload Proof (Images/PDF, Max: 2MB):</label>
                <input type="file" name="proof_file" id="proof_file" accept="image/*,application/pdf" required>
            </div>

            <?php if ($uploadError): ?>
                <p class="error"><?php echo $uploadError; ?></p>
            <?php endif; ?>

            <button type="submit" name="submit_proof">Submit Proof</button><br><br>
            <a href="mno.php"><button type="button" style="background-color:red">Cancel</button></a>
        </form>

        <h3>Submitted Proofs</h3>

        <!-- Table displaying submitted proofs -->
        <table>
            <thead>
                <tr>
                    <th>Project Name</th>
                    <th>Description</th>
                    <th>File</th>
                    <th>Uploaded At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($proofsResult->num_rows > 0): ?>
                    <?php while ($proof = $proofsResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($proof['project_name']); ?></td>
                            <td><?php echo htmlspecialchars($proof['description']); ?></td>
                            <td>
                                <!-- View the file -->
                                <a href="<?php echo 'http://localhost/Project-Task-Management-System/' . htmlspecialchars($proof['file_path']); ?>" target="_blank">View File</a>
                            </td>
                            <td><?php echo htmlspecialchars($proof['uploaded_at']); ?></td>
                            <td>
                                <a href="submit_proof.php?delete_id=<?php echo $proof['id']; ?>" onclick="return confirm('Are you sure you want to delete this proof?')">Remove</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No proofs submitted yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php $conn->close(); ?>
