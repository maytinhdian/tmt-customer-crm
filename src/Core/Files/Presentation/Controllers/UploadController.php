<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Files\Presentation\Controllers;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Files\Application\Services\FileService;
use TMT\CRM\Core\Capabilities\Domain\Capability;

final class UploadController
{
    public static function bootstrap(): void
    {
        add_action('admin_post_tmt_crm_file_upload', [self::class, 'handle']);
        // (tuỳ chọn) add_action('admin_post_tmt_crm_file_delete', [self::class,'delete']);
    }

    public static function handle(): void
    {
        $policy = Container::get('core.capabilities.policy_service');
        $policy->ensure_capability(Capability::FILE_CREATE, get_current_user_id(), 'Bạn không có quyền upload file');

        // if (!current_user_can(Capability::FILE_CREATE)) wp_die(__('Not allowed', 'tmt-crm'), 403);
        check_admin_referer('tmt_crm_file_upload');

        $entity_type = sanitize_text_field((string)($_POST['entity_type'] ?? ''));
        $entity_id   = (int)($_POST['entity_id'] ?? 0);
        $redirect    = esc_url_raw((string)($_POST['_redirect'] ?? admin_url()));

        if (!$entity_type || !$entity_id || empty($_FILES['file']['tmp_name'])) {
            wp_safe_redirect(add_query_arg('upload_error', '1', $redirect));
            exit;
        }

        /** @var FileService $svc */
        $svc = Container::get(FileService::class);
        // meta: cho NamingStrategy (vd: license_code, tag…)
        $meta = is_array($_POST['meta'] ?? null) ? array_map('sanitize_text_field', $_POST['meta']) : [];

        // Policy tự kiểm tra trong FileService (nếu bạn đã bật)
        $svc->storeFromUpload($_FILES['file'], $entity_type, $entity_id, get_current_user_id(), $meta);

        wp_safe_redirect(add_query_arg('upload_success', '1', $redirect));
        exit;
    }
}
