<?php

namespace TMT\CRM\Infrastructure\Security;

defined('ABSPATH') || exit;

final class CustomerRoleService
{
    /** Tạo/cập nhật quyền cho Administrator + tạo roles CRM */
    public static function install(): void
    {
        // 2.1 Gán full quyền cho Administrator
        if ($admin = get_role('administrator')) {
            foreach (Capability::fullSet() as $cap => $grant) {
                if ($grant && !$admin->has_cap($cap)) {
                    $admin->add_cap($cap);
                }
            }
        }

        // 2.2 Tạo role CRM Manager (đầy đủ quyền CRM, không đụng Setting WP)
        if (!get_role('tmt_crm_manager')) {
            add_role(
                'tmt_crm_manager',
                'manage_tmt_crm_companies',
                'CRM Manager',
                array_merge(['read' => true], Capability::fullSet()) // 👈 thêm read
            );
        } else {
            // nếu đã tồn tại: đảm bảo đủ quyền
            $mgr = get_role('tmt_crm_manager');
            foreach (Capability::fullSet() as $cap => $grant) {
                if ($grant && !$mgr->has_cap($cap)) {
                    $mgr->add_cap($cap);
                }
            }
        }

        // 2.3 Tạo role CRM Staff (không được xoá)
        if (!get_role('tmt_crm_staff')) {
            add_role(
                'tmt_crm_staff',
                'manage_tmt_crm_companies',
                'CRM Staff',
                array_merge(['read' => true], Capability::staffSet()) // 👈 thêm read
            );
        } else {
            $st = get_role('tmt_crm_staff');
            foreach (Capability::staffSet() as $cap => $grant) {
                if ($grant && !$st->has_cap($cap)) {
                    $st->add_cap($cap);
                }
                if (!$grant && $st->has_cap($cap)) {
                    $st->remove_cap($cap);
                }
            }
        }
    }





    /** (Tuỳ chọn) Gỡ role khi uninstall plugin */
    public static function uninstall(): void
    {
        // Lưu ý: thường KHÔNG xoá role để tránh mất phân quyền ngoài ý muốn.
        // Nếu muốn xoá, bỏ comment:
        // remove_role('tmt_crm_manager');
        // remove_role('tmt_crm_staff');
    }
}
