<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Redirect to the login page
header("Location: home.html"); // Change to 'team_login.php' for team logout
exit();
?>
