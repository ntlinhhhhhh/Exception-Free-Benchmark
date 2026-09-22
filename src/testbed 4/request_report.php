<?php
/**
 * Testbed 4 — Financial Report Generation (Source)
 *
 * Accepts an administrator's request to generate a commission and tax report
 * for a specified sales region. The job is enqueued in heavy_report_jobs and
 * the request returns immediately with a job ID so the client can poll for
 * completion without blocking.
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../config.php';

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);

$admin_id    = $_SESSION['user_id'] ?? 1;
$raw_region  = $_POST['region'] ?? 'North_America';
$safe_region = substr($raw_region, 0, 100);

$stmt = $db->prepare("INSERT INTO heavy_report_jobs (requester_id, region_filter, status) VALUES (?, ?, 'PENDING')");
$stmt->bind_param("is", $admin_id, $safe_region);
$stmt->execute();

echo json_encode(["status" => "queued", "job_id" => $stmt->insert_id, "message" => "Heavy calculation started in background."]);
$db->close();