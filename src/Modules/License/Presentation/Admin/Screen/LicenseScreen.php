<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Presentation\Admin\Screen;

use TMT\CRM\Modules\License\Presentation\Admin\ListTable\CredentialListTable;

use TMT\CRM\Modules\License\Application\Services\CryptoService;
use TMT\CRM\Modules\License\Application\Services\PolicyService;
use TMT\CRM\Modules\License\Application\Services\CredentialService;
use TMT\CRM\Modules\License\Application\Services\AllocationService;
use TMT\CRM\Modules\License\Application\Services\ActivationService;

use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialRepository;
use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialSeatAllocationRepository;
use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialActivationRepository;

use TMT\CRM\Modules\License\Application\DTO\CredentialDTO;

final class LicenseScreen
{
    public const PAGE_SLUG = 'tmt-crm-licenses';
    /** Danh sách credentials */
    public static function render_list(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Not allowed', 'tmt-crm'));
        }

        global $wpdb;
        $repo  = new WpdbCredentialRepository($wpdb);
        $aRepo = new WpdbCredentialSeatAllocationRepository($wpdb);

        $q      = isset($_GET['s']) ? sanitize_text_field((string)$_GET['s']) : '';
        $type   = isset($_GET['type']) ? sanitize_text_field((string)$_GET['type']) : '';
        $status = isset($_GET['status']) ? sanitize_text_field((string)$_GET['status']) : '';

        $page = isset($_GET['paged']) ? max(1, (int)$_GET['paged']) : 1;

        $filter = [];
        if ($q !== '')     $filter['q'] = $q;
        if ($type !== '')  $filter['type'] = $type;
        if ($status !== '') $filter['status'] = $status;

        $result = $repo->search($filter, $page, 20);
        $items  = $result['items'];
        $total  = $result['total'];

        // (tùy chọn) tính seats_used nhanh (P0 cho đẹp bảng)
        foreach ($items as $dto) {
            $allocs = $aRepo->list_by_credential((int)$dto->id);
            $used = 0;
            foreach ($allocs as $a) $used += (int)$a->seat_used;
            // $dto->seats_used = $used; // gán dynamic property để ListTable hiển thị
        }

        $table = new CredentialListTable();
        $table->set_data($items, $total);
        $table->prepare_items();

        $add_url = add_query_arg(['page' => 'tmt-crm-licenses-edit'], admin_url('admin.php'));
?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html__('Licenses', 'tmt-crm'); ?></h1>
            <a href="<?php echo esc_url($add_url); ?>" class="page-title-action"><?php _e('Add New', 'tmt-crm'); ?></a>
            <hr class="wp-header-end" />

            <form method="get">
                <input type="hidden" name="page" value="tmt-crm-licenses" />
                <p class="search-box">
                    <label class="screen-reader-text" for="license-search-input"><?php _e('Search Licenses', 'tmt-crm'); ?></label>
                    <input type="search" id="license-search-input" name="s" value="<?php echo esc_attr($q); ?>" />
                    <input type="submit" id="search-submit" class="button" value="<?php _e('Search', 'tmt-crm'); ?>">
                </p>
            </form>

            <form method="post">
                <?php
                $table->display();
                ?>
            </form>
        </div>
    <?php
    }

    /** Form tạo/sửa credential */
    public static function render_form(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Not allowed', 'tmt-crm'));
        }

        global $wpdb;
        $repo = new WpdbCredentialRepository($wpdb);

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $dto = $id ? $repo->find_by_id($id) : null;

        $action_url = admin_url('admin-post.php');
        $list_url   = add_query_arg(['page' => 'tmt-crm-licenses'], admin_url('admin.php'));

        $number       = $dto->number ?? '';
        $type         = $dto->type ?? 'LICENSE_KEY';
        $label        = $dto->label ?? '';
        $customer_id  = $dto->customer_id ?? '';
        $company_id   = $dto->company_id ?? '';
        $status       = $dto->status ?? 'active';
        $expires_at   = $dto->expires_at ?? '';
        $seats_total  = $dto->seats_total ?? '';
        $sharing_mode = $dto->sharing_mode ?? 'none';
        $renewal_of_id = $dto->renewal_of_id ?? '';
        $owner_id     = $dto->owner_id ?? '';
        $username     = $dto->username ?? '';
        $extra_json   = $dto->extra_json ?? '';

    ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php echo $id ? esc_html__('Edit License', 'tmt-crm') : esc_html__('Add License', 'tmt-crm'); ?>
            </h1>
            <a href="<?php echo esc_url($list_url); ?>" class="page-title-action"><?php _e('Back to list', 'tmt-crm'); ?></a>
            <hr class="wp-header-end" />

            <form method="post" action="<?php echo esc_url($action_url); ?>">
                <?php wp_nonce_field('tmt_license_save'); ?>
                <input type="hidden" name="action" value="tmt_license_save" />
                <?php if ($id): ?>
                    <input type="hidden" name="id" value="<?php echo (int)$id; ?>" />
                <?php endif; ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th><label for="number"><?php _e('Number', 'tmt-crm'); ?></label></th>
                            <td><input name="number" id="number" type="text" class="regular-text" value="<?php echo esc_attr($number); ?>" required /></td>
                        </tr>
                        <tr>
                            <th><label for="label"><?php _e('Label', 'tmt-crm'); ?></label></th>
                            <td><input name="label" id="label" type="text" class="regular-text" value="<?php echo esc_attr($label); ?>" required /></td>
                        </tr>
                        <tr>
                            <th><label for="type"><?php _e('Type', 'tmt-crm'); ?></label></th>
                            <td>
                                <select name="type" id="type">
                                    <?php
                                    $types = ['LICENSE_KEY', 'EMAIL_ACCOUNT', 'SAAS_ACCOUNT', 'API_TOKEN', 'WIFI_ACCOUNT', 'OTHER'];
                                    foreach ($types as $t) {
                                        printf(
                                            '<option value="%s" %s>%s</option>',
                                            esc_attr($t),
                                            selected($type, $t, false),
                                            esc_html($t)
                                        );
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="status"><?php _e('Status', 'tmt-crm'); ?></label></th>
                            <td>
                                <select name="status" id="status">
                                    <?php
                                    $statuses = ['active', 'disabled', 'expired', 'revoked', 'pending'];
                                    foreach ($statuses as $st) {
                                        printf(
                                            '<option value="%s" %s>%s</option>',
                                            esc_attr($st),
                                            selected($status, $st, false),
                                            esc_html($st)
                                        );
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="expires_at"><?php _e('Expires At', 'tmt-crm'); ?></label></th>
                            <td><input name="expires_at" id="expires_at" type="text" class="regular-text" placeholder="YYYY-MM-DD HH:MM:SS" value="<?php echo esc_attr($expires_at); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="seats_total"><?php _e('Seats Total', 'tmt-crm'); ?></label></th>
                            <td><input name="seats_total" id="seats_total" type="number" min="0" class="small-text" value="<?php echo esc_attr((string)$seats_total); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="sharing_mode"><?php _e('Sharing Mode', 'tmt-crm'); ?></label></th>
                            <td>
                                <select name="sharing_mode" id="sharing_mode">
                                    <?php
                                    $modes = ['none', 'seat_allocation', 'family_share'];
                                    foreach ($modes as $m) {
                                        printf(
                                            '<option value="%s" %s>%s</option>',
                                            esc_attr($m),
                                            selected($sharing_mode, $m, false),
                                            esc_html($m)
                                        );
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="customer_id"><?php _e('Customer ID', 'tmt-crm'); ?></label></th>
                            <td><input name="customer_id" id="customer_id" type="number" class="small-text" value="<?php echo esc_attr((string)$customer_id); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="company_id"><?php _e('Company ID', 'tmt-crm'); ?></label></th>
                            <td><input name="company_id" id="company_id" type="number" class="small-text" value="<?php echo esc_attr((string)$company_id); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="owner_id"><?php _e('Owner ID', 'tmt-crm'); ?></label></th>
                            <td><input name="owner_id" id="owner_id" type="number" class="small-text" value="<?php echo esc_attr((string)$owner_id); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="renewal_of_id"><?php _e('Renewal of Credential ID', 'tmt-crm'); ?></label></th>
                            <td><input name="renewal_of_id" id="renewal_of_id" type="number" class="small-text" value="<?php echo esc_attr((string)$renewal_of_id); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="username"><?php _e('Username/Email', 'tmt-crm'); ?></label></th>
                            <td><input name="username" id="username" type="text" class="regular-text" value="<?php echo esc_attr((string)$username); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="secret_primary"><?php _e('Secret (Key/Password)', 'tmt-crm'); ?></label></th>
                            <td><input name="secret_primary" id="secret_primary" type="text" class="regular-text" value="" /></td>
                        </tr>
                        <tr>
                            <th><label for="secret_secondary"><?php _e('Secret (Secondary)', 'tmt-crm'); ?></label></th>
                            <td><input name="secret_secondary" id="secret_secondary" type="text" class="regular-text" value="" /></td>
                        </tr>
                        <tr>
                            <th><label for="extra_json"><?php _e('Extra JSON (encrypted)', 'tmt-crm'); ?></label></th>
                            <td><textarea name="extra_json" id="extra_json" class="large-text code" rows="5"><?php echo esc_textarea((string)$extra_json); ?></textarea></td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button($id ? __('Update License', 'tmt-crm') : __('Create License', 'tmt-crm')); ?>
            </form>
        </div>
<?php
    }
}
