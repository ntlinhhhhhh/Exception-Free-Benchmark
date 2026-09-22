<?php
/**
 * Testbed 3 — CMS Theme Customization (Source)
 *
 * Saves the site's visual theme configuration (font family and background color)
 * submitted by an administrator. Settings are encoded as a JSON blob and stored
 * in site_options using a prepared statement.
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../config.php';
$db_name = getenv('DB_NAME') ?: 'silent_testbed';

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);

$raw_font = $_POST['font'] ?? 'Arial';

if (strlen($raw_font) > 100) {
    $raw_font = substr($raw_font, 0, 100);
}

$theme_config = [
    'color'       => $_POST['color'] ?? 'white',
    'font_family' => $raw_font
];

// Taint is lost here: the payload is embedded inside a JSON string,
// making it invisible to static analysis until json_decode() in the Sink.
$json_data = json_encode($theme_config);

$stmt = $db->prepare("UPDATE site_options SET config_value = ? WHERE option_name = 'theme_settings'");
$stmt->bind_param("s", $json_data);

try {
    $stmt->execute();
    echo "Theme saved successfully! (HTTP 200 OK)";
} catch (Exception $e) {
    error_log("DB Error: " . $e->getMessage());
} finally {
    $db->close();
}