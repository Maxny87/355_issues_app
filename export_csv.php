<?php

session_start();
require_once './database/database.php';

if (!isset($_SESSION['user_id'])) { // making sure the user is logged in
    header("Location: login2.php");
    exit();
}

$conn = new PDO($connstring, $db_user, $db_pass);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

?>
<div class="container mt-4">
    <h2>Export Issues</h2>
    <form method="post" action="export_issues.php">
        <div class="row mb-3">
            <div class="col">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="">All</option>
                    <option value="resolved">Resolved</option>
                    <option value="unresolved">Unresolved</option>
                </select>
            </div>
            <div class="col">
                <label>Sort by</label>
                <select name="sort_by" class="form-control">
                    <option value="created_at">Created At</option>
                    <option value="status">Status</option>
                    <option value="assigned_to">Assigned To</option>
                </select>
            </div>
            <div class="col">
                <label>Sort Order</label>
                <select name="sort_order" class="form-control">
                    <option value="ASC">Ascending</option>
                    <option value="DESC">Descending</option>
                </select>
            </div>
        </div>
        <button type="submit" name="export" class="btn btn-success">Export to CSV</button>
    </form>
</div>
