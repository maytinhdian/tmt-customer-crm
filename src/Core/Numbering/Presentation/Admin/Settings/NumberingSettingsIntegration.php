<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Numbering\Presentation\Admin\Settings;

use TMT\CRM\Shared\Container;
use TMT\CRM\Domain\Repositories\NumberingRepositoryInterface;
use TMT\CRM\Core\Numbering\Domain\DTO\NumberingRuleDTO;

/**
 * Tích hợp tab "Đánh số tự động" vào Core/Settings
 * Ghi chú: sử dụng View::render_admin_module() nếu project đã có sẵn View helper.
 */
final class NumberingSettingsIntegration
{
    public const TAB_KEY = 'numbering';

    public static function register(): void
    {
        // Thêm tab
        add_filter('tmt_crm/settings/tabs', function (array $tabs) {
            $tabs[self::TAB_KEY] = __('Đánh số tự động', 'tmt-crm');
            return $tabs;
        });

        // Render nội dung tab
        add_action('tmt_crm/settings/render_tab', function (string $active_tab) {
            if ($active_tab !== self::TAB_KEY) {
                return;
            }
            self::render_tab();
        });

        // Handle lưu form
        add_action('admin_post_tmt_crm_save_numbering', [self::class, 'handle_save']);
    }

    public static function render_tab(): void
    {
        /** @var NumberingRepositoryInterface $repo */
        $repo = Container::get(NumberingRepositoryInterface::class);

        $entities = [
            'company'  => __('Công ty', 'tmt-crm'),
            'customer' => __('Khách hàng', 'tmt-crm'),
            'contact'  => __('Liên hệ', 'tmt-crm'),
            'quote'    => __('Báo giá', 'tmt-crm'),
        ];

        echo '<div class="wrap"><h2>' . esc_html__('Cấu hình đánh số tự động', 'tmt-crm') . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="tmt_crm_save_numbering" />';
        wp_nonce_field('tmt_crm_numbering');

        echo '<table class="form-table">';
        foreach ($entities as $key => $label) {
            $rule = $repo->get_rule($key) ?? new NumberingRuleDTO($key);
            echo '<tr><th colspan="2"><h3>' . esc_html($label) . '</h3></th></tr>';
            echo '<tr><th><label>Prefix</label></th><td><input type="text" name="rules[' . esc_attr($key) . '][prefix]" value="' . esc_attr($rule->prefix) . '" class="regular-text" /></td></tr>';
            echo '<tr><th><label>Suffix</label></th><td><input type="text" name="rules[' . esc_attr($key) . '][suffix]" value="' . esc_attr($rule->suffix) . '" class="regular-text" /></td></tr>';
            echo '<tr><th><label>Padding</label></th><td><input type="number" min="1" max="10" name="rules[' . esc_attr($key) . '][padding]" value="' . esc_attr((string)$rule->padding) . '" /></td></tr>';
            echo '<tr><th><label>Reset</label></th><td>';
            echo '<select name="rules[' . esc_attr($key) . '][reset]">';
            foreach ([
                'never'   => __('Không reset', 'tmt-crm'),
                'yearly'  => __('Theo năm', 'tmt-crm'),
                'monthly' => __('Theo tháng', 'tmt-crm'),
            ] as $val => $text) {
                $sel = selected($rule->reset, $val, false);
                echo '<option value="' . esc_attr($val) . '" ' . $sel . '>' . esc_html($text) . '</option>';
            }
            echo '</select>';
            echo '</td></tr>';
            echo '<tr><th><label>Last Number</label></th><td><input type="number" min="0" name="rules[' . esc_attr($key) . '][last_number]" value="' . esc_attr((string)$rule->last_number) . '" /></td></tr>';
            echo '<tr><td colspan="2"><hr/></td></tr>';
        }
        echo '</table>';
        submit_button(__('Lưu thay đổi', 'tmt-crm'));
        echo '</form></div>';
    }

    public static function handle_save(): void
    {
        check_admin_referer('tmt_crm_numbering');
        if (!current_user_can('manage_options')) {
            wp_die(__('Bạn không có quyền.', 'tmt-crm'));
        }
        $rules = isset($_POST['rules']) && is_array($_POST['rules']) ? wp_unslash($_POST['rules']) : [];

        /** @var NumberingRepositoryInterface $repo */
        $repo = Container::get(NumberingRepositoryInterface::class);

        foreach ($rules as $entity => $data) {
            $entity = sanitize_key($entity);
            $dto = new NumberingRuleDTO(
                entity_type: $entity,
                prefix: sanitize_text_field($data['prefix'] ?? ''),
                suffix: sanitize_text_field($data['suffix'] ?? ''),
                padding: max(1, (int)($data['padding'] ?? 4)),
                reset: in_array(($data['reset'] ?? 'never'), ['never','yearly','monthly'], true) ? $data['reset'] : 'never',
                last_number: max(0, (int)($data['last_number'] ?? 0))
            );
            $repo->save_rule($dto);
        }
        wp_safe_redirect(add_query_arg(['page' => 'tmt-crm-settings', 'tab' => self::TAB_KEY, 'updated' => '1'], admin_url('admin.php')));
        exit;
    }
}
