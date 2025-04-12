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
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// resolved checkbox
$show_resolved = isset($_GET['show_resolved']) && $_GET['show_resolved'] === '1';

// pagination
$limit_unresolved = $show_resolved ? 5 : 10;
$limit_resolved = 5;
$page_unresolved = isset($_GET['page_unresolved']) ? max((int)$_GET['page_unresolved'], 1) : 1;
$page_resolved = isset($_GET['page_resolved']) ? max((int)$_GET['page_resolved'], 1) : 1;
$offset_unresolved = ($page_unresolved - 1) * $limit_unresolved;
$offset_resolved = ($page_resolved - 1) * $limit_resolved;

// filter options
$projects = $conn->query("SELECT DISTINCT project FROM iss_issues WHERE project != ''")->fetchAll(PDO::FETCH_COLUMN);
$orgs = $conn->query("SELECT DISTINCT org FROM iss_issues WHERE org != ''")->fetchAll(PDO::FETCH_COLUMN);
$persons = $conn->query("SELECT id, fname, lname FROM iss_persons ORDER BY lname")->fetchAll(PDO::FETCH_ASSOC);

// filters from GET
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
    } elseif ($person_filter === 'org') {
        $where[] = "iss_persons.id IS NULL";
    } else {
        $where[] = "per_id = :person";
        $params[':person'] = $person_filter;
    }
}

$where_clause = $where ? ' AND ' . implode(' AND ', $where) : '';

// unresolved issues
$unres_stmt = $conn->prepare("SELECT iss_issues.*, iss_persons.fname, iss_persons.lname 
    FROM iss_issues LEFT JOIN iss_persons ON iss_issues.per_id = iss_persons.id 
    WHERE resolved = 0 $where_clause 
    ORDER BY open_date DESC LIMIT :limit OFFSET :offset");
foreach ($params as $key => $val) $unres_stmt->bindValue($key, $val);
$unres_stmt->bindValue(':limit', $limit_unresolved, PDO::PARAM_INT);
$unres_stmt->bindValue(':offset', $offset_unresolved, PDO::PARAM_INT);
$unres_stmt->execute();
$unresolved_issues = $unres_stmt->fetchAll(PDO::FETCH_ASSOC);

// unresolved count
$count_unres = $conn->prepare("SELECT COUNT(*) FROM iss_issues WHERE resolved = 0 $where_clause");
$count_unres->execute($params);
$total_unresolved = $count_unres->fetchColumn();
$total_pages_unres = ceil($total_unresolved / $limit_unresolved);

// resolved issues only if checkbox is checked
$resolved_issues = [];
$total_pages_res = 0;

if ($show_resolved) {
    $res_stmt = $conn->prepare("SELECT iss_issues.*, iss_persons.fname, iss_persons.lname 
        FROM iss_issues LEFT JOIN iss_persons ON iss_issues.per_id = iss_persons.id 
        WHERE resolved = 1 $where_clause 
        ORDER BY open_date DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $key => $val) $res_stmt->bindValue($key, $val);
    $res_stmt->bindValue(':limit', $limit_resolved, PDO::PARAM_INT);
    $res_stmt->bindValue(':offset', $offset_resolved, PDO::PARAM_INT);
    $res_stmt->execute();
    $resolved_issues = $res_stmt->fetchAll(PDO::FETCH_ASSOC);

    $count_res = $conn->prepare("SELECT COUNT(*) FROM iss_issues WHERE resolved = 1 $where_clause");
    $count_res->execute($params);
    $total_resolved = $count_res->fetchColumn();
    $total_pages_res = ceil($total_resolved / $limit_resolved);
}
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
        <button type="button" class="btn btn-success me-3" data-bs-toggle="modal" data-bs-target="#addIssueModal">
            + Add Issue
        </button>
        <p class="me-3 mb-0">Welcome, <strong><?= $_SESSION['fname'] ?? 'User' ?></strong></p>
        <?php if ($_SESSION['admin'] === 'Y') : ?>
            <a href="list_users.php" class="btn btn-outline-secondary me-2 btn-sm">View Users</a>
            <a href="export_csv.php" class="btn btn-outline-secondary me-2 btn-sm">Export CSV</a>
        <?php endif; ?>
        <form action="logout.php" method="post">
            <button type="submit" class="btn btn-outline-danger btn-sm">Logout</button>
        </form>
    </div>
</div>

<div class="card mb-4 border-secondary">
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
                    <option value="self" <?= $person_filter === 'self' ? 'selected' : '' ?>>My Issues (<?= $_SESSION['fname'] . ' ' . $_SESSION['lname'] ?>) </option>
                    <option value="unknown" <?= $person_filter === 'unknown' ? 'selected' : '' ?>><em>Unknown</em></option>
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

<div class="card mb-4 border border-secondary shadow-sm">
<form method="get" class="row g-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="show_resolved" id="showResolvedCheckbox" value="1" <?= $show_resolved ? 'checked' : '' ?> onchange="this.form.submit()">
            <label class="form-check-label" for="showResolvedCheckbox">Show Resolved</label>
        </div>
        <small class="text-muted ms-3">Toggle this to include resolved issues in the list below</small>
</form>
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
            <td><?= isset($row['fname']) ? $row['fname'] . ' ' . $row['lname'] : '<em>Unknown</em>' ?></td>
            <td>
                <a href="issue_details.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">View</a>
                <?php if ($_SESSION['admin'] === 'Y' || $_SESSION['user_id'] == $row['per_id']) : ?>
                    <form action="toggle_issue_resolved.php" method="post" class="d-inline">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <input type="hidden" name="current_status" value="0">
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
<?php if ($show_resolved): ?>
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
                <td><?= isset($row['fname']) ? $row['fname'] . ' ' . $row['lname'] : '<em>Unknown</em>' ?></td>
                <td>
                    <a href="issue_details.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">View</a>
                    <?php if ($_SESSION['admin'] === 'Y' || $_SESSION['user_id'] == $row['per_id']) : ?>
                        <form action="toggle_issue_resolved.php" method="post" class="d-inline">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="current_status" value="1">
                            <button type="submit" class="btn btn-sm btn-warning">Unresolve</button>
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

    <?php if ($total_pages_res > 1): ?>
        <nav><ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages_res; $i++): ?>
                    <li class="page-item <?= $i == $page_resolved ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page_resolved' => $i])) ?>#resolved">Resolved <?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul></nav>
    <?php endif; ?>
<?php endif; ?>

<div class="modal fade" id="addIssueModal" tabindex="-1" aria-labelledby="addIssueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" action="add_issue_handler.php" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addIssueModalLabel">Add New Issue</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Short Description</label>
                    <input type="text" name="short_description" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select" required>
                        <option value="A">High</option>
                        <option value="B">Medium</option>
                        <option value="C">Low</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Long Description</label>
                    <textarea name="long_description" class="form-control" required></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Project</label>
                    <input type="text" name="project" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Organization</label>
                    <input type="text" name="org" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">PDF Attachment (Max 2MB)</label>
                    <input type="file" name="pdf_attachment" accept="application/pdf" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Issue For (User)</label>
                    <?php if ($_SESSION['admin'] === 'Y'): ?>
                        <select name="per_id" class="form-select" required>
                            <?php foreach ($persons as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= ($user['id'] == $_SESSION['user_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['fname'] . ' ' . $user['lname']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="hidden" name="per_id" value="<?= $_SESSION['user_id'] ?>">
                        <input type="text" class="form-control" value="<?= $_SESSION['fname'] . ' ' . $_SESSION['lname'] ?>" disabled>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success">Add Issue</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
