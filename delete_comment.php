<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once './database/database.php';

if (!isset($_SESSION['user_id'])) { // making sure the user is logged in
    header("Location: login2.php");
    exit();
}

if (!isset($_POST['id'])) { // making sure we get the id of the comment
    die("Comment ID not provided.");
}

$comment_id = $_POST['id'];

// db connection
$conn = new PDO($connstring, $db_user, $db_pass);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// fetch comment to verify access
$stmt = $conn->prepare("SELECT per_id, iss_id FROM iss_comments WHERE id = :id");
$stmt->execute(['id' => $comment_id]);
$comment = $stmt->fetch(PDO::FETCH_ASSOC); // result is the person who created the comment and also the issue its for

if (!$comment) { // comment not in db
    die("Comment not found.");
}

// making sure access is only for an admin or comment owner
if (!isset($_SESSION['user_id']) || ($_SESSION['user_id'] != $comment['per_id'] && $_SESSION['admin'] !== 'Y')) {
    die("Unauthorized.");
}

// delete the comment
$stmt = $conn->prepare("DELETE FROM iss_comments WHERE id = :id");
$stmt->execute(['id' => $comment_id]);

header("Location: issue_details.php?id=" . $comment['iss_id']); // sending them back to the issue's details page the comment was for
exit;
?>