<?php
session_start();
require_once './database/database.php';

if (!isset($_SESSION['user_id'])) { // making sure the user is logged in
    header("Location: login2.php");
    exit();
}

if (!isset($_GET['id'])) die("Issue ID not specified."); // making sure we have issue id to edit
$id = $_GET['id'];

$conn = new PDO($connstring, $db_user, $db_pass);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// get this issue
$stmt = $conn->prepare("SELECT * FROM iss_issues WHERE id = :id");
$stmt->execute(['id' => $id]);
$issue = $stmt->fetch(PDO::FETCH_ASSOC);

// only the owner or admin can edit
if ($_SESSION['user_id'] != $issue['per_id'] || $_SESSION['admin'] !== 'Y') {
    die("Unauthorized access.");
}

// fetch users for the dropdown
$users = $conn->query("SELECT id, fname, lname FROM iss_persons ORDER BY lname")->fetchAll(PDO::FETCH_ASSOC);

// if second time we are here and we have post method with details we edit the issue
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $conn->prepare("UPDATE iss_issues SET short_description = :short, long_description = :long, priority = :priority, project = :project, org = :org, per_id = :per_id WHERE id = :id");
    $stmt->execute([
        'short' => $_POST['short_description'],
        'long' => $_POST['long_description'],
        'priority' => $_POST['priority'],
        'project' => $_POST['project'],
        'org' => $_POST['org'],
        'per_id' => $_POST['per_id'],
        'id' => $id
    ]);
    header("Location: issue_details.php?id=" . $id);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Issue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
<h2>Edit Issue</h2>
<form method="post">
    <div class="mb-3">
        <label>Short Description</label>
        <input type="text" name="short_description" value="<?= htmlspecialchars($issue['short_description']) ?>" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Long Description</label>
        <textarea name="long_description" class="form-control" required><?= htmlspecialchars($issue['long_description']) ?></textarea>
    </div>
    <div class="mb-3">
        <label>Priority</label>
        <select name="priority" class="form-select">
            <option value="A" <?= $issue['priority'] == 'A' ? 'selected' : '' ?>>High</option>
            <option value="B" <?= $issue['priority'] == 'B' ? 'selected' : '' ?>>Medium</option>
            <option value="C" <?= $issue['priority'] == 'C' ? 'selected' : '' ?>>Low</option>
        </select>
    </div>
    <div class="mb-3">
        <label>Project</label>
        <input type="text" name="project" value="<?= htmlspecialchars($issue['project']) ?>" class="form-control">
    </div>
    <div class="mb-3">
        <label>Organization</label>
        <input type="text" name="org" value="<?= htmlspecialchars($issue['org']) ?>" class="form-control">
    </div>
    <div class="mb-3">
        <label>Issue Assigned To</label>
        <select name="per_id" class="form-select" required>
            <?php foreach ($users as $user): ?>
                <option value="<?= $user['id'] ?>" <?= ($issue['per_id'] == $user['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($user['fname'] . ' ' . $user['lname']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-success">Update Issue</button>
    <a href="issue_details.php?id=<?= $id ?>" class="btn btn-secondary">Cancel</a>
</form>
</body>
</html>