<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


session_start();
require_once './database/database.php';
if (!isset($_SESSION['user_id'])) {
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
        // validate priority
        $valid_priorities = ['A', 'B', 'C'];
        if (!in_array($_POST['priority'], $valid_priorities)) {
            die("Invalid priority value.");
        }

        // validate per_id (must exist in users table)
        $stmt = $conn->prepare("SELECT COUNT(*) FROM iss_persons WHERE id = ?");
        $stmt->execute([$_POST['per_id']]);
        if ($stmt->fetchColumn() == 0) {
            die("Invalid user assignment.");
        }

        $newFileName = null;
        if (isset($_FILES['pdf_attachment']) && $_FILES ['pdf_attachment']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['pdf_attachment']['tmp_name'];
            $fileName = $_FILES['pdf_attachment']['name'];
            $fileSize = $_FILES['pdf_attachment']['size'];
            $fileType = $_FILES['pdf_attachment']['type'];
            $fileNameCmps = explode(".", $fileName); // explode function makes an array of words separated by the filename and extension
            $fileExtension = strtolower(end($fileNameCmps)); // last element of the explode array

            if ( $fileExtension !== 'pdf') {
                die ("Only PDF files are allowed.") ; // dont want to kill entire program
            }

            if ( $fileSize > 2 * 1024 * 1024) {
                die ("File size exceeds 2 MB limit.") ; // dont want to kill entire program
            }

            $newFileName = MD5(time() . $fileName) . "." . $fileExtension; // timestamp it so its unique
            $uploadFileDirectory = './uploads/';
            $destination = $uploadFileDirectory . $newFileName;

            if (!is_dir($uploadFileDirectory)) {
                mkdir($uploadFileDirectory, 0755, true);
            }

            if (move_uploaded_file($fileTmpPath, $destination)) {
                $attachmentPath = $newFileName;
            } else {
                $error = error_get_last();
                die("Error uploading file: " . $error['message']);
//                $attachmentPath = null;
            }
        } else {
            $attachmentPath = null;
        }
        // add the issue
        $stmt = $conn->prepare("INSERT INTO iss_issues 
            (short_description, long_description, open_date, close_date, priority, org, project, per_id, resolved, pdf_attachment) 
            VALUES (:short, :long, CURDATE(), '0000-00-00', :priority, :org, :project, :per_id, 0, :pdf_attachment)");

        $stmt->execute([
            'short' => $_POST['short_description'],
            'long' => $_POST['long_description'],
            'priority' => $_POST['priority'],
            'org' => $_POST['org'],
            'project' => $_POST['project'],
            'per_id' => $_POST['per_id'],
            'pdf_attachment' => $newFileName,
        ]);

        header("Location: issues_list.php");
        exit;
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>