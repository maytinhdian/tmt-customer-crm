<?php

use TMT\CRM\Presentation\Admin\QuoteScreen;

$list_url = admin_url('admin.php?page=' . QuoteScreen::PAGE_SLUG);
$new_url  = add_query_arg(['page' => QuoteScreen::PAGE_SLUG, 'action' => 'new'], admin_url('admin.php'));
?>
<div class="wrap tmtcrm">
    <h1 class="wp-heading-inline"><?php esc_html_e('Báo giá', 'tmt-crm'); ?></h1>
    <a href="<?php echo esc_url($new_url); ?>" class="page-title-action"><?php _e('Tạo báo giá', 'tmt-crm'); ?></a>
    <hr class="wp-header-end" />

    <div class="card">
        <div class="toolbar">
            <div class="left tabs" role="tablist">
                <a class="tab active" href="<?php echo esc_url($list_url); ?>"><?php _e('Báo giá', 'tmt-crm'); ?></a>
                <a class="tab" href="<?php echo esc_url(admin_url('admin.php?page=tmt-crm-orders')); ?>"><?php _e('Đơn hàng', 'tmt-crm'); ?></a>
                <a class="tab" href="<?php echo esc_url(admin_url('admin.php?page=tmt-crm-invoices')); ?>"><?php _e('Hoá đơn', 'tmt-crm'); ?></a>
            </div>
            <div class="right">
                <form method="get">
                    <input type="hidden" name="page" value="<?php echo esc_attr(QuoteScreen::PAGE_SLUG); ?>" />
                    <div class="search">
                        <input type="text" name="s" placeholder="<?php esc_attr_e('Tìm theo mã/tên KH…', 'tmt-crm'); ?>" value="<?php echo esc_attr($_GET['s'] ?? ''); ?>" />
                        <select name="status">
                            <option value=""><?php _e('Trạng thái…', 'tmt-crm'); ?></option>
                            <option <?php selected(($_GET['status'] ?? '') === 'draft'); ?>>draft</option>
                            <option <?php selected(($_GET['status'] ?? '') === 'sent'); ?>>sent</option>
                            <option <?php selected(($_GET['status'] ?? '') === 'accepted'); ?>>accepted</option>
                        </select>
                        <button class="btn"><?php _e('Lọc', 'tmt-crm'); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="panel">
            <div class="note"><?php _e('Gợi ý: phần dưới có thể thay bằng WP_List_Table (QuoteListTable). Bản này render tĩnh để đối chiếu UI.', 'tmt-crm'); ?></div>
        </div>

        <div class="table-wrap">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Mã', 'tmt-crm'); ?></th>
                        <th><?php _e('Khách hàng', 'tmt-crm'); ?></th>
                        <th><?php _e('Ngày', 'tmt-crm'); ?></th>
                        <th><?php _e('Hết hạn', 'tmt-crm'); ?></th>
                        <th><?php _e('Trạng thái', 'tmt-crm'); ?></th>
                        <th style="text-align:right"><?php _e('Tổng tiền', 'tmt-crm'); ?></th>
                        <th><?php _e('Thao tác', 'tmt-crm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // dữ liệu mẫu khớp Canvas
                    $rows = [
                        ['QUO-202508-0003', 'CTY Minh Anh', '2025-08-27', '2025-09-05', 'sent', 24500000],
                        ['QUO-202508-0002', 'CTY Sao Bắc', '2025-08-25', '2025-09-01', 'accepted', 12800000],
                        ['QUO-202508-0001', 'Nguyễn Văn A', '2025-08-20', '2025-08-27', 'draft', 3800000],
                    ];
                    foreach ($rows as [$code, $cus, $date, $exp, $st, $total]): ?>
                        <tr>
                            <td><code class="kbd"><?php echo esc_html($code); ?></code></td>
                            <td><?php echo esc_html($cus); ?></td>
                            <td><?php echo esc_html($date); ?></td>
                            <td><?php echo esc_html($exp); ?></td>
                            <td><span class="pill <?php echo esc_attr($st); ?>"><?php echo esc_html($st); ?></span></td>
                            <td style="text-align:right"><?php echo number_format($total, 0, ',', '.'); ?> ₫</td>
                            <td>
                                <div class="row-actions">
                                    <a class="btn small" href="<?php echo esc_url(add_query_arg(['page' => QuoteScreen::PAGE_SLUG, 'action' => 'edit', 'id' => 1], admin_url('admin.php'))); ?>"><?php _e('Xem/Sửa', 'tmt-crm'); ?></a>
                                    <button class="btn small ok" type="button"><?php _e('Chấp nhận', 'tmt-crm'); ?></button>
                                    <button class="btn small primary" type="button"><?php _e('Chuyển đơn hàng', 'tmt-crm'); ?></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>