<?php

/**
 * Template: Danh sách khách hàng (List View)
 * Nhận từ CustomerScreen::render_list():
 * - $customers (CustomerDTO[]|array)
 * - $total (int), $page (int), $per_page (int)
 * - $message (string), $filters (array), $base_url (string)
 */
defined('ABSPATH') || exit;
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Danh sách khách hàng', 'tmt-crm'); ?></h1>
    <a href="<?php echo esc_url(add_query_arg(['page' => 'tmt-crm-customers', 'action' => 'add'], admin_url('admin.php'))); ?>" class="page-title-action">
        <?php esc_html_e('Thêm mới', 'tmt-crm'); ?>
    </a>
    <hr class="wp-header-end" />

    <?php if ($message === 'created'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Đã tạo khách hàng.', 'tmt-crm'); ?></p>
        </div>
    <?php elseif ($message === 'updated'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Đã cập nhật khách hàng.', 'tmt-crm'); ?></p>
        </div>
    <?php elseif ($message === 'deleted'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Đã xóa khách hàng.', 'tmt-crm'); ?></p>
        </div>
    <?php elseif ($message === 'error' && !empty($_GET['msg'])): ?>
        <div class="notice notice-error">
            <p><?php echo esc_html(wp_unslash($_GET['msg'])); ?></p>
        </div>
    <?php endif; ?>

    <form method="get">
        <input type="hidden" name="page" value="tmt-crm-customers" />
        <p class="search-box">
            <label class="screen-reader-text" for="post-search-input"><?php esc_html_e('Tìm khách hàng:', 'tmt-crm'); ?></label>
            <input type="search" id="post-search-input" name="s" value="<?php echo esc_attr($filters['keyword'] ?? ''); ?>" />
            <select name="type">
                <option value=""><?php esc_html_e('— Loại —', 'tmt-crm'); ?></option>
                <option value="individual" <?php selected(($filters['type'] ?? ''), 'individual'); ?>><?php esc_html_e('Cá nhân', 'tmt-crm'); ?></option>
                <option value="company" <?php selected(($filters['type'] ?? ''), 'company'); ?>><?php esc_html_e('Công ty', 'tmt-crm'); ?></option>
                <option value="partner" <?php selected(($filters['type'] ?? ''), 'partner'); ?>><?php esc_html_e('Đối tác', 'tmt-crm'); ?></option>
            </select>
            <input type="number" name="owner" min="0" placeholder="<?php esc_attr_e('ID người phụ trách', 'tmt-crm'); ?>"
                value="<?php echo isset($filters['owner_id']) ? (int)$filters['owner_id'] : ''; ?>" />
            <input type="submit" class="button" value="<?php esc_attr_e('Lọc', 'tmt-crm'); ?>" />
        </p>
    </form>

    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <th width="6%"><?php esc_html_e('ID', 'tmt-crm'); ?></th>
                <th><?php esc_html_e('Tên khách hàng', 'tmt-crm'); ?></th>
                <th><?php esc_html_e('Email', 'tmt-crm'); ?></th>
                <th><?php esc_html_e('Điện thoại', 'tmt-crm'); ?></th>
                <th><?php esc_html_e('Công ty', 'tmt-crm'); ?></th>
                <th width="14%"><?php esc_html_e('Hành động', 'tmt-crm'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($customers)) : ?>
                <?php foreach ($customers as $c): ?>
                    <?php
                    // $c có thể là DTO (object) hoặc array → hỗ trợ cả hai
                    $cid    = (int)($c->id ?? $c['id'] ?? 0);
                    $name   = $c->name   ?? $c['name']   ?? '';
                    $email  = $c->email  ?? $c['email']  ?? '';
                    $phone  = $c->phone  ?? $c['phone']  ?? '';
                    $company = $c->company ?? $c['company'] ?? '';
                    $edit_url = add_query_arg(['page' => 'tmt-crm-customers', 'action' => 'edit', 'id' => $cid], admin_url('admin.php'));
                    $del_url  = wp_nonce_url(
                        add_query_arg(['action' => 'tmt_crm_customer_delete', 'id' => $cid], admin_url('admin-post.php')),
                        'tmt_crm_customer_delete_' . $cid
                    );
                    ?>
                    <tr>
                        <td><?php echo $cid; ?></td>
                        <td><?php echo esc_html($name); ?></td>
                        <td><?php echo esc_html($email); ?></td>
                        <td><?php echo esc_html($phone); ?></td>
                        <td><?php echo esc_html($company); ?></td>
                        <td>
                            <a href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Sửa', 'tmt-crm'); ?></a>
                            &nbsp;|&nbsp;
                            <a href="<?php echo esc_url($del_url); ?>" onclick="return confirm('<?php echo esc_js(__('Xác nhận xóa khách hàng?', 'tmt-crm')); ?>');">
                                <?php esc_html_e('Xóa', 'tmt-crm'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6"><?php esc_html_e('Chưa có khách hàng nào.', 'tmt-crm'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php
    // Phân trang
    if ($total > $per_page) {
        $total_pages = (int) ceil($total / $per_page);
        echo '<div class="tablenav"><div class="tablenav-pages">';
        echo paginate_links([
            'base'      => add_query_arg(array_merge($_GET, ['paged' => '%#%']), $base_url),
            'format'    => '',
            'current'   => max(1, $page),
            'total'     => $total_pages,
            'prev_text' => __('« Trước', 'tmt-crm'),
            'next_text' => __('Sau »', 'tmt-crm'),
        ]);
        echo '</div></div>';
    }
    ?>
</div>