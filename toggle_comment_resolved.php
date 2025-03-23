<?php
session_start();
require_once './database/database.php';

if (!isset($_SESSION['user_id'])) { // making sure the user is logged in
    header("Location: login2.php");
    exit();
}

if (!isset($_POST['id']) || !isset($_POST['current_status'])) {
    die("Invalid request.");
}

$comment_id = $_POST['id'];
$current_status = $_POST['current_status']; // 1 or 0 - current value of resolved or not

$conn = new PDO($connstring, $db_user, $db_pass);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $conn->prepare("SELECT * FROM iss_comments WHERE id = :id");
$stmt->execute(['id' => $comment_id]);
$comment = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SESSION['user_id'] != $comment['per_id'] && $_SESSION['admin'] != 'Y') {
    // if they are not the owner and they are not the admin
    die("Unauthorized.");
}

$new_status = $current_status ? 0 : 1; // setting new status

$stmt = $conn->prepare("UPDATE iss_comments SET resolved = :resolved WHERE id = :id");
$stmt->execute(['resolved' => $new_status, 'id' => $comment_id]);

header("Location: issue_details.php?id=" . $comment['iss_id']);
exit;
?>