<?php
// Central Router — dispatches incoming HTTP requests to the correct testbed handler.
// Each route maps to a specific testbed file based on URL path.

$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// =========================================================================
// Testbed 1: Web Traffic Analytics (Pattern: System-Level Exception Masking)
// Source: POST /api/track          -> records raw visit into raw_traffic
// Sink  : GET  /cron/aggregate_stats -> reads raw_traffic, aggregates into agent_stats (ERRMODE_SILENT)
// =========================================================================
if ($request_path === '/api/track' || $request_path === '/api/track.php') {
    require_once __DIR__ . '/testbed 1/track.php';
    exit;
}
if ($request_path === '/cron/aggregate_stats' || $request_path === '/cron/aggregate_stats.php') {
    require_once __DIR__ . '/testbed 1/aggregate_stats.php';
    exit;
}

// =========================================================================
// Testbed 2.1: CRM Loyalty Enrollment (Pattern: Batching Blackhole — multi_query)
// Source: POST /register -> stores client_name into clients table (Prepared Stmt)
// Sink  : GET  /billing  -> reads client_name, runs multi_query to enroll loyalty (errors swallowed)
// =========================================================================
if ($request_path === '/register' || $request_path === '/register.php') {
    require_once __DIR__ . '/testbed 2.1/register.php';
    exit;
}
if ($request_path === '/billing' || $request_path === '/billing.php') {
    require_once __DIR__ . '/testbed 2.1/billing.php';
    exit;
}

// =========================================================================
// Testbed 2.2: Event Telemetry Fire-and-Forget (Pattern: Async Socket Blackhole — MYSQLI_ASYNC)
// Combined Source+Sink: POST /api/fast_log -> inserts into metric_logs via MYSQLI_ASYNC (result never reaped)
// =========================================================================
if ($request_path === '/api/fast_log' || $request_path === '/api/fast_log.php') {
    require_once __DIR__ . '/testbed 2.2/fast_log.php';
    exit;
}

// =========================================================================
// Testbed 3: CMS Theme Customization (Pattern: Serialization Taint Loss)
// Source: POST /admin/save_theme -> encodes font input into JSON, stores in site_options (Prepared Stmt)
// Sink  : GET  /public/index     -> decodes JSON, injects font value into raw SQL (render_logs)
// =========================================================================
if ($request_path === '/admin/save_theme' || $request_path === '/admin/save_theme.php') {
    require_once __DIR__ . '/testbed 3/save_theme.php';
    exit;
}
if ($request_path === '/public/index' || $request_path === '/public/index.php') {
    require_once __DIR__ . '/testbed 3/index.php';
    exit;
}

// =========================================================================
// Testbed 4: Financial Report Generation (Pattern: Distributed Context Fragmentation)
// Source: POST /api/request_report      -> enqueues region_filter into heavy_report_jobs (Prepared Stmt)
// Poll  : GET  /api/check_status        -> returns job status to frontend
// Sink  : GET  /daemon/financial_worker -> CLI worker reads region_filter, builds raw SQL report
// =========================================================================
if ($request_path === '/api/request_report' || $request_path === '/api/request_report.php') {
    require_once __DIR__ . '/testbed 4/request_report.php';
    exit;
}
if ($request_path === '/api/check_status' || $request_path === '/api/check_status.php') {
    require_once __DIR__ . '/testbed 4/check_status.php';
    exit;
}
if ($request_path === '/daemon/financial_worker' || $request_path === '/daemon/financial_worker.php') {
    require_once __DIR__ . '/testbed 4/financial_worker.php';
    exit;
}
