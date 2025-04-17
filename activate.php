<?php
require_once './database/database.php';

if (!isset($_GET['activation_code'])) {
    die("<h3>Invalid activation link.</h3>");
}

$code = $_GET['activation_code'];

try {
    $conn = new PDO($connstring, $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // find user with matching code and not yet active
    $stmt = $conn->prepare("SELECT id, activation_expiry FROM iss_persons 
                            WHERE activation_code = :code AND active = 0");
    $stmt->execute(['code' => $code]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $now = date('Y-m-d H:i:s');
        if ($user['activation_expiry'] >= $now) {
            // activate the user
            $stmt = $conn->prepare("UPDATE iss_persons 
                                    SET active = 1, activated_at = NOW(), activation_code = NULL, activation_expiry = NULL 
                                    WHERE id = :id");
            $stmt->execute(['id' => $user['id']]);

            echo "<div class='container mt-5'><div class='alert alert-success'>Account activated successfully. You can now <a href='login2.php'>login</a>.</div></div>";
        } else {
            echo "<div class='container mt-5'><div class='alert alert-warning'>Activation link has expired. Please register again.</div></div>";
        }
    } else {
        echo "<div class='container mt-5'><div class='alert alert-danger'>Invalid or already activated account.</div></div>";
    }
} catch (PDOException $e) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div></div>";
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">