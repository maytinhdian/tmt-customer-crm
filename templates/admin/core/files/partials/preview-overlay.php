<?php
/**
 * Partial: templates/admin/core/files/partials/preview-overlay.php
 * Props:
 * @var object $selected   (FileDTO)
 * @var string $entity_type
 * @var int    $entity_id
 */

use TMT\CRM\Core\Files\Presentation\Controllers\FilesPlaygroundController;

$view_url = function(int $id) {
  return wp_nonce_url(admin_url('admin-post.php?action=tmt_crm_view_file&file_id=' . $id), 'tmt_crm_view_file_' . $id);
};
$download_url = function(int $id) {
  return wp_nonce_url(admin_url('admin-post.php?action=tmt_crm_download_file&file_id=' . $id), 'tmt_crm_download_file_' . $id);
};

$base_url = admin_url('tools.php?page=' . FilesPlaygroundController::SLUG);
$back_url = add_query_arg(['entity_type'=>$entity_type,'entity_id'=>$entity_id], $base_url);

$is_image = strpos((string)$selected->mime, 'image/') === 0;
$is_pdf   = (string)$selected->mime === 'application/pdf' || preg_match('~pdf$~i', (string)$selected->originalName);
$src_view = $view_url((int)$selected->id);
?>
<style>
  .tmtcrm-overlay{position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.95);display:flex;align-items:center;justify-content:center}
  .tmtcrm-viewer{position:relative;width:96vw;height:92vh;display:flex;flex-direction:column;gap:12px}
  .tmtcrm-media{flex:1 1 auto;background:#000;border-radius:10px;overflow:hidden;display:flex;align-items:center;justify-content:center}
  .tmtcrm-media img,.tmtcrm-media iframe{width:100%;height:100%;object-fit:contain;border:0}
  .tmtcrm-toolbar{display:flex;gap:8px;align-items:center;justify-content:flex-end;color:#ddd}
  .tmtcrm-btn{background:#2271b1;color:#fff;padding:6px 12px;border-radius:6px;text-decoration:none}
  .tmtcrm-btn.secondary{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25)}
  .tmtcrm-close{position:absolute;top:8px;right:8px;background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.2);padding:6px 10px;border-radius:8px;cursor:pointer}
  .tmtcrm-close:hover{background:rgba(255,255,255,.2)}
</style>

<div class="tmtcrm-overlay" id="tmtcrm-overlay" tabindex="-1" role="dialog" aria-modal="true">
  <div class="tmtcrm-viewer">
    <button class="tmtcrm-close" id="tmtcrm-close" title="<?php esc_attr_e('Đóng (Esc)', 'tmt-crm'); ?>">×</button>
    <div class="tmtcrm-media">
      <?php if ($is_image): ?>
        <img src="<?php echo esc_url($src_view); ?>" alt="">
      <?php elseif ($is_pdf): ?>
        <iframe src="<?php echo esc_url($src_view); ?>" loading="eager"></iframe>
      <?php else: ?>
        <div style="color:#fff; text-align:center; padding:24px;">
          <p><strong><?php echo esc_html($selected->mime ?: 'application/octet-stream'); ?></strong></p>
          <p><?php _e('Loại tệp này không hỗ trợ xem trực tiếp. Bạn có thể tải xuống.', 'tmt-crm'); ?></p>
        </div>
      <?php endif; ?>
    </div>
    <div class="tmtcrm-toolbar">
      <div style="margin-right:auto;overflow:auto;white-space:nowrap">
        <?php echo esc_html($selected->originalName); ?> •
        <?php echo esc_html($selected->mime ?: ''); ?> •
        <?php echo esc_html(size_format((float)$selected->sizeBytes)); ?> •
        <?php echo esc_html($selected->entityType . '#' . (int)$selected->entityId); ?> •
        <code style="color:#fff;background:rgba(255,255,255,.08);padding:2px 6px;border-radius:6px;"><?php echo esc_html($selected->path); ?></code>
      </div>
      <a class="tmtcrm-btn secondary" href="<?php echo esc_url($src_view); ?>" target="_blank"><?php _e('Mở tab mới', 'tmt-crm'); ?></a>
      <a class="tmtcrm-btn" href="<?php echo esc_url($download_url((int)$selected->id)); ?>"><?php _e('Tải xuống', 'tmt-crm'); ?></a>
      <a class="tmtcrm-btn secondary" id="tmtcrm-back" href="<?php echo esc_url($back_url); ?>"><?php _e('Đóng xem', 'tmt-crm'); ?></a>
    </div>
  </div>
</div>
<script>
  (function(){ const ov=document.getElementById('tmtcrm-overlay'); const closeBtn=document.getElementById('tmtcrm-close');
    function close(){ window.location.href = <?php echo json_encode($back_url); ?>; }
    closeBtn?.addEventListener('click', close);
    ov?.addEventListener('click', e => { if (e.target===ov) close(); });
    document.addEventListener('keydown', e => { if (e.key==='Escape') close(); });
    setTimeout(()=>ov?.focus(),0);
  })();
</script>
