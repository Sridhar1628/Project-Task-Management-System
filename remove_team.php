<?php
require_once 'config.php'; // DB connection
// Handle team removal
if (isset($_POST['remove_team'])) {
    $team_id = $_POST['team_id'];

    // Just delete from teams; cascading will handle related rows
    $remove_team_query = "DELETE FROM teams WHERE team_id = ?";
    $stmt = $conn->prepare($remove_team_query);
    $stmt->bind_param("i", $team_id);

    if ($stmt->execute()) {
        // Redirect after successful deletion
        header("Location: adteam.php");
        exit;

    } else {
        echo "Error deleting team: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
