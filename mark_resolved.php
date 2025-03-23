<?php
require_once './database/database.php';
session_start();
// when we want to resolve an issue from the issue_details page

if (!isset($_SESSION['user_id'])) { // making sure the user is logged in
    header("Location: login2.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    // marking the current issue as resolved we are given the id of issue in post
    $conn = new PDO($connstring, $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("UPDATE iss_issues SET close_date = CURDATE(), resolved = 1 WHERE id = :id");
    $stmt->execute(['id' => $_POST['id']]);

    header("Location: issues_list.php");
    exit;
}
?>