<?php

/**
 * @var array  $tabs     ['slug' => 'Label', ...]
 * @var string $active
 * @var int    $id
 * @var string $action
 * @var string $list_url
 * @var array  $general
 */

use TMT\CRM\Shared\Presentation\Support\View;
use \TMT\CRM\Modules\License\Presentation\Admin\Screen\LicenseScreen;

defined('ABSPATH') || exit;

?>
<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo $id ? esc_html__('Edit License', 'tmt-crm') : esc_html__('Add License', 'tmt-crm'); ?>
    </h1>
    <a href="<?php echo esc_url($list_url); ?>" class="page-title-action"><?php _e('Back to list', 'tmt-crm'); ?></a>
    <hr class="wp-header-end" />

    <h2 class="nav-tab-wrapper" style="margin-top:10px;">
        <?php foreach ($tabs as $slug => $label_txt): ?>
            <?php
            $url = add_query_arg([
                'page' => LicenseScreen::PAGE_SLUG,
                'view' => 'edit',
                'id'   => $id,
                'tab'  => $slug,
            ], admin_url('admin.php'));

            $is_active   = ($active === $slug) ? ' nav-tab-active' : '';
            $is_disabled = ($id === 0 && $slug !== 'general');
            $a_attrs     = $is_disabled
                ? 'href="#" onclick="return false;" style="opacity:.5;cursor:not-allowed;"'
                : 'href="' . esc_url($url) . '"';
            ?>
            <a class="nav-tab<?php echo esc_attr($is_active); ?>" <?php echo $a_attrs; ?>>
                <?php echo esc_html($label_txt); ?>
            </a>
        <?php endforeach; ?>
    </h2>

    <div class="tab-content" style="width:100%; padding:20px 0;">
        <?php
        // Nạp panel theo tab
        switch ($active) {
            case 'general':
                View::render_admin_partial('license', 'general', [
                    'action'  => $action,
                    'general' => $general,
                ]);
                break;

            case 'allocations':
                View::render_admin_partial('license', 'allocations', [
                    'credential_id' => (int)$id,
                ]);
                break;

            case 'activations':
                View::render_admin_partial('license', 'activations', [
                    'credential_id' => (int)$id,
                ]);
                break;

            case 'deliveries':
                View::render_admin_partial('license', 'deliveries', [
                    'credential_id' => (int)$id,
                ]);
                break;
        }
        ?>
    </div>
</div>