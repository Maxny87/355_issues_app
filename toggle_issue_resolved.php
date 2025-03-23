<?php
session_start();
require_once './database/database.php';

if (!isset($_SESSION['user_id'])) { // making sure the user is logged in
    header("Location: login2.php");
    exit();
}

if (!isset($_POST['id']) || !isset($_POST['current_status'])) { // getting current status of the issue
    die("Invalid request.");
}

$issue_id = $_POST['id'];
$current_status = $_POST['current_status']; // 1 or 0

$conn = new PDO($connstring, $db_user, $db_pass);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch issue to verify access
$stmt = $conn->prepare("SELECT * FROM iss_issues WHERE id = :id");
$stmt->execute(['id' => $issue_id]);
$issue = $stmt->fetch(PDO::FETCH_ASSOC);

if (
    $_SESSION['user_id'] != $issue['per_id'] &&
    $_SESSION['admin'] != 'Y'
) {
    die("Unauthorized.");
}

// Toggle logic
$new_status = $current_status ? 0 : 1;
$new_close_date = $new_status ? 'CURDATE()' : "'0000-00-00'";

$stmt = $conn->prepare("UPDATE iss_issues SET resolved = :resolved, close_date = $new_close_date WHERE id = :id");
$stmt->execute(['resolved' => $new_status, 'id' => $issue_id]);

header("Location: issues_list.php");
exit;
?>