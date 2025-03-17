<?php
session_start();
require_once './database/database.php';

// create a PDO connection using the imported variables
try {
    $conn = new PDO($connstring, $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// check if the registration form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // retrieve and trim the posted values
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $mobile = trim($_POST['mobile']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // validate that all required fields are provided
    if (empty($fname) || empty($lname) || empty($mobile) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!preg_match('/@svsu\.edu$/', $email)) { // Check if email ends with @svsu.edu
        $error = "Registration is limited to SVSU email addresses.";
    } else {
        // optionally, check if the email is already registered
        $stmt = $conn->prepare("SELECT id FROM iss_persons WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $error = "This email is already registered. Please use another email or login.";
        } else {
            // generate a salt (16 hex characters)
            $salt = bin2hex(random_bytes(8));
            // compute the md5 hash of the password concatenated with the salt
            $pwd_hash = md5($password . $salt);

            // insert the new user into the iss_persons table
            $stmt = $conn->prepare("INSERT INTO iss_persons (fname, lname, mobile, email, pwd_hash, pwd_salt, admin) VALUES (:fname, :lname, :mobile, :email, :pwd_hash, :pwd_salt, :admin)");
            $result = $stmt->execute([
                'fname'     => $fname,
                'lname'     => $lname,
                'mobile'    => $mobile,
                'email'     => $email,
                'pwd_hash'  => $pwd_hash,
                'pwd_salt'  => $salt,
                'admin'     => 'N'
            ]);

            if ($result) {
                $success = "Registration successful! You can now <a href='login2.php'>login</a>.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
    $conn = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Department Status Report</title>
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
            <?php if (isset($success)) : ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Register</h3>
                </div>
                <div class="card-body">
                    <form action="register.php" method="post">
                        <div class="mb-3">
                            <label for="fname" class="form-label">First Name:</label>
                            <input type="text" name="fname" id="fname" class="form-control" placeholder="Enter your first name" required>
                        </div>
                        <div class="mb-3">
                            <label for="lname" class="form-label">Last Name:</label>
                            <input type="text" name="lname" id="lname" class="form-control" placeholder="Enter your last name" required>
                        </div>
                        <div class="mb-3">
                            <label for="mobile" class="form-label">Mobile:</label>
                            <input type="text" name="mobile" id="mobile" class="form-control" placeholder="Enter your mobile number" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password:</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password:</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-enter your password" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Register</button>
                            <a href="login2.php" class="btn btn-secondary">Back to Login</a>
                        </div>
                    </form>
                </div>
                <!-- Optionally, you could add a card footer here -->
            </div>
        </div>
    </div>
</div>
<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>