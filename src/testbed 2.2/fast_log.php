<?php
/**
 * Testbed 2.2 — Event Telemetry (Source + Sink combined)
 *
 * High-performance beacon endpoint that records each access event into
 * metric_logs without blocking the HTTP response. Uses MYSQLI_ASYNC
 * (fire-and-forget): the server replies immediately and never waits for
 * the database to confirm the write. Any SQL error is silently trapped
 * in the socket buffer and never surfaces to the caller.
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../config.php';

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);

$raw_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

$sql = "INSERT INTO metric_logs (user_agent, access_time) VALUES ('$raw_agent', NOW())";

$db->query($sql, MYSQLI_ASYNC);

echo "Event logged asynchronously! (HTTP 200 OK)";