<?php
session_start();
require_once './database/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login2.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['comment_id'], $_POST['short_comment'], $_POST['long_comment'])) {
    header("Location: issue_details.php?id=" . $issue_id);
    exit();
}

$comment_id = $_POST['comment_id'];
$issue_id = $_POST['iss_id'];

$conn = new PDO($connstring, $db_user, $db_pass);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// fetch comment to check ownership
$stmt = $conn->prepare("SELECT per_id FROM iss_comments WHERE id = ?");
$stmt->execute([$comment_id]);
$comment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$comment) {
    die("Comment not found.");
}

if ($_SESSION['user_id'] != $comment['per_id'] && $_SESSION['admin'] !== 'Y') {
    header("Location: issue_details.php?id=" . $issue_id);
    exit();
}

// update comment
$stmt = $conn->prepare("UPDATE iss_comments SET short_comment = ?, long_comment = ? WHERE id = ?");
$stmt->execute([
    $_POST['short_comment'],
    $_POST['long_comment'],
    $comment_id
]);

header("Location: issue_details.php?id=" . $issue_id);
exit;
?>