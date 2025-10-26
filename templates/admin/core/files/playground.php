<?php
/**
 * Template: templates/admin/core/files/playground.php
 * Props:
 * @var string      $entity_type
 * @var int         $entity_id
 * @var array       $files       (FileDTO[])
 * @var object|null $selected    (FileDTO|null)
 */

use TMT\CRM\Shared\Presentation\Support\View;

?>
<div class="wrap">
  <h1 class="wp-heading-inline"><?php _e('TMT Files Playground', 'tmt-crm'); ?></h1>
  <p class="description"><?php _e('Demo xem file inline và tải xuống (dev only).', 'tmt-crm'); ?></p>
  <hr class="wp-header-end">

  <?php if (!empty($selected)): ?>
    <?php View::render_admin_partial('core/files', 'preview-overlay', [
      'selected'    => $selected,
      'entity_type' => $entity_type,
      'entity_id'   => $entity_id,
    ]); ?>
  <?php endif; ?>

  <?php View::render_admin_partial('core/files', 'upload', [
    'entity_type' => $entity_type,
    'entity_id'   => $entity_id,
  ]); ?>

  <?php View::render_admin_partial('core/files', 'files-table', [
    'entity_type' => $entity_type,
    'entity_id'   => $entity_id,
    'files'       => $files,
  ]); ?>
</div>
