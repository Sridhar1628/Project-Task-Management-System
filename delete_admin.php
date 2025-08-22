<?php
require 'config.php';
session_start();

$id = $_GET['id'];

$stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    echo "<script>alert('Admin deleted successfully!');window.location.href='manage_admins.php';</script>";
} else {
    echo "<script>alert('Failed to Delete!');window.location.href='manage_admins.php';</script>";;
}
exit();
?>
