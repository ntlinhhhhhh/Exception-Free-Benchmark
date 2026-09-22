<?php
/**
 * Testbed 1 — Web Traffic Analytics (Sink)
 *
 * Background cronjob that reads unprocessed visit records from raw_traffic
 * and aggregates hit counts per User-Agent string into agent_stats.
 *
 * ERRMODE_SILENT is intentional: if one record fails, the process must
 * continue aggregating the remaining records without crashing.
 */
require_once __DIR__ . '/../config.php';

$pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

$stmt = $pdo->query("SELECT id, user_agent FROM raw_traffic WHERE is_processed = 0");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    $id = (int)$row['id'];
    $ua = $row['user_agent'];

    $sql = "INSERT INTO agent_stats (agent_string, hit_count) VALUES ('$ua', 1)"
            . " ON DUPLICATE KEY UPDATE hit_count = hit_count + 1";

    $pdo->query($sql);
    $pdo->query("UPDATE raw_traffic SET is_processed = 1 WHERE id = $id");
}

echo "Aggregation complete. (HTTP 200 OK)";
$pdo = null;