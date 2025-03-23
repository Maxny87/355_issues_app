<?php
session_start();
require_once './database/database.php';

if (!isset($_SESSION['user_id'])) { // making sure the user is logged in
    header("Location: login2.php");
    exit();
}

try {
    $conn = new PDO($connstring, $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // fetch all users for dropdown to set who the issue is for
    $users = $conn->query("SELECT id, fname, lname FROM iss_persons ORDER BY lname")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // add the issue
        $stmt = $conn->prepare("INSERT INTO iss_issues 
            (short_description, long_description, open_date, close_date, priority, org, project, per_id, resolved) 
            VALUES (:short, :long, CURDATE(), '0000-00-00', :priority, :org, :project, :per_id, 0)");

        $stmt->execute([
            'short' => $_POST['short_description'],
            'long' => $_POST['long_description'],
            'priority' => $_POST['priority'],
            'org' => $_POST['org'],
            'project' => $_POST['project'],
            'per_id' => $_POST['per_id']
        ]);

        header("Location: issues_list.php");
        exit;
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Issue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
<h2>Add New Issue</h2>
<form method="post">
    <div class="mb-3">
        <label class="form-label">Short Description</label>
        <input type="text" name="short_description" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Long Description</label>
        <textarea name="long_description" class="form-control" required></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Priority</label>
        <select name="priority" class="form-select" required>
            <option value="A">High</option>
            <option value="B">Medium</option>
            <option value="C">Low</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Project</label>
        <input type="text" name="project" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label">Organization</label>
        <input type="text" name="org" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label">Issue For (User)</label>
        <select name="per_id" class="form-select" required>
            <?php foreach ($users as $user): ?>
                <option value="<?= $user['id'] ?>" <?= ($user['id'] == ($_SESSION['user_id'] ?? 0)) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($user['fname'] . ' ' . $user['lname']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-success">Submit</button>
    <a href="issues_list.php" class="btn btn-secondary">Back</a>
</form>
</body>
</html>