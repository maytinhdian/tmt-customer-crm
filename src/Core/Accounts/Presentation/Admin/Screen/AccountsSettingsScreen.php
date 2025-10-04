<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Accounts\Presentation\Admin\Screen;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Accounts\Application\Services\PreferenceService;

final class AccountsSettingsScreen
{
    public const PAGE_SLUG = 'tmt-crm-accounts';

    // public static function register_menu(): void
    // {
    //     add_action('admin_menu', function () {
    //         add_submenu_page(
    //             'tmt-crm', // parent slug
    //             __('Tài khoản & Tuỳ chọn', 'tmt-crm'),
    //             __('Tài khoản', 'tmt-crm'),
    //             'manage_options',
    //             self::PAGE_SLUG,
    //             [self::class, 'render']
    //         );
    //     },20);
    // }

    public static function render(): void
    {
        if (isset($_POST['tmt_pref_timezone']) && check_admin_referer('tmt_crm_accounts_save')) {
            $tz = sanitize_text_field((string)$_POST['tmt_pref_timezone']);
            /** @var PreferenceService $prefs */
            $prefs = Container::get(PreferenceService::class);
            $prefs->set('timezone', $tz);
            echo '<div class="updated"><p>Saved.</p></div>';
        }

        /** @var PreferenceService $prefs */
        $prefs = Container::get(PreferenceService::class);
        $curr = (string)$prefs->get('timezone', null, '');

        echo '<div class="wrap"><h1>Tài khoản & Tuỳ chọn</h1>';
        echo '<form method="post">';
        wp_nonce_field('tmt_crm_accounts_save');
        echo '<table class="form-table"><tr><th>Timezone</th><td>';
        echo '<input type="text" name="tmt_pref_timezone" value="' . esc_attr($curr) . '" class="regular-text" placeholder="Asia/Ho_Chi_Minh" />';
        echo '</td></tr></table>';
        submit_button(__('Lưu', 'tmt-crm'));
        echo '</form></div>';
    }
}
