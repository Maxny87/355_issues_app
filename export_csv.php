<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login2.php");
    exit();
}

require_once './database/database.php';

try {
    $conn = new PDO($connstring, $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get filter options
$projects = $conn->query("SELECT DISTINCT project FROM iss_issues WHERE project != ''")->fetchAll(PDO::FETCH_COLUMN);
$orgs = $conn->query("SELECT DISTINCT org FROM iss_issues WHERE org != ''")->fetchAll(PDO::FETCH_COLUMN);
$persons = $conn->query("SELECT id, fname, lname FROM iss_persons ORDER BY lname")->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['export'])) {
    $status_filter = $_POST['status'] ?? '';
    $project_filter = $_POST['project'] ?? '';
    $org_filter = $_POST['org'] ?? '';
    $person_filter = $_POST['person'] ?? '';
    $sort_by = $_POST['sort_by'] ?? 'open_date';
    $sort_order = $_POST['sort_order'] ?? 'ASC';

    $where = [];
    $params = [];

    if ($status_filter !== '') {
        $where[] = "resolved = :status";
        $params[':status'] = $status_filter;
    }
    if ($project_filter) {
        $where[] = "project = :project";
        $params[':project'] = $project_filter;
    }
    if ($org_filter) {
        $where[] = "org = :org";
        $params[':org'] = $org_filter;
    }
    if ($person_filter) {
        if ($person_filter === 'self') {
            $where[] = "per_id = :self_id";
            $params[':self_id'] = $_SESSION['user_id'];
        } elseif ($person_filter === 'unknown') {
            $where[] = "iss_persons.id IS NULL";
        } else {
            $where[] = "per_id = :person";
            $params[':person'] = $person_filter;
        }
    }

    $where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "SELECT iss_issues.*, iss_persons.fname, iss_persons.lname 
            FROM iss_issues 
            LEFT JOIN iss_persons ON iss_issues.per_id = iss_persons.id 
            $where_clause 
            ORDER BY $sort_by $sort_order";

    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=filtered_issues_export.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Short Description', 'Open Date', 'Close Date', 'Priority', 'Project', 'Org', 'Person', 'Resolved']);

    foreach ($rows as $row) {
        $person = isset($row['fname']) ? $row['fname'] . ' ' . $row['lname'] : 'Unknown';
        fputcsv($output, [
            $row['id'],
            $row['short_description'],
            $row['open_date'],
            $row['close_date'],
            $row['priority'],
            $row['project'],
            $row['org'],
            $person,
            $row['resolved'] ? 'Resolved' : 'Unresolved'
        ]);
    }

    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Export Issues</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
<h2 class="mb-4">Export Issues to CSV</h2>
<form method="post" class="card p-4">
    <div class="row mb-3">
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">All</option>
                <option value="0">Unresolved</option>
                <option value="1">Resolved</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Project</label>
            <select name="project" class="form-select">
                <option value="">All</option>
                <?php foreach ($projects as $p): ?>
                    <option value="<?= $p ?>"><?= $p ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Organization</label>
            <select name="org" class="form-select">
                <option value="">All</option>
                <?php foreach ($orgs as $o): ?>
                    <option value="<?= $o ?>"><?= $o ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Person</label>
            <select name="person" class="form-select">
                <option value="">All</option>
                <option value="self">My Issues  (<?= $_SESSION['fname'] . ' ' . $_SESSION['lname'] ?>) </option>
                <option value="unknown"><em>Unknown</em></option>
                <?php foreach ($persons as $p): if ($p['id'] == $_SESSION['user_id']) continue; ?>
                    <option value="<?= $p['id'] ?>"><?= $p['fname'] . ' ' . $p['lname'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <label class="form-label">Sort By</label>
            <select name="sort_by" class="form-select">
                <option value="open_date">Open Date</option>
                <option value="priority">Priority</option>
                <option value="project">Project</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Sort Order</label>
            <select name="sort_order" class="form-select">
                <option value="ASC">Ascending</option>
                <option value="DESC">Descending</option>
            </select>
        </div>
    </div>

    <button type="submit" name="export" class="btn btn-success mb-3">Export to CSV</button>
    <a href="issues_list.php" class="btn btn-secondary mb-3">Back</a>
</form>
</body>
</html>