<?php
session_start();
require_once './database/database.php';

if (!isset($_SESSION['user_id'])) { // making sure the user is logged in
    header("Location: login2.php");
    exit();
}

if (!isset($_GET['id'])) die("Comment ID not specified.");
$comment_id = $_GET['id'];

$conn = new PDO($connstring, $db_user, $db_pass);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// fetch comment
$stmt = $conn->prepare("SELECT * FROM iss_comments WHERE id = :id");
$stmt->execute(['id' => $comment_id]);
$comment = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SESSION['user_id'] != $comment['per_id'] && $_SESSION['admin'] != 'Y') { // only the comment creator can edit it or an admin
    die("Unauthorized access.");
}

// editing the comment when form is submitted from this page and we send them back to the issue details page for this issue
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $conn->prepare("UPDATE iss_comments SET short_comment = :short, long_comment = :long WHERE id = :id");
    $stmt->execute([
        'short' => $_POST['short_comment'],
        'long' => $_POST['long_comment'],
        'id' => $comment_id
    ]);
    header("Location: issue_details.php?id=" . $comment['iss_id']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Comment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
<h2>Edit Comment</h2>
<form method="post">
    <div class="mb-3">
        <label>Short Comment</label>
        <input type="text" name="short_comment" value="<?= htmlspecialchars($comment['short_comment']) ?>" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Long Comment</label>
        <textarea name="long_comment" class="form-control" required><?= htmlspecialchars($comment['long_comment']) ?></textarea>
    </div>
    <button type="submit" class="btn btn-success">Update Comment</button>
    <a href="issue_details.php?id=<?= $comment['iss_id'] ?>" class="btn btn-secondary">Cancel</a>
</form>
</body>
</html>