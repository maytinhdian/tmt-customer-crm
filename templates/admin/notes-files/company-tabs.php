<?php

/** @var string $entity_type */
/** @var int    $entity_id   */
/** @var array  $notes       */
/** @var array  $files       */

use TMT\CRM\Application\DTO\NoteDTO;
use TMT\CRM\Application\DTO\FileDTO;
?>
<div class="wrap tmt-notes-files">
    <h2 class="title"><?php esc_html_e('Ghi chú/Tài liệu (Công ty)', 'tmt-crm'); ?></h2>

    <div class="card" style="padding:16px;margin-top:12px;">
        <h3><?php esc_html_e('Thêm ghi chú', 'tmt-crm'); ?></h3>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="tmt_crm_add_note">
            <input type="hidden" name="entity_type" value="company">
            <input type="hidden" name="entity_id" value="<?php echo (int)$entity_id; ?>">
            <?php wp_nonce_field('tmt_crm_add_note'); ?>
            <p><textarea name="content" rows="4" style="width:100%" required></textarea></p>
            <p><button class="button button-primary"><?php esc_html_e('Thêm ghi chú', 'tmt-crm'); ?></button></p>
        </form>

        <hr>

        <h3><?php esc_html_e('Danh sách ghi chú', 'tmt-crm'); ?></h3>
        <ul>
            <?php /** @var NoteDTO $n */ foreach ($notes as $n): ?>
                <li style="margin-bottom:8px;border-bottom:1px solid #e3e3e3;padding-bottom:8px;">
                    <div><?php echo esc_html($n->content); ?></div>
                    <small><?php echo esc_html(sprintf(__('Bởi UserID %d lúc %s', 'tmt-crm'), $n->created_by, $n->created_at ?? '')); ?></small>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;margin-left:8px;">
                        <input type="hidden" name="action" value="tmt_crm_delete_note">
                        <input type="hidden" name="note_id" value="<?php echo (int)$n->id; ?>">
                        <?php wp_nonce_field('tmt_crm_delete_note'); ?>
                        <button class="button-link delete-link" onclick="return confirm('<?php echo esc_js(__('Xoá ghi chú?', 'tmt-crm')); ?>');"><?php esc_html_e('Xoá', 'tmt-crm'); ?></button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="card" style="padding:16px;margin-top:12px;">
        <h3><?php esc_html_e('Đính kèm file (Media)', 'tmt-crm'); ?></h3>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="tmt_crm_attach_file">
            <input type="hidden" name="entity_type" value="company">
            <input type="hidden" name="entity_id" value="<?php echo (int)$entity_id; ?>">
            <?php wp_nonce_field('tmt_crm_attach_file'); ?>
            <p>
                <input type="number" name="attachment_id" placeholder="<?php esc_attr_e('Attachment ID', 'tmt-crm'); ?>" required>
                <button class="button"><?php esc_html_e('Đính kèm', 'tmt-crm'); ?></button>
            </p>
        </form>

        <hr>

        <h3><?php esc_html_e('Danh sách tài liệu', 'tmt-crm'); ?></h3>
        <ul>
            <?php /** @var FileDTO $f */ foreach ($files as $f): ?>
                <li style="margin-bottom:8px;border-bottom:1px solid #e3e3e3;padding-bottom:8px;">
                    <a href="<?php echo esc_url(wp_get_attachment_url($f->attachment_id)); ?>" target="_blank">
                        <?php echo esc_html(get_the_title($f->attachment_id) ?: ('#' . $f->attachment_id)); ?>
                    </a>
                    <small style="margin-left:6px;"><?php echo esc_html(sprintf(__('Upload bởi UserID %d lúc %s', 'tmt-crm'), $f->uploaded_by, $f->uploaded_at ?? '')); ?></small>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;margin-left:8px;">
                        <input type="hidden" name="action" value="tmt_crm_detach_file">
                        <input type="hidden" name="file_id" value="<?php echo (int)$f->id; ?>">
                        <?php wp_nonce_field('tmt_crm_detach_file'); ?>
                        <button class="button-link delete-link" onclick="return confirm('<?php echo esc_js(__('Gỡ file này?', 'tmt-crm')); ?>');"><?php esc_html_e('Gỡ', 'tmt-crm'); ?></button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>