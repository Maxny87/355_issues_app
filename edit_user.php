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

if ($_SESSION['admin'] !== 'Y') die("Access denied."); // only admin can access edit user
if (!isset($_GET['id'])) die("User ID not provided."); // need user id to edit

$user_id = $_GET['id'];

$conn = new PDO($connstring, $db_user, $db_pass);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $admin = $_POST['admin'] === 'Y' ? 'Y' : 'N';

    // update password if entered
    if (!empty($_POST['password'])) {
        $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE iss_persons SET fname=?, lname=?, email=?, mobile=?, admin=?, pwd_hash=? WHERE id=?");
        $stmt->execute([$fname, $lname, $email, $mobile, $admin, $hashed, $user_id]);
    } else {
        $stmt = $conn->prepare("UPDATE iss_persons SET fname=?, lname=?, email=?, mobile=?, admin=? WHERE id=?");
        $stmt->execute([$fname, $lname, $email, $mobile, $admin, $user_id]);
    }

    header("Location: list_users.php");
    exit;
}

// fetch user info
$stmt = $conn->prepare("SELECT * FROM iss_persons WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) die("User not found.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
<h2>Edit User</h2>
<form method="post">
    <div class="mb-2">
        <label>First Name</label>
        <input type="text" name="fname" class="form-control" value="<?= htmlspecialchars($user['fname']) ?>" required>
    </div>
    <div class="mb-2">
        <label>Last Name</label>
        <input type="text" name="lname" class="form-control" value="<?= htmlspecialchars($user['lname']) ?>" required>
    </div>
    <div class="mb-2">
        <label>Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
    </div>
    <div class="mb-2">
        <label>Mobile</label>
        <input type="text" name="mobile" class="form-control" value="<?= htmlspecialchars($user['mobile']) ?>">
    </div>
    <div class="mb-2">
        <label>Admin?</label>
        <select name="admin" class="form-control">
            <option value="N" <?= $user['admin'] === 'N' ? 'selected' : '' ?>>No</option>
            <option value="Y" <?= $user['admin'] === 'Y' ? 'selected' : '' ?>>Yes</option>
        </select>
    </div>
    <div class="mb-2">
        <label>New Password (optional)</label>
        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep existing">
    </div>
    <button class="btn btn-primary">Save Changes</button>
    <a href="list_users.php" class="btn btn-secondary">Cancel</a>
</form>
</body>
</html>