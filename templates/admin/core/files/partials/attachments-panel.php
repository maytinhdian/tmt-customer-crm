<?php
/**
 * Partial: templates/admin/core/files/partials/attachments-panel.php
 *
 * Reusable attachments panel for any module.
 * Props:
 *  - string $entity_type             (required)
 *  - int    $entity_id               (required)
 *  - array  $meta = []               (optional, naming strategy hints: tag, license_code, ...)
 *  - bool   $allow_delete = false    (optional)
 *  - array  $files = null            (optional, FileDTO[]; if null we query by entity)
 */

use TMT\CRM\Shared\Presentation\Support\View;
use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Files\Domain\Repositories\FileRepositoryInterface;

$entity_type   = isset($entity_type) ? (string)$entity_type : '';
$entity_id     = isset($entity_id) ? (int)$entity_id : 0;
$meta          = isset($meta) && is_array($meta) ? $meta : [];
$allow_delete  = !empty($allow_delete);

if (!isset($files) || !is_array($files)) {
  /** @var FileRepositoryInterface $repo */
  $repo  = Container::get(FileRepositoryInterface::class);
  $files = $repo->findByEntity($entity_type, $entity_id, false);
}

// Endpoints
$action_upload = admin_url('admin-post.php?action=tmt_crm_file_upload'); // requires UploadController
$nonce_upload  = wp_create_nonce('tmt_crm_file_upload');

$view_url = function(int $id) {
  return wp_nonce_url(admin_url('admin-post.php?action=tmt_crm_view_file&file_id=' . $id), 'tmt_crm_view_file_' . $id);
};
$download_url = function(int $id) {
  return wp_nonce_url(admin_url('admin-post.php?action=tmt_crm_download_file&file_id=' . $id), 'tmt_crm_download_file_' . $id);
};
$delete_url = function(int $id) use ($entity_type, $entity_id) {
  return wp_nonce_url(admin_url('admin-post.php?action=tmt_crm_playground_delete&file_id=' . $id . '&entity_type=' . urlencode($entity_type) . '&entity_id=' . (int)$entity_id), 'tmt_crm_playground_delete_' . $id);
};
$redirect = (is_admin() ? (wp_get_referer() ?: admin_url()) : home_url());
?>
<style>
  .tmtcrm-files-panel .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:12px; }
  .tmtcrm-files-panel .card { border:1px solid #e5e7eb; border-radius:10px; padding:10px; background:#fff; }
  .tmtcrm-files-panel .thumb { height:120px; background:#fafafa; display:flex; align-items:center; justify-content:center; border-radius:8px; overflow:hidden; }
  .tmtcrm-files-panel .thumb img { max-width:100%; max-height:100%; display:block; }
  .tmtcrm-files-panel .meta { font-size:12px; color:#555; margin-top:6px; }
  .tmtcrm-files-overlay { position:fixed; inset:0; z-index:99999; background:rgba(0,0,0,.95); display:none; align-items:center; justify-content:center; }
  .tmtcrm-files-overlay.show { display:flex; }
  .tmtcrm-files-overlay .viewer { width:96vw; height:92vh; display:flex; flex-direction:column; gap:12px; }
  .tmtcrm-files-overlay .media { flex:1 1 auto; background:#000; border-radius:10px; overflow:hidden; display:flex; align-items:center; justify-content:center; }
  .tmtcrm-files-overlay img, .tmtcrm-files-overlay iframe { width:100%; height:100%; object-fit:contain; border:0; }
  .tmtcrm-files-overlay .bar { display:flex; gap:8px; align-items:center; justify-content:flex-end; color:#ddd; }
  .tmtcrm-files-overlay .btn { background:#2271b1; color:#fff; padding:6px 12px; border-radius:6px; text-decoration:none; }
  .tmtcrm-files-overlay .btn.secondary { background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.25); }
  .tmtcrm-files-overlay .close { position:absolute; top:8px; right:8px; background:rgba(255,255,255,.12); color:#fff; border:1px solid rgba(255,255,255,.2); padding:6px 10px; border-radius:8px; cursor:pointer; }
</style>

<div class="tmtcrm-files-panel">
  <h3><?php echo esc_html__('Tệp đính kèm', 'tmt-crm'); ?></h3>

  <form method="post" action="<?php echo esc_url($action_upload); ?>" enctype="multipart/form-data" style="margin-bottom:12px">
    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_upload); ?>">
    <input type="hidden" name="_redirect" value="<?php echo esc_attr($redirect); ?>">
    <input type="hidden" name="action" value="tmt_crm_file_upload">
    <input type="hidden" name="entity_type" value="<?php echo esc_attr($entity_type); ?>">
    <input type="hidden" name="entity_id" value="<?php echo esc_attr((string)$entity_id); ?>">
    <?php foreach ($meta as $k=>$v): ?>
      <input type="hidden" name="meta[<?php echo esc_attr($k); ?>]" value="<?php echo esc_attr((string)$v); ?>">
    <?php endforeach; ?>
    <input type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.pdf" required> 
    <button class="button button-primary"><?php _e('Tải lên','tmt-crm'); ?></button>
  </form>

  <?php if (empty($files)): ?>
    <p><em><?php _e('Chưa có tệp nào.','tmt-crm'); ?></em></p>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($files as $f): ?>
        <?php
          $is_image = strpos((string)$f->mime, 'image/') === 0;
          $v = $view_url((int)$f->id);
          $d = $download_url((int)$f->id);
          $del = $allow_delete ? $delete_url((int)$f->id) : null;
        ?>
        <div class="card" data-file-id="<?php echo (int)$f->id; ?>" data-file-name="<?php echo esc_attr($f->originalName); ?>" data-file-mime="<?php echo esc_attr($f->mime ?: ''); ?>">
          <div class="thumb">
            <?php if ($is_image): ?>
              <img src="<?php echo esc_url($v); ?>" alt="">
            <?php else: ?>
              <span><?php echo esc_html($f->mime ?: 'file'); ?></span>
            <?php endif; ?>
          </div>
          <div class="meta" title="<?php echo esc_attr($f->originalName); ?>">
            <?php echo esc_html(mb_strimwidth($f->originalName, 0, 40, '…')); ?>
          </div>
          <div style="margin-top:6px; display:flex; gap:6px; flex-wrap:wrap;">
            <a class="button button-small" href="<?php echo esc_url($d); ?>"><?php _e('Tải','tmt-crm'); ?></a>
            <button class="button button-small" data-tmtcrm-preview="<?php echo (int)$f->id; ?>"><?php _e('Xem','tmt-crm'); ?></button>
            <?php if ($allow_delete): ?>
              <a class="button button-small button-link-delete" href="<?php echo esc_url($del); ?>" onclick="return confirm('<?php echo esc_js(__('Delete this file?', 'tmt-crm')); ?>')"><?php _e('Xoá','tmt-crm'); ?></a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Fullscreen overlay -->
<div class="tmtcrm-files-overlay" id="tmtcrm-files-overlay" aria-modal="true" role="dialog" tabindex="-1">
  <button class="close" id="tmtcrm-files-close" title="<?php esc_attr_e('Đóng (Esc)','tmt-crm'); ?>">×</button>
  <div class="viewer">
    <div class="media" id="tmtcrm-files-media"></div>
    <div class="bar">
      <div style="margin-right:auto" id="tmtcrm-files-meta"></div>
      <a class="btn secondary" id="tmtcrm-files-open" target="_blank"><?php _e('Mở tab mới','tmt-crm'); ?></a>
      <a class="btn" id="tmtcrm-files-download"><?php _e('Tải xuống','tmt-crm'); ?></a>
    </div>
  </div>
</div>

<script>
(function(){
  const overlay = document.getElementById('tmtcrm-files-overlay');
  const media   = document.getElementById('tmtcrm-files-media');
  const metaEl  = document.getElementById('tmtcrm-files-meta');
  const btnOpen = document.getElementById('tmtcrm-files-open');
  const btnDown = document.getElementById('tmtcrm-files-download');
  const btnClose= document.getElementById('tmtcrm-files-close');

  function openOverlay(fileId, mime, name){
    const view = '<?php echo esc_js(admin_url('admin-post.php?action=tmt_crm_view_file&file_id=')); ?>' + fileId + '&_wpnonce=' + encodeURIComponent('<?php echo wp_create_nonce('tmt_crm_view_file_'); ?>' + fileId);
    const down = '<?php echo esc_js(admin_url('admin-post.php?action=tmt_crm_download_file&file_id=')); ?>' + fileId + '&_wpnonce=' + encodeURIComponent('<?php echo wp_create_nonce('tmt_crm_download_file_'); ?>' + fileId);

    media.innerHTML = '';
    if (mime && mime.indexOf('image/') === 0) {
      const img = document.createElement('img'); img.src = view; media.appendChild(img);
    } else if (mime === 'application/pdf' || (name||'').toLowerCase().endsWith('.pdf')) {
      const ifr = document.createElement('iframe'); ifr.src=view; media.appendChild(ifr);
    } else {
      media.innerHTML = '<div style="color:#fff;text-align:center;padding:24px"><?php echo esc_js(__('File không hỗ trợ xem trực tiếp.', 'tmt-crm')); ?></div>';
    }

    metaEl.textContent = (name||'') + (mime ? ' • ' + mime : '');
    btnOpen.href = view;
    btnDown.href = down;

    overlay.classList.add('show');
    overlay.focus();
  }
  function close(){ overlay.classList.remove('show'); }

  document.addEventListener('click', function(e){
    const el = e.target.closest('[data-tmtcrm-preview]');
    if (el) {
      e.preventDefault();
      const card = el.closest('.card');
      openOverlay(card.getAttribute('data-file-id'), card.getAttribute('data-file-mime'), card.getAttribute('data-file-name'));
    }
    if (e.target === overlay) close();
  });

  btnClose?.addEventListener('click', close);
  document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') close(); });
})();
</script>
