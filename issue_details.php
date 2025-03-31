<?php
session_start();
require_once './database/database.php';

if (!isset($_SESSION['user_id'])) { // making sure the user is logged in
    header("Location: login2.php");
    exit();
}

if (!isset($_GET['id'])) die("Issue ID not specified.");

$issue_id = $_GET['id'];

$conn = new PDO($connstring, $db_user, $db_pass);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// handle comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['short_comment'], $_POST['long_comment'])) {
    // creating a new comment when they press add comment button
    $stmt = $conn->prepare("INSERT INTO iss_comments (per_id, iss_id, short_comment, long_comment, posted_date, resolved)
                            VALUES (:per_id, :iss_id, :short, :long, CURDATE(), 0)");
    $stmt->execute([
        'per_id' => $_SESSION['user_id'] ?? 0,
        'iss_id' => $issue_id,
        'short' => $_POST['short_comment'],
        'long' => $_POST['long_comment']
    ]);
    header("Location: issue_details.php?id=" . $issue_id);
    exit;
}

// fetch issue
$stmt = $conn->prepare("SELECT * FROM iss_issues WHERE id = :id");
$stmt->execute(['id' => $issue_id]);
$issue = $stmt->fetch(PDO::FETCH_ASSOC);

// fetch comments (resolved at bottom)
$stmt = $conn->prepare("SELECT * FROM iss_comments WHERE iss_id = :id ORDER BY resolved ASC, posted_date DESC");
$stmt->execute(['id' => $issue_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$is_owner_or_admin = (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $issue['per_id'] || $_SESSION['admin'] == 'Y')); // checking if they are the owner or the admin of the issue
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Issue Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">
        Issue #<?= $issue['id'] ?>
        <span class="text-muted small ms-2"><?= $issue['open_date'] ?></span>
    </h2>
    <span class="badge fs-6 <?= $issue['resolved'] ? 'bg-success' : 'bg-warning text-dark' ?>">
        <?= $issue['resolved'] ? 'Resolved' : 'Unresolved' ?>
    </span>
</div>
<p><strong>Short:</strong> <?= htmlspecialchars($issue['short_description']) ?></p>
<p><strong>Long:</strong> <?= nl2br(htmlspecialchars($issue['long_description'])) ?></p>
<p><strong>Priority:</strong> <?= $issue['priority'] ?></p>
<p><strong>Open Date:</strong> <?= $issue['open_date'] ?></p>
<?php if ($issue['close_date'] !== '0000-00-00'): ?>
    <p><strong>Close Date:</strong> <?= $issue['close_date'] ?></p>
<?php endif; ?>
<?php
if (!empty($issue['pdf_attachment'])) {
    $pdfPath = './uploads/' . htmlspecialchars($issue['pdf_attachment']);
    echo "<p><strong>Attachment:</strong> <a href='$pdfPath' target='_blank'>View PDF</a></p>";
}
?>

<?php if ($is_owner_or_admin): ?>
    <a href="edit_issue.php?id=<?= $issue['id'] ?>" class="btn btn-warning mb-3">Edit Issue</a>
    <form action="toggle_issue_resolved.php" method="post" style="display:inline;">
        <input type="hidden" name="id" value="<?= $issue['id']; ?>">
        <input type="hidden" name="current_status" value="<?= $issue['resolved']; ?>">
        <button type="submit" class="btn <?= $issue['resolved'] ? 'btn-secondary' : 'btn-success' ?> mb-3">
            <?= $issue['resolved'] ? 'Mark Unresolved' : 'Mark Resolved' ?>
        </button>
    </form>
    <form action="delete_issue.php" method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this issue?')">
        <input type="hidden" name="id" value="<?= $issue['id']; ?>">
        <button type="submit" class="btn btn-danger mb-3">Delete Issue</button>
    </form>
<?php endif; ?>

<hr>
<h4>Comments</h4>
<?php
$unresolved = array_filter($comments, fn($c) => $c['resolved'] == 0);
$resolved = array_filter($comments, fn($c) => $c['resolved'] == 1);
?>

<?php foreach ($unresolved as $comment):
    $is_comment_owner = $_SESSION['user_id'] == $comment['per_id'] || $_SESSION['admin'] == 'Y';
    ?>
    <div class="border p-2 mb-2">
        <strong><?= htmlspecialchars($comment['short_comment']) ?></strong>
        <p><?= nl2br(htmlspecialchars($comment['long_comment'])) ?></p>
        <small>Posted on <?= $comment['posted_date'] ?></small>
        <?php if ($is_comment_owner): ?>
            <div class="mt-1">
                <a href="edit_comment.php?id=<?= $comment['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                <form method="post" action="toggle_comment_resolved.php" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $comment['id'] ?>">
                    <input type="hidden" name="current_status" value="0">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Mark Resolved</button>
                </form>
                <form method="post" action="delete_comment.php" style="display:inline;" onsubmit="return confirm('Delete this comment?');">
                    <input type="hidden" name="id" value="<?= $comment['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php if (count($resolved) > 0): ?>
    <h5 class="text-muted mt-4">Resolved Comments</h5>
<?php endif; ?>

<?php foreach ($resolved as $comment):
    $is_comment_owner = $_SESSION['user_id'] == $comment['per_id'] || $_SESSION['admin'] == 'Y';
    ?>
    <div class="border p-2 mb-2 bg-light text-muted">
        <strong><?= htmlspecialchars($comment['short_comment']) ?> <span class="text-success">&#10003;</span></strong>
        <p><?= nl2br(htmlspecialchars($comment['long_comment'])) ?></p>
        <small>Posted on <?= $comment['posted_date'] ?></small>
        <?php if ($is_comment_owner): ?>
            <div class="mt-1">
                <a href="edit_comment.php?id=<?= $comment['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                <form method="post" action="toggle_comment_resolved.php" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $comment['id'] ?>">
                    <input type="hidden" name="current_status" value="1">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Mark Unresolved</button>
                </form>
                <form method="post" action="delete_comment.php" style="display:inline;" onsubmit="return confirm('Delete this comment?');">
                    <input type="hidden" name="id" value="<?= $comment['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<h5 class="mt-4">Add a Comment</h5>
<form method="post">
    <div class="mb-2">
        <label>Short Comment</label>
        <input type="text" name="short_comment" class="form-control" required>
    </div>
    <div class="mb-2">
        <label>Long Comment</label>
        <textarea name="long_comment" class="form-control" required></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Add Comment</button>
    <a href="issues_list.php" class="btn btn-secondary">Back</a>
</form>
</body>
</html>
