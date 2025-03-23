<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'])) { // making sure the user is logged in
    header("Location: login2.php");
    exit();
}

require_once './database/database.php';

// db connection
try {
    $conn = new PDO($connstring, $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// pagination settings
$limit = 5;
$page_unresolved = isset($_GET['page_unresolved']) ? max((int)$_GET['page_unresolved'], 1) : 1;
$page_resolved = isset($_GET['page_resolved']) ? max((int)$_GET['page_resolved'], 1) : 1;
$offset_unresolved = ($page_unresolved - 1) * $limit;
$offset_resolved = ($page_resolved - 1) * $limit;

// filter settings
$projects = $conn->query("SELECT DISTINCT project FROM iss_issues WHERE project != ''")->fetchAll(PDO::FETCH_COLUMN);
$orgs = $conn->query("SELECT DISTINCT org FROM iss_issues WHERE org != ''")->fetchAll(PDO::FETCH_COLUMN);
$persons = $conn->query("SELECT id, fname, lname FROM iss_persons ORDER BY lname")->fetchAll(PDO::FETCH_ASSOC);

// any filters given or sent
$status_filter = $_GET['status'] ?? '';
$project_filter = $_GET['project'] ?? '';
$org_filter = $_GET['org'] ?? '';
$person_filter = $_GET['person'] ?? '';

$where = [];
$params = [];

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
    } else {
        $where[] = "per_id = :person";
        $params[':person'] = $person_filter;
    }
}

$where_clause = $where ? ' AND ' . implode(' AND ', $where) : '';

$unres_sql = "SELECT iss_issues.*, iss_persons.fname, iss_persons.lname FROM iss_issues JOIN iss_persons ON iss_issues.per_id = iss_persons.id WHERE resolved = 0 $where_clause ORDER BY open_date DESC LIMIT :limit OFFSET :offset";
$unres_stmt = $conn->prepare($unres_sql);
foreach ($params as $key => $val) $unres_stmt->bindValue($key, $val);
$unres_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$unres_stmt->bindValue(':offset', $offset_unresolved, PDO::PARAM_INT);
$unres_stmt->execute();
$unresolved_issues = $unres_stmt->fetchAll(PDO::FETCH_ASSOC);

$count_unres = $conn->prepare("SELECT COUNT(*) FROM iss_issues WHERE resolved = 0 $where_clause");
$count_unres->execute($params);
$total_unresolved = $count_unres->fetchColumn();
$total_pages_unres = ceil($total_unresolved / $limit);

$res_sql = "SELECT iss_issues.*, iss_persons.fname, iss_persons.lname FROM iss_issues JOIN iss_persons ON iss_issues.per_id = iss_persons.id WHERE resolved = 1 $where_clause ORDER BY open_date DESC LIMIT :limit OFFSET :offset";
$res_stmt = $conn->prepare($res_sql);
foreach ($params as $key => $val) $res_stmt->bindValue($key, $val);
$res_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$res_stmt->bindValue(':offset', $offset_resolved, PDO::PARAM_INT);
$res_stmt->execute();
$resolved_issues = $res_stmt->fetchAll(PDO::FETCH_ASSOC);

$count_res = $conn->prepare("SELECT COUNT(*) FROM iss_issues WHERE resolved = 1 $where_clause");
$count_res->execute($params);
$total_resolved = $count_res->fetchColumn();
$total_pages_res = ceil($total_resolved / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Issues List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Issues List</h2>
    <div class="d-flex align-items-center">
        <a href="add_issue.php" class="btn btn-success me-3">+ Add Issue</a>
        <p class="me-3 mb-0">Welcome, <strong><?= $_SESSION['fname'] ?? 'User' ?></strong></p>
        <?php if ($_SESSION['admin'] === 'Y') : ?>
            <a href="list_users.php" class="btn btn-outline-secondary me-2 btn-sm">View Users</a>
        <?php endif; ?>
        <form action="logout.php" method="post">
            <button type="submit" class="btn btn-outline-danger btn-sm">Logout</button>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Project</label>
                <select name="project" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p ?>" <?= $project_filter === $p ? 'selected' : '' ?>><?= $p ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Organization</label>
                <select name="org" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($orgs as $o): ?>
                        <option value="<?= $o ?>" <?= $org_filter === $o ? 'selected' : '' ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Person</label>
                <select name="person" class="form-select">
                    <option value="">All</option>
                    <option value="self" <?= $person_filter === 'self' ? 'selected' : '' ?>>My Issues</option>
                    <?php foreach ($persons as $p): if ($p['id'] == $_SESSION['user_id']) continue; ?>
                        <option value="<?= $p['id'] ?>" <?= $person_filter == $p['id'] ? 'selected' : '' ?>><?= $p['fname'] . ' ' . $p['lname'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                <a href="issues_list.php" class="btn btn-outline-secondary">Clear Filters</a>
            </div>
        </form>
    </div>
</div>

<h5>Unresolved Issues</h5>
<table class="table table-hover table-striped">
    <thead class="table-dark">
    <tr>
        <th>ID</th>
        <th>Short Description</th>
        <th>Open Date</th>
        <th>Priority</th>
        <th>Project</th>
        <th>Org</th>
        <th>Person</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($unresolved_issues as $row): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['short_description']) ?></td>
            <td><?= $row['open_date'] ?></td>
            <td><?= $row['priority'] ?></td>
            <td><?= $row['project'] ?></td>
            <td><?= $row['org'] ?></td>
            <td><?= $row['fname'] . ' ' . $row['lname'] ?></td>
            <td>
                <a href="issue_details.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">View</a>
                <?php if ($_SESSION['admin'] === 'Y' || $_SESSION['user_id'] == $row['per_id']) : ?>
                    <form action="mark_resolved.php" method="post" class="d-inline">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-warning">Resolve</button>
                    </form>
                    <form action="delete_issue.php" method="post" class="d-inline" onsubmit="return confirm('Delete this issue?');">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php if ($total_pages_unres > 1): ?>
    <nav><ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages_unres; $i++): ?>
                <li class="page-item <?= $i == $page_unresolved ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page_unresolved' => $i])) ?>#unresolved">Unresolved <?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul></nav>
<?php endif; ?>

<h5 class="mt-5">Resolved Issues</h5>
<table class="table table-hover table-striped">
    <thead class="table-secondary">
    <tr>
        <th>ID</th>
        <th>Short Description</th>
        <th>Open Date</th>
        <th>Close Date</th>
        <th>Priority</th>
        <th>Project</th>
        <th>Org</th>
        <th>Person</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($resolved_issues as $row): ?>
        <tr class="table-success">
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['short_description']) ?></td>
            <td><?= $row['open_date'] ?></td>
            <td><?= $row['close_date'] ?></td>
            <td><?= $row['priority'] ?></td>
            <td><?= $row['project'] ?></td>
            <td><?= $row['org'] ?></td>
            <td><?= $row['fname'] . ' ' . $row['lname'] ?></td>
            <td>
            <a href="issue_details.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">View</a>
            <?php if ($_SESSION['admin'] === 'Y' || $_SESSION['user_id'] == $row['per_id']) : ?>
                <form action="unresolve_issue.php" method="post" class="d-inline">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-warning">Unresolve</button>
                </form>
                <form action="delete_issue.php" method="post" class="d-inline" onsubmit="return confirm('Delete this issue?');">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php if ($total_pages_res > 1): ?>
    <nav><ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages_res; $i++): ?>
                <li class="page-item <?= $i == $page_resolved ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page_resolved' => $i])) ?>#resolved">Resolved <?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul></nav>
<?php endif; ?>
</body>
</html>
