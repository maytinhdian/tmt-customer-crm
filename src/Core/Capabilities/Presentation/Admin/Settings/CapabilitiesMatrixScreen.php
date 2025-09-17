<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Capabilities\Presentation\Admin\Settings;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Capabilities\Domain\Capability;
use TMT\CRM\Core\Capabilities\Infrastructure\Role\RoleSynchronizer;
use TMT\CRM\Domain\Repositories\CapabilitiesRepositoryInterface;

final class CapabilitiesMatrixScreen
{
    public static function register(): void
    {
        add_options_page(
            __('TMT CRM - Capabilities', 'tmt-crm'),
            __('TMT CRM - Capabilities', 'tmt-crm'),
            'manage_options',
            'tmt-crm-capabilities',
            [self::class, 'render']
        );
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Bạn không có quyền truy cập.', 'tmt-crm'));
        }

        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        $roles = get_editable_roles();
        $caps_groups = Capability::grouped();

        /** @var CapabilitiesRepositoryInterface $repo */
        $repo = Container::get('core.capabilities.repo');
        $matrix = $repo->get_matrix();

        if (isset($_POST['tmt_crm_caps_save']) && check_admin_referer('tmt_crm_caps_matrix')) {
            $new_matrix = [];
            foreach ($roles as $role_slug => $role_info) {
                $new_matrix[$role_slug] = array_map('sanitize_text_field', (array)($_POST['caps'][$role_slug] ?? []));
            }
            $repo->set_matrix($new_matrix);

            /** @var RoleSynchronizer $sync */
            $sync = Container::get('core.capabilities.role_sync');
            $sync->sync_all();

            echo '<div class="notice notice-success"><p>' . esc_html__('Đã lưu ma trận quyền.', 'tmt-crm') . '</p></div>';
            $matrix = $repo->get_matrix();
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('TMT CRM - Capabilities Matrix', 'tmt-crm'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('tmt_crm_caps_matrix'); ?>

                <p class="description">
                    <?php esc_html_e('Chọn capability cho từng vai trò (role). Administrator luôn có toàn quyền.', 'tmt-crm'); ?>
                </p>

                <style>
                    .tmt-caps-table th, .tmt-caps-table td { vertical-align: top; }
                    .tmt-cap-group { margin-bottom: 8px; padding: 8px; border: 1px solid #e5e5e5; border-radius: 6px; }
                    .tmt-cap-group h3 { margin: 0 0 6px; font-size: 14px; }
                    .tmt-cap-columns { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 8px; }
                </style>

                <?php foreach ($roles as $role_slug => $role_info): ?>
                    <h2><?php echo esc_html($role_info['name'] . " ({$role_slug})"); ?></h2>
                    <div class="tmt-cap-columns">
                        <?php foreach ($caps_groups as $group_label => $caps): ?>
                            <div class="tmt-cap-group">
                                <h3><?php echo esc_html($group_label); ?></h3>
                                <?php foreach ($caps as $cap): 
                                    $checked = !empty($matrix[$role_slug]) && in_array($cap, $matrix[$role_slug], true); ?>
                                    <label style="display:block; margin:2px 0;">
                                        <input type="checkbox" name="caps[<?php echo esc_attr($role_slug); ?>][]" value="<?php echo esc_attr($cap); ?>" <?php checked($checked); ?> />
                                        <code><?php echo esc_html($cap); ?></code>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <hr/>
                <?php endforeach; ?>

                <p class="submit">
                    <button type="submit" name="tmt_crm_caps_save" class="button button-primary">
                        <?php esc_html_e('Lưu ma trận quyền', 'tmt-crm'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }
}
