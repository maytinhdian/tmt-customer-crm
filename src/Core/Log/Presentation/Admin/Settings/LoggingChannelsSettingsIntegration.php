<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Log\Presentation\Admin\Settings;

use TMT\CRM\Core\Settings\SettingsSectionInterface;

/**
 * Section: "Logging — Per Channel"
 * Lưu vào: tmt_crm_settings['logging']['channels'] = [
 *   'customer'      => ['min_level' => 'info',   'targets' => 'both'],
 *   'notifications' => ['min_level' => 'warning','targets' => 'database'],
 *   'events'        => ['min_level' => 'debug',  'targets' => 'file'],
 * ]
 */
final class LoggingChannelsSettingsIntegration implements SettingsSectionInterface
{
    /** Gọi trong LogModule::bootstrap() để đăng ký section vào Settings */
    public static function register(): void
    {
        add_filter('tmt_crm_settings_sections', static function (array $sections) {
            $sections[] = new self();
            return $sections;
        });
    }

    public function section_id(): string
    {
        // tạo slug riêng tránh trùng với "logging" (mặc định)
        return 'logging_channels';
    }

    public function section_title(): string
    {
        return __('Logging — Per Channel', 'tmt-crm');
    }
    public function capability(): string
    {
        return 'manage_options';
    }
    public function header_html(): string
    {
        return '<p>' . esc_html__('Cấu hình quản lý Log Chanel.', 'tmt-crm') . '</p>';
    }

    /**
     * Render repeater: mỗi dòng = 1 channel
     */
    public function register_fields(string $page_slug, string $option_key): void
    {
        $all = get_option($option_key, []);
        $logging = isset($all['logging']) && is_array($all['logging']) ? $all['logging'] : [];
        $channels_map = isset($logging['channels']) && is_array($logging['channels'])
            ? $logging['channels']
            : $this->get_defaults();

        // Flatten về mảng các dòng [channel, min_level, targets] để tiện render
        $rows = [];
        foreach ($channels_map as $channel => $opts) {
            $rows[] = [
                'channel'   => (string)$channel,
                'min_level' => (string)($opts['min_level'] ?? 'info'),
                'targets'   => (string)($opts['targets'] ?? 'file'),
            ];
        }

        add_settings_field(
            $this->section_id() . '_table',
            __('Danh sách channel', 'tmt-crm'),
            function () use ($option_key, $rows) {
                $name_base = $option_key . '[logging][channels]'; // tên gốc sẽ là mảng assoc theo "channel"
                $levels    = ['debug', 'info', 'warning', 'error', 'critical'];
                $targets   = ['file' => 'File', 'database' => 'Database', 'both' => 'Cả hai (File + DB)'];

                // Gợi ý UI: mỗi dòng nhập "channel" (text), chọn min_level, targets
                echo '<div id="tmt-logging-channels-wrap">';
                echo '<table class="widefat striped" id="tmt-logging-channels-table" style="max-width:900px">';
                echo '<thead><tr>';
                echo '<th style="width:28%">' . esc_html__('Channel (tên duy nhất)', 'tmt-crm') . '</th>';
                echo '<th style="width:28%">' . esc_html__('Min Level', 'tmt-crm') . '</th>';
                echo '<th style="width:28%">' . esc_html__('Targets', 'tmt-crm') . '</th>';
                echo '<th style="width:16%"></th>';
                echo '</tr></thead><tbody>';

                if (!$rows) {
                    $rows = [['channel' => 'customer', 'min_level' => 'info', 'targets' => 'both']];
                }

                foreach ($rows as $i => $row) {
                    $chan   = $row['channel'];
                    $min    = $row['min_level'];
                    $tgt    = $row['targets'];

                    // Khi submit, mình sẽ đọc từ "channels_temp" (array index) để sanitize rồi build map assoc
                    echo '<tr class="tmt-logging-row">';
                    printf(
                        '<td><input type="text" class="regular-text" name="%s[channels_temp][%d][channel]" value="%s" placeholder="vd: customer" /></td>',
                        esc_attr($option_key . '[logging]'),
                        $i,
                        esc_attr($chan)
                    );

                    // Min level
                    echo '<td><select name="' . esc_attr($option_key . '[logging]') . '[channels_temp][' . (int)$i . '][min_level]">';
                    foreach ($levels as $lv) {
                        printf('<option value="%1$s"%2$s>%1$s</option>', esc_attr($lv), selected($min, $lv, false));
                    }
                    echo '</select></td>';

                    // Targets
                    echo '<td><select name="' . esc_attr($option_key . '[logging]') . '[channels_temp][' . (int)$i . '][targets]">';
                    foreach ($targets as $key => $label) {
                        printf('<option value="%1$s"%2$s>%3$s</option>', esc_attr($key), selected($tgt, $key, false), esc_html($label));
                    }
                    echo '</select></td>';

                    echo '<td><button type="button" class="button button-link-delete tmt-logging-row-remove">' . esc_html__('Xoá', 'tmt-crm') . '</button></td>';
                    echo '</tr>';
                }

                echo '</tbody></table>';
                echo '<p><button type="button" class="button" id="tmt-logging-row-add">' . esc_html__('Thêm channel', 'tmt-crm') . '</button></p>';
                echo '</div>';

                // Template ẩn cho dòng mới
                ob_start();
?>
            <tr class="tmt-logging-row">
                <td><input type="text" class="regular-text" name="<?php echo esc_attr($option_key . '[logging]'); ?>[channels_temp][__INDEX__][channel]" value="" placeholder="vd: customer" /></td>
                <td>
                    <select name="<?php echo esc_attr($option_key . '[logging]'); ?>[channels_temp][__INDEX__][min_level]">
                        <option value="debug">debug</option>
                        <option value="info" selected>info</option>
                        <option value="warning">warning</option>
                        <option value="error">error</option>
                        <option value="critical">critical</option>
                    </select>
                </td>
                <td>
                    <select name="<?php echo esc_attr($option_key . '[logging]'); ?>[channels_temp][__INDEX__][targets]">
                        <option value="file">File</option>
                        <option value="database">Database</option>
                        <option value="both" selected>Cả hai (File + DB)</option>
                    </select>
                </td>
                <td><button type="button" class="button button-link-delete tmt-logging-row-remove"><?php echo esc_html__('Xoá', 'tmt-crm'); ?></button></td>
            </tr>
            <?php
                $tpl = trim((string)ob_get_clean());
                $tpl = str_replace("\n", "", $tpl); // inline
            ?>
            <script>
                (function($) {
                    var $tbl = $('#tmt-logging-channels-table tbody');
                    var idx = $tbl.find('tr.tmt-logging-row').length;

                    $('#tmt-logging-row-add').on('click', function() {
                        var html = <?php echo wp_json_encode($tpl); ?>;
                        html = html.replace(/__INDEX__/g, String(idx++));
                        $tbl.append(html);
                    });

                    $(document).on('click', '.tmt-logging-row-remove', function() {
                        $(this).closest('tr').remove();
                    });
                })(jQuery);
            </script>
<?php

                echo '<p class="description">';
                echo esc_html__('Mỗi dòng là một channel. Ví dụ:', 'tmt-crm') . ' ';
                echo '<code>customer</code>, <code>notifications</code>, <code>events</code>';
                echo '. ';
                echo esc_html__('Targets: "file" (ghi file), "database" (ghi DB), "both" (cả hai).', 'tmt-crm');
                echo '</p>';
            },
            $page_slug,
            $this->section_id()
        );
    }

    /**
     * Default channels nếu admin chưa cấu hình
     * @return array<string, array{min_level:string, targets:string}>
     */
    public function get_defaults(): array
    {
        return [
            'customer'      => ['min_level' => 'info',   'targets' => 'both'],
            'notifications' => ['min_level' => 'warning', 'targets' => 'database'],
            'events'        => ['min_level' => 'debug',  'targets' => 'file'],
        ];
    }

    /**
     * Sanitize dữ liệu post về của section này.
     * Nhận vào phần của "logging" (mình đọc từ logging[channels_temp]), rồi build về logging['channels'] dạng assoc.
     *
     * @param array $input       Mảng post của toàn settings hoặc riêng section; ở đây ta expect có key 'logging'
     * @param array $current_all Toàn bộ option hiện tại
     * @return array key=>value hợp lệ của section này (ở đây return nguyên map logging['channels'])
     */
    public function sanitize(array $input, array $current_all): array
    {
        $defaults = $this->get_defaults();

        // Lấy phần tạm channels_temp (mảng index)
        $logging_in = isset($input['logging']) && is_array($input['logging']) ? $input['logging'] : $input;
        $rows       = isset($logging_in['channels_temp']) && is_array($logging_in['channels_temp'])
            ? $logging_in['channels_temp']
            : [];

        $allowed_levels  = ['debug', 'info', 'warning', 'error', 'critical'];
        $allowed_targets = ['file', 'database', 'both'];

        $out = []; // map assoc theo channel
        foreach ($rows as $row) {
            $chan = isset($row['channel']) ? trim((string)$row['channel']) : '';
            if ($chan === '') {
                continue;
            }

            // chuẩn hóa key channel (chỉ chữ, số, _, -)
            $chan = strtolower(preg_replace('/[^a-z0-9_\-]/i', '', $chan));

            $min = isset($row['min_level']) ? (string)$row['min_level'] : 'info';
            if (!in_array($min, $allowed_levels, true)) {
                $min = 'info';
            }

            $tgt = isset($row['targets']) ? (string)$row['targets'] : 'file';
            if (!in_array($tgt, $allowed_targets, true)) {
                $tgt = 'file';
            }

            $out[$chan] = ['min_level' => $min, 'targets' => $tgt];
        }

        // Nếu admin xoá hết, dùng defaults (tránh về mảng rỗng)
        if (!$out) {
            $out = $defaults;
        }

        // Trả về phần của section này — framework Settings của bạn sẽ merge vào tmt_crm_settings['logging']['channels']
        return $out;
    }
}
