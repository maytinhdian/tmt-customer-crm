<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Records\Presentation\Admin\Settings;

final class RecordsSettingsScreen
{
    public const OPTION_KEY = 'tmt_crm_records_settings';

    public static function register(): void
    {
        add_options_page(
            __('TMT CRM - Records', 'tmt-crm'),
            __('TMT CRM - Records', 'tmt-crm'),
            'manage_options',
            'tmt-crm-records-settings',
            [self::class, 'render']
        );
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Bạn không có quyền truy cập.', 'tmt-crm'));
        }

        $defaults = [
            'enable_archive'     => 1,
            'retention_days'     => 180,
            'soft_delete_expire' => 0, // 0 = không auto xoá
        ];
        $opts = wp_parse_args(get_option(self::OPTION_KEY, []), $defaults);

        if (isset($_POST['tmt_crm_records_save']) && check_admin_referer('tmt_crm_records_settings')) {
            $opts['enable_archive']     = isset($_POST['enable_archive']) ? 1 : 0;
            $opts['retention_days']     = max(0, (int) ($_POST['retention_days'] ?? 180));
            $opts['soft_delete_expire'] = max(0, (int) ($_POST['soft_delete_expire'] ?? 0));
            update_option(self::OPTION_KEY, $opts);
            echo '<div class="notice notice-success"><p>' . esc_html__('Đã lưu cài đặt.', 'tmt-crm') . '</p></div>';
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('TMT CRM - Records Settings', 'tmt-crm'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('tmt_crm_records_settings'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Bật lưu Archive snapshot khi Purge', 'tmt-crm'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_archive" value="1" <?php checked($opts['enable_archive'], 1); ?> />
                                <?php esc_html_e('Lưu JSON snapshot trước khi xoá vĩnh viễn', 'tmt-crm'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Giữ Archive tối đa (ngày)', 'tmt-crm'); ?></th>
                        <td>
                            <input type="number" name="retention_days" value="<?php echo esc_attr((string)$opts['retention_days']); ?>" min="0" class="regular-text" />
                            <p class="description"><?php esc_html_e('0 = không dọn tự động', 'tmt-crm'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Tự xoá bản ghi đã xoá mềm sau (ngày)', 'tmt-crm'); ?></th>
                        <td>
                            <input type="number" name="soft_delete_expire" value="<?php echo esc_attr((string)$opts['soft_delete_expire']); ?>" min="0" class="regular-text" />
                            <p class="description"><?php esc_html_e('0 = không xoá tự động (khuyến nghị)', 'tmt-crm'); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="tmt_crm_records_save" class="button button-primary">
                        <?php esc_html_e('Lưu thay đổi', 'tmt-crm'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }
}
