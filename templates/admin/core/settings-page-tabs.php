<?php
/**
 * Biến truyền vào từ SettingsPage::render():
 * @var string $option_key
 * @var string $menu_slug
 * @var string $settings_group
 * @var array  $tabs       [['id'=>'general','title'=>'...'], ...]
 * @var string $active_tab
 */

use TMT\CRM\Core\Settings\SettingsPage;

?>
<div class="wrap" role="region" aria-labelledby="tmt-crm-settings-title">
  <h1 id="tmt-crm-settings-title"><?php echo esc_html__('TMT CRM Settings', 'tmt-crm'); ?></h1>

  <?php
    // 1) Hiện thông báo lỗi/thành công từ Settings API
    settings_errors();
  ?>

  <h2 class="nav-tab-wrapper" role="tablist" aria-label="<?php echo esc_attr__('CRM Settings Tabs', 'tmt-crm'); ?>">
    <?php foreach ($tabs as $t): 
      $is_active = ($t['id'] === $active_tab);
      $active_cls = $is_active ? ' nav-tab-active' : '';
      $url        = is_callable([SettingsPage::class, 'tab_url'])
                      ? SettingsPage::tab_url($t['id'])
                      : esc_url(add_query_arg(['page' => $menu_slug, 'tab' => $t['id']], admin_url('admin.php')));
    ?>
      <a
        href="<?php echo esc_url($url); ?>"
        class="nav-tab<?php echo esc_attr($active_cls); ?>"
        role="tab"
        id="tab-<?php echo esc_attr($t['id']); ?>"
        aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
        aria-controls="panel-<?php echo esc_attr($t['id']); ?>"
      >
        <?php echo esc_html($t['title']); ?>
      </a>
    <?php endforeach; ?>
  </h2>

  <div id="panel-<?php echo esc_attr($active_tab); ?>" role="tabpanel" aria-labelledby="tab-<?php echo esc_attr($active_tab); ?>">
    <form method="post" action="options.php" style="margin-top:16px;">
      <?php
        // 2) Liên kết form với đúng settings_group (nonce, option_page, action=update)
        settings_fields($settings_group);

        // 3) Hiển thị mô tả của section (callback) nếu có
        global $wp_settings_sections;
        if (isset($wp_settings_sections[$menu_slug][$active_tab])) {
            $sec = $wp_settings_sections[$menu_slug][$active_tab];
            if (!empty($sec['title'])) {
                echo '<h3 style="margin-top:8px;">' . esc_html($sec['title']) . '</h3>';
            }
            if (!empty($sec['callback']) && is_callable($sec['callback'])) {
                // In mô tả section do module/SettingsPage đã đăng ký
                call_user_func($sec['callback']);
            }
        }

        // 4) Chỉ render FIELDS của tab/section đang chọn
        do_settings_fields($menu_slug, $active_tab);

        // 5) Fallback giữ đúng tab sau khi lưu (trong trường hợp referer bị strip bởi plugin khác)
        $referer_fallback = add_query_arg(['page' => $menu_slug, 'tab' => $active_tab], admin_url('admin.php'));
      ?>
      <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url($referer_fallback); ?>">

      <?php submit_button(__('Lưu thay đổi', 'tmt-crm')); ?>
    </form>
  </div>
</div>
