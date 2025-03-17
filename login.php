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
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // check if the email exists in the database
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
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
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login - Department Status Report</title>
</head>
<body>
<h1>Login</h1>
<?php
// Display error message if login failed.
if (isset($error)) {
    echo "<p style='color:red;'>$error</p>";
}
?>
<form action="login.php" method="post">
    <div>
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required />
    </div>
    <br>
    <div>
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required />
    </div>
    <br>
    <input type="submit" value="Login" />
</form>
</body>
</html>