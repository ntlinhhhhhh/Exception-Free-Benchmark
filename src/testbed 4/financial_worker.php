<?php
/**
 * Testbed 4 — Financial Report Generation (Sink)
 *
 * Long-running CLI daemon that polls the job queue for pending report requests.
 * For each job, it calculates employee commission (15% of total sales) and
 * applicable tax rates by region, exports the result as a CSV file, and updates
 * the job record with a download URL.
 * Errors are caught internally — the HTTP layer never sees them.
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../config.php';

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);
echo "Financial Report Worker running...\n";

while (true) {
    $res = $db->query("SELECT id, region_filter FROM heavy_report_jobs WHERE status = 'PENDING' LIMIT 1");
    if ($res->num_rows > 0) {
        $job    = $res->fetch_assoc();
        $job_id = (int)$job['id'];
        $region = $job['region_filter'];

        $db->query("UPDATE heavy_report_jobs SET status = 'PROCESSING' WHERE id = $job_id");

        // Dynamic query required to support complex aggregation with JOINs across multiple tables
        $report_sql = "
            SELECT e.emp_name, e.tax_code, SUM(s.transaction_amount) * 0.15 AS commission, t.tax_rate
            FROM employees e
            JOIN sales_transactions s ON e.id = s.emp_id
            JOIN tax_brackets t ON e.salary_tier = t.tier_id
            WHERE e.region = '$region'
            GROUP BY e.id
        ";

        try {
            $data_res = $db->query($report_sql);

            sleep(5);

            $filename     = uniqid('finance_report_') . '.csv';
            $download_dir = '/var/www/html/downloads';
            if (!is_dir($download_dir)) {
                @mkdir($download_dir, 0777, true);
            }
            $filepath = $download_dir . '/' . $filename;

            $file = fopen($filepath, 'w');
            if ($file && $data_res instanceof mysqli_result) {
                fputcsv($file, ['Employee Name', 'Tax Code', 'Calculated Commission', 'Tax Rate']);
                while ($row = $data_res->fetch_assoc()) {
                    fputcsv($file, $row);
                }
                fclose($file);
            }

            $db->query("UPDATE heavy_report_jobs SET status = 'DONE', report_url = '/downloads/$filename' WHERE id = $job_id");
            echo "Successfully compiled report for Job $job_id\n";

        } catch (Throwable $e) {
            $db->query("UPDATE heavy_report_jobs SET status = 'FAILED' WHERE id = $job_id");
            error_log("Job $job_id failed: " . $e->getMessage());
        }
    }
    sleep(2);
}