<?php
session_start();
require_once './database/database.php';

if (!isset($_SESSION['user_id'])) { // making sure the user is logged in
    header("Location: login2.php");
    exit();
}

if ($_SESSION['admin'] !== 'Y') die("Access denied."); // need to be admin to delete a user
if (!isset($_POST['id'])) die("User ID not provided.");

$conn = new PDO($connstring, $db_user, $db_pass);
$stmt = $conn->prepare("DELETE FROM iss_persons WHERE id = ?");
$stmt->execute([$_POST['id']]);

header("Location: list_users.php");
exit;
?>