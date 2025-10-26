<?php
/**
 * Partial: templates/admin/core/files/partials/files-table.php
 * Props:
 * @var string $entity_type
 * @var int    $entity_id
 * @var array  $files (FileDTO[])
 */

use TMT\CRM\Core\Files\Presentation\Controllers\FilesPlaygroundController;

$base_url = admin_url('tools.php?page=' . FilesPlaygroundController::SLUG);
$inpage = function(int $id) use ($base_url, $entity_type, $entity_id) {
  return add_query_arg(['preview' => $id, 'entity_type' => $entity_type, 'entity_id' => $entity_id], $base_url);
};
$view_url = function(int $id) {
  return wp_nonce_url(admin_url('admin-post.php?action=tmt_crm_view_file&file_id=' . $id), 'tmt_crm_view_file_' . $id);
};
$download_url = function(int $id) {
  return wp_nonce_url(admin_url('admin-post.php?action=tmt_crm_download_file&file_id=' . $id), 'tmt_crm_download_file_' . $id);
};
?>
<h2><?php printf(esc_html__('Files of %s #%d','tmt-crm'), esc_html($entity_type), (int)$entity_id); ?></h2>

<?php if (empty($files)): ?>
  <p><em><?php _e('No files.', 'tmt-crm'); ?></em></p>
<?php else: ?>
  <table class="widefat striped">
    <thead>
      <tr>
        <th><?php _e('ID','tmt-crm'); ?></th>
        <th><?php _e('Name','tmt-crm'); ?></th>
        <th><?php _e('MIME','tmt-crm'); ?></th>
        <th><?php _e('Size','tmt-crm'); ?></th>
        <th><?php _e('Uploaded At','tmt-crm'); ?></th>
        <th><?php _e('Actions','tmt-crm'); ?></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($files as $f):
      $delete_url = wp_nonce_url(
        admin_url('admin-post.php?action=tmt_crm_playground_delete&file_id='.$f->id.'&entity_type='.$entity_type.'&entity_id='.$entity_id),
        'tmt_crm_playground_delete_'.$f->id
      ); ?>
      <tr>
        <td><?php echo (int)$f->id; ?></td>
        <td title="<?php echo esc_attr($f->originalName); ?>"><?php echo esc_html(mb_strimwidth($f->originalName, 0, 40, '…')); ?></td>
        <td><?php echo esc_html($f->mime ?: ''); ?></td>
        <td><?php echo esc_html(size_format((float)$f->sizeBytes)); ?></td>
        <td><?php echo esc_html($f->uploadedAt ?: ''); ?></td>
        <td>
          <a class="button button-small" href="<?php echo esc_url($inpage((int)$f->id)); ?>"><?php _e('Xem trong trang', 'tmt-crm'); ?></a>
          <a class="button button-small" href="<?php echo esc_url($view_url((int)$f->id)); ?>" target="_blank"><?php _e('Xem tab mới', 'tmt-crm'); ?></a>
          <a class="button button-small" href="<?php echo esc_url($download_url((int)$f->id)); ?>"><?php _e('Tải', 'tmt-crm'); ?></a>
          <a class="button button-small button-link-delete" href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('Delete this file?')"><?php _e('Xoá', 'tmt-crm'); ?></a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
