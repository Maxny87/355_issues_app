<?php
session_start();
require_once './database/database.php';

if (!isset($_SESSION['user_id'])) { // making sure the user is logged in
    header("Location: login2.php");
    exit();
}

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== 'Y') { // making sure they are an admin
    die("Access denied.");
}

$conn = new PDO($connstring, $db_user, $db_pass);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// selecting all the users to list them for the admin
$stmt = $conn->query("SELECT * FROM iss_persons ORDER BY id ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
<h2>User Management</h2>
<div class="mb-3">
    <a href="add_user.php" class="btn btn-success">+ Add User</a>
    <a href="issues_list.php" class="btn btn-secondary">Back to Issues</a>
</div>
<table class="table table-bordered table-striped">
    <thead class="table-dark">
    <tr>
        <th>ID</th>
        <th>First</th>
        <th>Last</th>
        <th>Email</th>
        <th>Mobile</th>
        <th>Admin</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?= $user['id'] ?></td>
            <td><?= htmlspecialchars($user['fname']) ?></td>
            <td><?= htmlspecialchars($user['lname']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td><?= htmlspecialchars($user['mobile']) ?></td>
            <td><?= $user['admin'] == 'Y' ? 'Yes' : 'No' ?></td>
            <td>
                <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                <?php if ($_SESSION['user_id'] != $user['id']) : ?>
                    <form action="delete_user.php" method="post" style="display:inline;" onsubmit="return confirm('Delete this user?');">
                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>