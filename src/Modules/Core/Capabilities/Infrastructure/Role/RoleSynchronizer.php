<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\Core\Capabilities\Infrastructure\Role;

use TMT\CRM\Domain\Repositories\CapabilitiesRepositoryInterface;
use TMT\CRM\Modules\Core\Capabilities\Domain\Capability;

final class RoleSynchronizer
{
    public function __construct(
        private CapabilitiesRepositoryInterface $repo
    ) {}

    /**
     * Đồng bộ capability vào WP roles theo ma trận.
     * - Đảm bảo administrator luôn sở hữu mọi capability.
     * - Các role khác chỉ theo matrix lưu trong option.
     */
    public function sync_all(): void
    {
        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        $roles = get_editable_roles();
        if (!is_array($roles)) return;

        $matrix = $this->repo->get_matrix();

        // Administrator luôn có tất cả cap
        $matrix['administrator'] = Capability::all();

        foreach ($roles as $role_slug => $role_info) {
            $wp_role = get_role($role_slug);
            if (!$wp_role) continue;

            // Lấy danh sách caps mong muốn cho role
            $desired_caps = $matrix[$role_slug] ?? [];

            // 1) Add: thêm mọi cap mong muốn
            foreach ($desired_caps as $cap) {
                if (!$wp_role->has_cap($cap)) {
                    $wp_role->add_cap($cap);
                }
            }

            // 2) Remove: gỡ những cap không còn trong matrix (chỉ các cap tmt_crm_)
            foreach ($wp_role->capabilities as $cap => $_granted) {
                if (str_starts_with($cap, 'tmt_crm_') && !in_array($cap, $desired_caps, true)) {
                    $wp_role->remove_cap($cap);
                }
            }
        }
    }
}
