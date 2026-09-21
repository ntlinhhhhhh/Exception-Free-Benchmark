<?php
/**
 * Testbed 3 — CMS Theme Customization (Sink)
 *
 * Renders the public homepage by loading the active theme configuration from
 * the database, applying the stored font family to the page style, and logging
 * the render event to render_logs.
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../config.php';
$db_name = getenv('DB_NAME') ?: 'silent_testbed';

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);

$res = $db->query("SELECT config_value FROM site_options WHERE option_name = 'theme_settings'");
$theme_json = $res->fetch_assoc()['config_value'];

// json_decode restores the raw payload from the JSON blob stored by save_theme.php
$config = json_decode($theme_json, true);
$font = $config['font_family'];

$log_sql = "INSERT INTO render_logs (element_name, render_time) VALUES ('Font_Loaded: $font', NOW())";
$db->query($log_sql);

echo "<body style='font-family: " . htmlspecialchars($font) . "'>";
echo "<h1>Welcome to CMS</h1>";