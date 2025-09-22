<?php

/**
 * Template: Notification Center
 * Vị trí: templates/admin/notifications/index.php
 * @var array $filters
 * @var array $items // DeliveryDTO[]
 */
defined('ABSPATH') || exit();

// Phòng thủ: nếu $filters chưa được inject đúng dạng
$filters = (isset($filters) && is_array($filters)) ? $filters : [];

$status  = isset($filters['status'])  ? (string)$filters['status']  : '';
$channel = isset($filters['channel']) ? (string)$filters['channel'] : '';
$event   = isset($filters['event'])   ? (string)$filters['event']   : '';
?>
<form method="get" action="">
    <input type="hidden" name="page" value="tmt-crm-notifications" />
    <div style="display:flex; gap:8px; align-items:center; margin:12px 0;">
        <select name="channel">
            <option value=""><?php echo esc_html__('Tất cả kênh', 'tmt-crm'); ?></option>
            <option value="notice" <?php selected($channel, 'notice'); ?>>Admin Notice</option>
            <option value="email" <?php selected($channel, 'email'); ?>>Email</option>
            <option value="webhook" <?php selected($channel, 'webhook'); ?>>Webhook</option>
        </select>


        <select name="status">
            <option value=""><?php echo esc_html__('Tất cả trạng thái', 'tmt-crm'); ?></option>
            <option value="unread" <?php selected($status, 'unread'); ?>><?php echo esc_html__('Chưa đọc', 'tmt-crm'); ?></option>
            <option value="read" <?php selected($status, 'read'); ?>><?php echo esc_html__('Đã đọc', 'tmt-crm'); ?></option>
            <option value="failed" <?php selected($status, 'failed'); ?>><?php echo esc_html__('Lỗi', 'tmt-crm'); ?></option>
            <option value="sent" <?php selected($status, 'sent'); ?>><?php echo esc_html__('Đã gửi', 'tmt-crm'); ?></option>
        </select>


        <input type="text" name="event" placeholder="Event key..." value="<?php echo esc_attr($event); ?>" />
        <button class="button button-primary" type="submit"><?php echo esc_html__('Lọc', 'tmt-crm'); ?></button>
    </div>
</form>


<table class="widefat fixed striped">
    <thead>
        <tr>
            <th><?php echo esc_html__('ID', 'tmt-crm'); ?></th>
            <th><?php echo esc_html__('Kênh', 'tmt-crm'); ?></th>
            <th><?php echo esc_html__('Người nhận', 'tmt-crm'); ?></th>
            <th><?php echo esc_html__('Trạng thái', 'tmt-crm'); ?></th>
            <th><?php echo esc_html__('Gửi lúc', 'tmt-crm'); ?></th>
            <th><?php echo esc_html__('Thao tác', 'tmt-crm'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($items)) : ?>
            <?php foreach ($items as $d) : ?>
                <tr>
                    <td><?php echo (int) $d->id; ?></td>
                    <td><?php echo esc_html($d->channel); ?></td>
                    <td><?php echo esc_html($d->recipient_type . ': ' . $d->recipient_value); ?></td>
                    <td><?php echo esc_html($d->status); ?></td>
                    <td><?php echo esc_html($d->sent_at ?? ''); ?></td>
                    <td>
                        <?php if ($d->status === 'unread') : ?>
                            <button class="button mark-read" data-id="<?php echo (int) $d->id; ?>"><?php echo esc_html__('Đánh dấu đã đọc', 'tmt-crm'); ?></button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="6"><?php echo esc_html__('Chưa có thông báo phù hợp.', 'tmt-crm'); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
</div>


<script>
    // Gọi AJAX mark-read
    (function() {
        document.addEventListener('click', function(e) {
            if (!e.target.classList.contains('mark-read')) return;
            e.preventDefault();
            const id = e.target.getAttribute('data-id');
            const form = new FormData();
            form.append('action', 'tmt_crm_notifications_mark_read');
            form.append('delivery_id', id);
            fetch(ajaxurl, {
                    method: 'POST',
                    body: form,
                    credentials: 'same-origin'
                })
                .then(r => r.json()).then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
        });
    })();
</script>