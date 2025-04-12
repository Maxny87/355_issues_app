<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once './database/database.php';

$conn = new PDO($connstring, $db_user, $db_pass);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login2.php");
    exit();
}

// Ensure user is admin
if ($_SESSION['admin'] !== 'Y') die("Access denied.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $password = trim($_POST['password']);
    $admin = $_POST['admin'] === 'Y' ? 'Y' : 'N';

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // Check if email already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM iss_persons WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        die("Email already exists.");
    }

    // Insert new user
    $stmt = $conn->prepare("
        INSERT INTO iss_persons 
        (fname, lname, email, mobile, pwd_hash, admin, active, activation_code, activation_expiry) 
        VALUES (?, ?, ?, ?, ?, ?, 1, NULL, NULL)
    ");
    $stmt->execute([$fname, $lname, $email, $mobile, $hashed, $admin]);

    header("Location: list_users.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
<h2>Add User</h2>
<form method="post">
    <div class="mb-2">
        <label>First Name</label>
        <input type="text" name="fname" class="form-control" required>
    </div>
    <div class="mb-2">
        <label>Last Name</label>
        <input type="text" name="lname" class="form-control" required>
    </div>
    <div class="mb-2">
        <label>Email</label>
        <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-2">
        <label>Mobile</label>
        <input type="text" name="mobile" class="form-control">
    </div>
    <div class="mb-2">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <div class="mb-2">
        <label>Admin?</label>
        <select name="admin" class="form-control">
            <option value="N">No</option>
            <option value="Y">Yes</option>
        </select>
    </div>
    <button class="btn btn-success">Add User</button>
    <a href="list_users.php" class="btn btn-secondary">Cancel</a>
</form>
</body>
</html>