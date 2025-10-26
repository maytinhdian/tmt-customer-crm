<?php
/**
 * Partial: templates/admin/core/files/partials/upload.php
 * Props:
 * @var string $entity_type
 * @var int    $entity_id
 */

$upload_action = admin_url('admin-post.php?action=tmt_crm_playground_upload');
$nonce_upload  = wp_create_nonce('tmt_crm_playground_upload');
?>
<h2><?php _e('Upload', 'tmt-crm'); ?></h2>
<form method="post" action="<?php echo esc_url($upload_action); ?>" enctype="multipart/form-data">
  <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_upload); ?>">
  <input type="hidden" name="action" value="tmt_crm_playground_upload">
  <table class="form-table" role="presentation">
    <tr>
      <th><label for="entity_type"><?php _e('Entity Type', 'tmt-crm'); ?></label></th>
      <td><input id="entity_type" name="entity_type" type="text" class="regular-text" value="<?php echo esc_attr($entity_type); ?>"></td>
    </tr>
    <tr>
      <th><label for="entity_id"><?php _e('Entity ID', 'tmt-crm'); ?></label></th>
      <td><input id="entity_id" name="entity_id" type="number" class="small-text" value="<?php echo esc_attr((string)$entity_id); ?>" min="1"></td>
    </tr>
    <tr>
      <th><label for="file"><?php _e('File', 'tmt-crm'); ?></label></th>
      <td><input id="file" name="file" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" required></td>
    </tr>
  </table>
  <p><button class="button button-primary"><?php _e('Upload file', 'tmt-crm'); ?></button></p>
</form>
