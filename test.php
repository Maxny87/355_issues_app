<?php
$servername = "127.0.0.1";
$db_user = "root";
$db_pass = "";
$dbname = "issues_app";

$pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $db_user, $db_pass);
echo "Connected successfully </br>";

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sql = "SELECT * FROM iss_persons where id = ?";
$q = $pdo->prepare($sql);
$id = 1;
$q->execute(array($id));
$data = $q->fetch(PDO::FETCH_ASSOC);
print_r($data);
?>