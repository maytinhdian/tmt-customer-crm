<?php

/**
 * TMT CRM – Uninstall handler
 *
 * Mặc định chỉ xoá option nhỏ. KHÔNG xoá bảng dữ liệu trừ khi:
 * - define('TMT_CRM_DELETE_DATA_ON_UNINSTALL', true) trong wp-config.php
 *   HOẶC
 * - update_option('tmt_crm_delete_on_uninstall', 'yes')
 * // Trong file (file chính) tmt-customer-crm.php  (boostrap – file chính)
 *   register_uninstall_hook(__FILE__, ['TMT\\CRM\\Infrastructure\\Migrations\\Uninstaller', 'run']);
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Hàm tiện ích: dọn option/transient của plugin trên 1 site.
 */
function tmt_crm_cleanup_site_level(): void
{
    // Xoá option cấu hình/phiên bản
    delete_option('tmt_crm_version');
    delete_option('tmt_crm_db_version');
    delete_option('tmt_crm_settings');            // nếu có
    delete_option('tmt_crm_delete_on_uninstall'); // cờ người dùng đã chọn

    // Xoá site transients nếu bạn dùng (ví dụ)
    // delete_site_transient('tmt_crm_something_cache');
}

/**
 * Hàm tiện ích: xoá bảng dữ liệu của plugin trên 1 site (chỉ khi người dùng xác nhận).
 * ⚠️ Cực kỳ cẩn thận, thao tác không thể phục hồi.
 */
function tmt_crm_drop_tables_for_site(\wpdb $wpdb): void
{
    // Danh sách bảng do plugin tạo
    $tables = [
        "{$wpdb->prefix}tmt_crm_customers",
        "{$wpdb->prefix}tmt_crm_companies",
        "{$wpdb->prefix}tmt_crm_company_contacts",  // nếu có
        "{$wpdb->prefix}tmt_crm_customer_company",  // nếu có bảng liên kết
        // ... thêm các bảng khác nếu plugin của bạn có tạo
    ];

    foreach ($tables as $tbl) {
        // Phòng tránh SQL injection: $tbl được build từ $wpdb->prefix + tên tĩnh
        $wpdb->query("DROP TABLE IF EXISTS `{$tbl}`");
    }
}

/**
 * Quyết định có xoá dữ liệu hay không.
 * - Ưu tiên hằng số (dev/admin chủ động).
 * - Nếu không có hằng số, kiểm tra option cấu hình của plugin.
 */
function tmt_crm_should_hard_delete(): bool
{
    if (defined('TMT_CRM_DELETE_DATA_ON_UNINSTALL')) {
        return (bool) TMT_CRM_DELETE_DATA_ON_UNINSTALL;
    }
    // Cho phép người dùng bật trong UI: Settings -> tick “Xoá dữ liệu khi gỡ plugin”
    $flag = get_option('tmt_crm_delete_on_uninstall', 'no');
    return ($flag === 'yes' || $flag === '1' || $flag === 1);
}

/**
 * Xử lý single-site hoặc multisite.
 */
global $wpdb;

if (is_multisite()) {
    // Lưu lại blog hiện tại
    $current_blog_id = get_current_blog_id();
    $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

    foreach ($blog_ids as $blog_id) {
        switch_to_blog((int)$blog_id);

        tmt_crm_cleanup_site_level();

        if (tmt_crm_should_hard_delete()) {
            tmt_crm_drop_tables_for_site($wpdb);
        }
    }

    // Trở về blog ban đầu
    switch_to_blog((int)$current_blog_id);

    // Nếu có dùng site-option cấp network, dọn ở đây
    // delete_site_option('tmt_crm_network_settings');

} else {
    // Single site
    tmt_crm_cleanup_site_level();

    if (tmt_crm_should_hard_delete()) {
        tmt_crm_drop_tables_for_site($wpdb);
    }
}
