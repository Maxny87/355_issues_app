<?php
session_start();
// password is stored as md5 hash and salt is stored as plain text

require_once './database/database.php';

// create a PDO connection using the imported variables.
try {
    $conn = new PDO($connstring, $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// check if the login form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // retrieve the posted email and password
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // basic validation to ensure fields are not empty
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // prepare the SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, fname, lname, pwd_hash, pwd_salt FROM iss_persons WHERE email = ?");
        $stmt->bindValue(1, $email, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // check if the email exists in the database
        if ($row) {
            // retrieve the stored salt and password hash
            $salt = $row['pwd_salt'];
            $stored_hash = $row['pwd_hash'];

            // hash the provided password with the stored salt (assuming the password was hashed as: md5($password . $salt))
            $computed_hash = md5($password . $salt);

            // verify the password
            if ($computed_hash === $stored_hash) {
                // set session variables for the logged in user
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['fname'] = $row['fname'];
                $_SESSION['lname'] = $row['lname'];

                // redirect to the issues list.
                header("Location: issues_list.php");
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
        $stmt = null;
        $conn = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Department Status Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <?php if (isset($error)) : ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Login</h3>
                </div>
                <div class="card-body">
                    <form action="login.php" method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password:</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                        </div>
                        <div class="row">
                            <div class="col">
                                <button type="submit" class="btn btn-primary w-100">Login</button>
                            </div>
                            <div class="col">
                                <a href="register.php" class="btn btn-secondary w-100">Register</a>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- Optionally, you could add a card footer with additional links or info -->
            </div>
        </div>
    </div>
</div>
<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>