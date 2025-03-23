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

// making sure we get the id of the issue we want to delete in POST
if (!isset($_POST['id'])) {
    die("Issue ID not provided.");
}

$issue_id = $_POST['id']; // issue to delete

// database connecting
$conn = new PDO($connstring, $db_user, $db_pass);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// fetch issue to verify ownership
$stmt = $conn->prepare("SELECT per_id FROM iss_issues WHERE id = :id");
$stmt->execute(['id' => $issue_id]); // getting the issue
$issue = $stmt->fetch(PDO::FETCH_ASSOC); // the result is the person id of the issue we want to delete

// if there is no issue then the issue does not exist
if (!$issue) {
    die("Issue not found.");
}

if ($_SESSION['user_id'] != $issue['per_id'] && $_SESSION['admin'] !== 'Y') {
    die("Unauthorized.");
}

// first delete all comments related to the issue
$stmt = $conn->prepare("DELETE FROM iss_comments WHERE iss_id = :id");
$stmt->execute(['id' => $issue_id]);

// then delete the issue itself
$stmt = $conn->prepare("DELETE FROM iss_issues WHERE id = :id");
$stmt->execute(['id' => $issue_id]);

header("Location: issues_list.php");
exit;
?>