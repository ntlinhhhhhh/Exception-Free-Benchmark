<?php
/**
 * Testbed 4 — Financial Report Generation (Polling)
 *
 * Allows the frontend to check the progress of a previously enqueued report
 * job. Returns the current status and, once complete, a download URL for the
 * generated CSV report.
 */
require_once __DIR__ . '/../config.php';

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);

$job_id = (isset($_GET['job_id'])) ? (int)$_GET['job_id'] : 0   ;

$res = $db->query("SELECT status, report_url FROM heavy_report_jobs WHERE id = $job_id");
$job = $res ? $res->fetch_assoc() : null;

if (!$job) {
    $db->close();
    echo json_encode(["status" => "error", "message" => "Job not found."]);
    exit;
}

if ($job['status'] === 'DONE') {
    echo json_encode(["status" => "ready", "download_url" => $job['report_url']]);
} else if ($job['status'] === 'FAILED') {
    echo json_encode(["status" => "error", "message" => "Report generation failed."]);
} else {
    echo json_encode(["status" => "processing"]);
}
$db->close();