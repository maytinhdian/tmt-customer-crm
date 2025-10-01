<?php
declare(strict_types=1);

namespace TMT\CRM\Core\ExportImport\Presentation\Admin\Controller;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\ExportImport\Application\Services\{ExportService, ImportService};

final class ExportImportController
{
    public static function handle_export_start(): void
    {
        check_admin_referer('tmt_crm_export_start');
        $entity  = isset($_POST['entity_type']) ? sanitize_text_field((string)$_POST['entity_type']) : 'company';
        $columns_text = isset($_POST['columns_text']) ? sanitize_text_field((string)$_POST['columns_text']) : '';
        $columns = array_values(array_filter(array_map('trim', explode(',', $columns_text))));
        $filters = isset($_POST['filters']) && is_array($_POST['filters']) ? array_map('sanitize_text_field', $_POST['filters']) : [];

        /** @var ExportService $svc */
        $svc = Container::instance()->get(ExportService::class);
        $job = $svc->start_export($entity, $filters, $columns, get_current_user_id());

        if ($job->status === 'done' && $job->file_path) {
            wp_safe_redirect(add_query_arg(['download' => base64_encode($job->file_path)], admin_url('admin.php?page=tmt-crm-export-import')));
        } else {
            wp_safe_redirect(add_query_arg(['notice' => 'export_failed'], admin_url('admin.php?page=tmt-crm-export-import')));
        }
        exit;
    }

    public static function handle_import_preview(): void
    {
        check_admin_referer('tmt_crm_import_preview');
        $entity = isset($_POST['entity_type']) ? sanitize_text_field((string)$_POST['entity_type']) : 'company';
        $has_header = !empty($_POST['has_header']);

        // MVP: xử lý upload đơn giản
        if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            wp_die('No file');
        }
        $uploaded = wp_handle_upload($_FILES['file'], ['test_form' => false]);
        if (!empty($uploaded['error'])) { wp_die(esc_html($uploaded['error'])); }

        /** @var ImportService $svc */
        $svc = Container::instance()->get(ImportService::class);
        $job = $svc->create_job($entity, (string)$uploaded['file'], $has_header, get_current_user_id());

        // mapping rỗng lúc preview
        $svc->preview($job, []);

        wp_safe_redirect(add_query_arg([
            'page' => 'tmt-crm-export-import',
            'import_job' => $job->id,
            'step' => 'map'
        ], admin_url('admin.php')));
        exit;
    }

    public static function handle_import_commit(): void
    {
        check_admin_referer('tmt_crm_import_commit');
        $job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
        if ($job_id <= 0) { wp_die('Invalid job'); }

        /** @var ImportService $svc */
        $svc = Container::instance()->get(ImportService::class);
        $job = $svc->find_job_by_id($job_id);
        if (!$job) { wp_die('Job not found'); }

        // Parse mapping_text -> array
        $mapping_text = (string) ($_POST['mapping_text'] ?? '');
        $mapping = [];
        foreach (preg_split('/\r?\n/', $mapping_text) as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, ':') === false) continue;
            [$src, $dst] = array_map('trim', explode(':', $line, 2));
            if ($src !== '' && $dst !== '') { $mapping[$src] = $dst; }
        }
        $job->mapping = $mapping;

        $svc->commit($job);

        wp_safe_redirect(add_query_arg(['page' => 'tmt-crm-export-import', 'import_done' => 1], admin_url('admin.php')));
        exit;
    }
}
