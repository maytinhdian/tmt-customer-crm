<?php

declare(strict_types=1);

namespace TMT\CRM\Infrastructure\Security;

defined('ABSPATH') || exit;

/**
 * Cấp/cập nhật role và gán capability pack.
 * Module mở rộng có thể “bơm thêm” quyền qua filter `tmt_crm_role_packs`.
 */
final class RoleService
{

    /**
     * Gọi ở hook 'init' để cài/đồng bộ role & capabilities.
     */
    public static function install(): void
    {
        self::grant_admin_full_pack();
        self::sync_crm_roles();
    }

    /**
     * Cấp đầy đủ quyền Manager cho Administrator (chuẩn WordPress).
     */
    private static function grant_admin_full_pack(): void
    {
        $admin = get_role('administrator');
        if (!$admin) {
            return;
        }

        foreach (Capability::pack_manager() as $cap => $grant) {
            if ($grant && !$admin->has_cap($cap)) {
                $admin->add_cap($cap);
            }
        }
    }

    /**
     * Tạo/cập nhật các role TMT CRM theo packs.
     * - Mở rộng được qua filter `tmt_crm_role_packs`.
     */
    private static function sync_crm_roles(): void
    {
        $packs = apply_filters('tmt_crm_role_packs', [
            'tmt_crm_manager' => [
                'display_name' => __('TMT CRM Manager', 'tmt-crm'),
                'caps'         => Capability::pack_manager(),
            ],
            'tmt_crm_staff' => [
                'display_name' => __('TMT CRM Staff', 'tmt-crm'),
                'caps'         => Capability::pack_staff(),
            ],
            'tmt_crm_viewer' => [
                'display_name' => __('TMT CRM Viewer', 'tmt-crm'),
                'caps'         => Capability::pack_viewer(),
            ],
        ]);

        foreach ($packs as $role_key => $conf) {
            $role = get_role($role_key);

            if (!$role) {
                // tạo mới
                add_role(
                    $role_key,
                    (string)($conf['display_name'] ?? $role_key),
                    (array)($conf['caps'] ?? [])
                );
                continue;
            }

            // cập nhật idempotent
            foreach ((array)($conf['caps'] ?? []) as $cap => $grant) {
                $grant ? $role->add_cap($cap) : $role->remove_cap($cap);
            }
        }
    }

    /** Không khuyến khích xoá role khi uninstall để tránh mất phân quyền ngoài ý muốn */
    public static function uninstall(): void
    {
        // Nếu thực sự muốn xoá:
        // remove_role('tmt_crm_manager');
        // remove_role('tmt_crm_staff');
        // remove_role('tmt_crm_viewer');
    }
}
