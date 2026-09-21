<?php
/**
 * Testbed 1 — Web Traffic Analytics (Source)
 *
 * Records each incoming HTTP visit (IP address and User-Agent) into raw_traffic
 * for later batch aggregation by the analytics cronjob.
 * Input is stored safely via a prepared statement.
 */
require_once __DIR__ . '/../config.php';

$pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$raw_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$agent = substr($raw_agent, 0, 255);

$stmt = $pdo->prepare("INSERT INTO raw_traffic (ip_address, user_agent, access_time) VALUES (?, ?, NOW())");

try {
    $stmt->execute([$_SERVER['REMOTE_ADDR'], $agent]);
    echo "Tracking enabled. (HTTP 200 OK)";
} catch (Exception $e) {
    error_log("Tracking error: " . $e->getMessage());
}