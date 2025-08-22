<?php
require_once 'config.php';

$conn = getMySQLConnection();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database configuration
    $teamName = $_POST['teamName'];
    $teamUserId = $_POST['teamUserId'];
    $teamPassword = password_hash($_POST['teamPassword'], PASSWORD_BCRYPT);

    $leaderName = $_POST['leaderName'];
    $leaderUserId = $_POST['leaderUserId'];
    $leaderPassword = password_hash($_POST['leaderPassword'], PASSWORD_BCRYPT);
    $leaderEmail = $_POST['leaderEmail'];
    $leaderPhone = $_POST['leaderPhone'];

    $conn->begin_transaction();

    try {
        // Insert team
        $stmt = $conn->prepare("INSERT INTO teams (team_name, team_user_id, team_password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $teamName, $teamUserId, $teamPassword);
        $stmt->execute();
        $teamId = $conn->insert_id;
        $stmt->close();

        // Insert leader
        $stmt = $conn->prepare("INSERT INTO team_leaders (team_id, team_name, leader_name, leader_user_id, leader_password, leader_email, leader_phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $teamId, $teamName, $leaderName, $leaderUserId, $leaderPassword, $leaderEmail, $leaderPhone);
        $stmt->execute();
        $stmt->close();

        // Insert members
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'memberName') === 0) {
                $i = str_replace('memberName', '', $key);
                $memberName = $_POST['memberName' . $i];
                $memberEmail = $_POST['memberEmail' . $i];
                $memberPhone = $_POST['memberPhone' . $i];
                $memberPassword = password_hash($_POST['memberPassword' . $i], PASSWORD_BCRYPT);

                $stmt = $conn->prepare("INSERT INTO team_members (team_id, team_name, member_name, member_email, member_phone, member_password) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssss", $teamId, $teamName, $memberName, $memberEmail, $memberPhone, $memberPassword);
                $stmt->execute();
            }
        }

        $conn->commit();
        echo "<script>alert('Registration successful!'); window.location.href='adteam.php';</script>";
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            background-color: lightblue;
            min-height : 100vh;
            box-sizing: border-box;
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

        .form-container {
            width : 50%;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            max-height : 90vh;
            overflow-y: auto;
        }
        .form-step { display: none; }
        .form-step.active { display: block; }
        label { display: block; margin-bottom : 8px; font-weight: bold; }
        input {
            width : 100%; padding: 8px; margin-bottom : 10px;
            border: 1px solid #ccc; border-radius: 4px;
        }
        button {
            padding: 10px 20px; margin: 10px 0;
            border: none; border-radius: 4px;
            cursor: pointer; background-color: #007bff; color: white;
        }
        button:hover { background-color: #0056b3; }
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
    <div class="form-container">
        <form method="POST">
            <!-- Step 1 -->
            <div class="form-step active" id="step-1">
                <h2>Step 1: Team Details</h2>
                <label for="teamName">Team Name:</label>
                <input type="text" id="teamName" name="teamName" required>
                <label for="teamUserId">Team User ID:</label>
                <input type="text" id="teamUserId" name="teamUserId" required>
                <label for="teamPassword">Password:</label>
                <input type="password" id="teamPassword" name="teamPassword" placeholder="Enter password"
                    pattern="^(?=.*[A-Z])(?=.*[@$!%*?&])(?=.*[0-9]).{8,}$"
                    title="Password must contain at least one uppercase letter, one special character, one number, and be at least 8 characters long" required>
                <label for="memberCount">Number of Members:</label>
                <input type="number" id="memberCount" name="memberCount" min="1" required oninput="generateMembers()">
                <button type="button" onclick="nextStep(2)">Next</button>
                <a href="adteam.php" style="text-decoration:none;"><button type="button" style="background-color:red;">Cancel</button></a>

            </div>

            <!-- Step 2 -->
            <div class="form-step" id="step-2">
                <h2>Step 2: Leader Account Details</h2>
                <label for="leaderName">Leader Name:</label>
                <input type="text" id="leaderName" name="leaderName" required>
                <label for="leaderUserId">Leader User ID:</label>
                <input type="text" id="leaderUserId" name="leaderUserId" required>
                <label for="leaderPassword">Leader Password:</label>
                <input type="password" id="leaderPassword" name="leaderPassword" placeholder="Enter password" pattern="^(?=.*[A-Z])(?=.*[@$!%*?&])(?=.*[0-9]).{8,}$"
                    title="Password must contain at least one uppercase letter, one special character, one number, and be at least 8 characters long" required>
                <label for="leaderEmail">Leader Email:</label>
                <input type="email" id="leaderEmail" name="leaderEmail" required>
                <label for="leaderPhone">Leader Phone:</label>
                <input type="text" id="leaderPhone" name="leaderPhone" pattern="[0-9]{10}" placeholder="10-digit number" title="Enter 10 digit number" required>
                <button type="button" onclick="prevStep(1)">Previous</button>
                <button type="button" onclick="nextStep(3)">Next</button>
            </div>

            <!-- Step 3 -->
            <div class="form-step" id="step-3">
                <h2>Step 3: Member Details</h2>
                <div id="memberFields"></div>
                <button type="button" onclick="prevStep(2)">Previous</button>
                <button type="submit">Register</button>
            </div>
        </form>
    </div>

    <script>
        let currentStep = 1;
        function nextStep(step) {
            document.getElementById('step-' + currentStep).classList.remove('active');
            currentStep = step;
            document.getElementById('step-' + currentStep).classList.add('active');
        }
        function prevStep(step) {
            document.getElementById('step-' + currentStep).classList.remove('active');
            currentStep = step;
            document.getElementById('step-' + currentStep).classList.add('active');
        }
        function generateMembers() {
            let count = parseInt(document.getElementById('memberCount').value);
            let memberFields = document.getElementById('memberFields');
            memberFields.innerHTML = '';
            if (isNaN(count) || count < 1) return;
            for (let i = 1; i <= count; i++) {
                let memberDiv = document.createElement('div');
                memberDiv.classList.add('member');
                memberDiv.innerHTML =
                    `<h3>Member ${i}</h3>
                    <label for="memberName${i}">Name:</label>
                    <input type="text" id="memberName${i}" name="memberName${i}" required>
                    <label for="memberEmail${i}">Email:</label>
                    <input type="email" id="memberEmail${i}" name="memberEmail${i}" required>
                    <label for="memberPhone${i}">Phone:</label>
                    <input type="text" id="memberPhone${i}" name="memberPhone${i}" pattern="[0-9]{10}" title="Enter 10 digit number" placeholder="10-digit number" required>
                    <label for="memberPassword${i}">Password:</label>
                    <input type="password" id="memberPassword${i}" name="memberPassword${i}" 
                        pattern="^(?=.*[A-Z])(?=.*[@$!%*?&])(?=.*[0-9]).{8,}$"
                        title="Password must contain at least one uppercase letter, one special character, one number, and be at least 8 characters long" required>`;
                memberFields.appendChild(memberDiv);
            }
        }
    </script>
</body>
</html>
