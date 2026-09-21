<?php
/**
 * Testbed 2.1 — CRM Loyalty Program (Source)
 *
 * Registers a new corporate client and records a NEW_CLIENT_REGISTERED
 * audit event in system_logs. Input is stored safely via prepared statements.
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../config.php';
$db_name = getenv('DB_NAME') ?: 'silent_testbed';

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);

$raw_client_name = $_POST['client_name'] ?? '';

if (strlen($raw_client_name) > 100) {
    $raw_client_name = substr($raw_client_name, 0, 100);
}

$stmt = $db->prepare("INSERT INTO clients (client_name, registered_at) VALUES (?, NOW())");
$stmt->bind_param("s", $raw_client_name);

$log_stmt = $db->prepare("INSERT INTO system_logs (client_id, event_type, message) VALUES (LAST_INSERT_ID(), 'NEW_CLIENT_REGISTERED', ?)");
$log_message = "New client registered: " . $raw_client_name;
$log_stmt->bind_param("s", $log_message);

try {
    $stmt->execute();
    $log_stmt->execute();
    echo "Client registered successfully!";
} catch (Exception $e) {
    error_log("System error: " . $e->getMessage());
}