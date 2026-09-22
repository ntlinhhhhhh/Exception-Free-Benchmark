<?php
/**
 * Testbed 2.1 — CRM Loyalty Program (Sink)
 *
 * Activates loyalty membership for a registered client. On first access,
 * the client is enrolled at BRONZE tier: loyalty record is created, status
 * is updated, and an activation event is logged — all via multi_query.
 * Errors from subsequent statements in the batch are silently swallowed.
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../config.php';

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);

function validate_safe_id($id) {
    return filter_var($id, FILTER_VALIDATE_INT, array("options" => array("min_range" => 1))) !== false;
}

function process_loyalty_sync($db, $input_id) {
    $clean_id = validate_safe_id($input_id) ? (int)$input_id : 0;

    $result = $db->query("SELECT client_name, is_synced FROM clients WHERE id = $clean_id");
    if ($result->num_rows === 0) {
        error_log("Client ID $clean_id not found.");
        return null;
    }

    $row = $result->fetch_assoc();
    $cname = $row['client_name'];
    $is_synced = (int)$row['is_synced'];

    try {
        if ($is_synced === 0) {
            $msg = "Enrolled new loyal customer: " . $cname;
            $sql = "UPDATE clients SET is_synced = 1 WHERE id = $clean_id; \n
                    INSERT INTO loyalty_points (client_id, current_tier, total_spent) VALUES ($clean_id, 'BRONZE', 0); \n
                    INSERT INTO system_logs (client_id, event_type, message) VALUES ($clean_id, 'NEW_MEMBER_ACTIVATION', '$msg')";

            $db->multi_query($sql);

            return [
                "tier"   => "BRONZE",
                "spent"  => 0,
                "status" => "NEW ENROLLMENT"
            ];
        }
    } catch (Exception $e) {
        error_log("Failed to process client ID $clean_id: " . $e->getMessage());
        return null;
    }
}

$input_id = (int)$_GET['id'] ?? null;
$report_data = process_loyalty_sync($db, $input_id);

echo "<h1>Corporate Loyalty Dashboard</h1>";
$db->close();