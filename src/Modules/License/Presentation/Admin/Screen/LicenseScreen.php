<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Presentation\Admin\Screen;

use TMT\CRM\Modules\License\Presentation\Admin\ListTable\{CredentialListTable, AllocationListTable, ActivationListTable, DeliveryListTable};

use TMT\CRM\Modules\License\Application\Services\CryptoService;
use TMT\CRM\Modules\License\Application\Services\PolicyService;
use TMT\CRM\Modules\License\Application\Services\CredentialService;
use TMT\CRM\Modules\License\Application\Services\AllocationService;
use TMT\CRM\Modules\License\Application\Services\ActivationService;

use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialRepository;
use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialSeatAllocationRepository;
use TMT\CRM\Modules\License\Infrastructure\Persistence\{WpdbCredentialActivationRepository, WpdbCredentialDeliveryRepository};

use TMT\CRM\Domain\Repositories\{CredentialRepositoryInterface, CredentialSeatAllocationRepositoryInterface};

use TMT\CRM\Modules\License\Application\DTO\CredentialDTO;
use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Shared\Presentation\Support\AdminPostHelper;

final class LicenseScreen
{
    public const PAGE_SLUG = 'tmt-crm-licenses';
    /** Danh sách credentials */
    public static function render_list(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Not allowed', 'tmt-crm'));
        }

        $repo  = Container::get(CredentialRepositoryInterface::class);
        $aRepo = Container::get(CredentialSeatAllocationRepositoryInterface::class);

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

        $add_url = AdminPostHelper::url('tmt_crm_license_open_form');

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
        // LicenseController.php NONCE_SAVE        = 'tmt_crm_license_save_';
        // check_admin_referer('tmt_crm_license_save_', '_wpnonce');

        $repo  = Container::get(CredentialRepositoryInterface::class);

        $id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $dto = $id ? $repo->find_by_id($id) : null;

        // Tabs cấu hình
        $tabs = [
            'general'     => __('General', 'tmt-crm'),       // Add/Edit
            'allocations' => __('Allocations', 'tmt-crm'),
            'activations' => __('Activations', 'tmt-crm'),
            'deliveries'  => __('Deliveries', 'tmt-crm'), // mở sau nếu cần
        ];
        $active_tab = isset($_GET['tab']) ? sanitize_key((string)$_GET['tab']) : 'general';
        if (!array_key_exists($active_tab, $tabs)) {
            $active_tab = 'general';
        }

        $action_url = admin_url('admin-post.php');
        $list_url   = add_query_arg(['page' => self::PAGE_SLUG], admin_url('admin.php'));

        // Giá trị form General
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

            <!-- NAV TABS CHUNG -->
            <h2 class="nav-tab-wrapper" style="margin-top:10px;">
                <?php foreach ($tabs as $slug => $label_txt): ?>
                    <?php
                    $url = add_query_arg([
                        'page' => 'tmt-crm-licenses-edit',
                        'id'   => $id,
                        'tab'  => $slug,
                    ], admin_url('admin.php'));

                    $active = ($active_tab === $slug) ? ' nav-tab-active' : '';

                    // Nếu chưa có ID thì disable các tab khác General
                    $is_disabled = ($id === 0 && $slug !== 'general');
                    $a_attrs = $is_disabled
                        ? 'href="#" onclick="return false;" style="opacity:.5;cursor:not-allowed;"'
                        : 'href="' . esc_url($url) . '"';

                    echo '<a class="nav-tab' . $active . '" ' . $a_attrs . '>' . esc_html($label_txt) . '</a>';
                    ?>
                <?php endforeach; ?>
            </h2>

            <div class="tab-content" style="width:100%; padding:20px 0;">
                <?php
                switch ($active_tab) {
                    case 'general':
                ?>
                        <!-- FORM GENERAL (ADD/EDIT) -->
                        <form method="post" action="<?php echo esc_url($action_url); ?>">
                            <?php wp_nonce_field('tmt_crm_license_save_'); ?>
                            <input type="hidden" name="action" value="tmt_crm_license_save" />
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
                <?php
                        break;

                    case 'allocations':
                        if ($id === 0) {
                            echo '<p>' . esc_html__('Please create the license first, then manage allocations.', 'tmt-crm') . '</p>';
                        } else {
                            // Panel đã có sẵn – nhớ bỏ nav-tab wrapper bên trong panel
                            self::render_allocations_panel($id);
                        }
                        break;

                    case 'activations':
                        if ($id === 0) {
                            echo '<p>' . esc_html__('Please create the license first, then manage activations.', 'tmt-crm') . '</p>';
                        } else {
                            // Panel đã có sẵn – nhớ bỏ nav-tab wrapper bên trong panel
                            self::render_activations_panel($id);
                        }
                        break;
                    case 'deliveries':
                        if ($id === 0) {
                            echo '<p>' . esc_html__('Please create the license first, then log deliveries.', 'tmt-crm') . '</p>';
                        } else {
                            self::render_deliveries_panel($id);
                        }
                        break;

                        // case 'deliveries':
                        //     if ($id === 0) { echo '<p>…</p>'; } else { self::render_deliveries_panel($id); }
                        //     break;
                }
                ?>
            </div> <!-- /.tab-content -->
        </div> <!-- /.wrap -->
    <?php
    }
    private static function render_deliveries_panel(int $credential_id): void
    {
        global $wpdb;
        $repo = new WpdbCredentialDeliveryRepository($wpdb);

        $items = $repo->list_by_credential($credential_id);
        $action_url = admin_url('admin-post.php');
    ?>
        <div id="deliveries" class="tab-pane" style="width:100%; padding:0;">
            <h2 style="margin-top:20px;"><?php _e('Deliveries', 'tmt-crm'); ?></h2>

            <?php
            $table = new DeliveryListTable();
            $table->set_data($credential_id, $items);
            $table->prepare_items();
            ?>
            <form method="post" style="margin:0;">
                <?php $table->display(); ?>
            </form>

            <hr style="margin:20px 0;" />

            <h3 style="margin-top:10px;"><?php _e('Log Delivery', 'tmt-crm'); ?></h3>
            <form method="post" action="<?php echo esc_url($action_url); ?>" style="max-width:100%;">
                <input type="hidden" name="action" value="tmt_license_delivery_log" />
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('tmt_license_delivery_log')); ?>" />
                <input type="hidden" name="credential_id" value="<?php echo (int)$credential_id; ?>" />

                <table class="form-table" role="presentation" style="max-width:100%;">
                    <tbody>
                        <tr>
                            <th><label for="delivered_to_email"><?php _e('Delivered To (Email)', 'tmt-crm'); ?></label></th>
                            <td><input type="email" name="delivered_to_email" id="delivered_to_email" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="delivered_to_contact_id"><?php _e('Contact ID', 'tmt-crm'); ?></label></th>
                            <td><input type="number" name="delivered_to_contact_id" id="delivered_to_contact_id" class="small-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="delivered_to_customer_id"><?php _e('Customer ID', 'tmt-crm'); ?></label></th>
                            <td><input type="number" name="delivered_to_customer_id" id="delivered_to_customer_id" class="small-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="delivered_to_company_id"><?php _e('Company ID', 'tmt-crm'); ?></label></th>
                            <td><input type="number" name="delivered_to_company_id" id="delivered_to_company_id" class="small-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="channel"><?php _e('Channel', 'tmt-crm'); ?></label></th>
                            <td>
                                <select name="channel" id="channel">
                                    <?php foreach (['email', 'zalo', 'file', 'printed', 'other'] as $ch): ?>
                                        <option value="<?php echo esc_attr($ch); ?>"><?php echo esc_html($ch); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="delivered_at"><?php _e('Delivered At', 'tmt-crm'); ?></label></th>
                            <td><input type="text" name="delivered_at" id="delivered_at" class="regular-text" placeholder="YYYY-MM-DD HH:MM:SS" /></td>
                        </tr>
                        <tr>
                            <th><label for="delivery_note"><?php _e('Note', 'tmt-crm'); ?></label></th>
                            <td><input type="text" name="delivery_note" id="delivery_note" class="regular-text" /></td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button(__('Log Delivery', 'tmt-crm')); ?>
            </form>
        </div>
    <?php
    }

    /** Panel Allocations: bảng + form add/update */
    private static function render_allocations_panel(int $credential_id): void
    {
        global $wpdb;
        $allocRepo = new WpdbCredentialSeatAllocationRepository($wpdb);

        $items = $allocRepo->list_by_credential($credential_id);

        // Nếu đang edit 1 allocation
        $edit_id = isset($_GET['edit_allocation']) ? (int)$_GET['edit_allocation'] : 0;
        $editing = null;
        if ($edit_id) {
            foreach ($items as $a) {
                if ((int)$a->id === $edit_id) {
                    $editing = $a;
                    break;
                }
            }
        }

        $action_url = admin_url('admin-post.php');
        $nonce_save = wp_create_nonce('tmt_license_allocation_save');

        // UI
    ?>
        <!-- <h2 class="nav-tab-wrapper" style="margin-top:25px;">
            <a href="#allocations" class="nav-tab nav-tab-active"><?php _e('Allocations', 'tmt-crm'); ?></a>
        </h2> -->

        <div id="allocations" class="tab-content" style="width:100%; padding:20px 0;">
            <h2><?php _e('Seat Allocations', 'tmt-crm'); ?></h2>

            <?php
            // Bảng danh sách
            $table = new AllocationListTable();
            $table->set_data($items);
            $table->prepare_items();
            ?>
            <form method="post">
                <?php $table->display(); ?>
            </form>

            <hr />

            <h3><?php echo $editing ? esc_html__('Edit Allocation', 'tmt-crm') : esc_html__('Add Allocation', 'tmt-crm'); ?></h3>
            <form method="post" action="<?php echo esc_url($action_url); ?>">
                <input type="hidden" name="action" value="tmt_license_allocation_save" />
                <input type="hidden" name="credential_id" value="<?php echo (int)$credential_id; ?>" />
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_save); ?>" />
                <?php if ($editing): ?>
                    <input type="hidden" name="id" value="<?php echo (int)$editing->id; ?>" />
                <?php endif; ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th><label for="beneficiary_type"><?php _e('Beneficiary Type', 'tmt-crm'); ?></label></th>
                            <td>
                                <select name="beneficiary_type" id="beneficiary_type">
                                    <?php
                                    $types = ['company', 'customer', 'contact', 'email'];
                                    $val = $editing ? $editing->beneficiary_type : 'company';
                                    foreach ($types as $t) {
                                        printf(
                                            '<option value="%s" %s>%s</option>',
                                            esc_attr($t),
                                            selected($val, $t, false),
                                            esc_html($t)
                                        );
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="beneficiary_id"><?php _e('Beneficiary ID', 'tmt-crm'); ?></label></th>
                            <td>
                                <input type="number" name="beneficiary_id" id="beneficiary_id" class="small-text"
                                    value="<?php echo esc_attr((string)($editing->beneficiary_id ?? '')); ?>" />
                                <p class="description"><?php _e('Để trống nếu dùng email', 'tmt-crm'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="beneficiary_email"><?php _e('Beneficiary Email', 'tmt-crm'); ?></label></th>
                            <td>
                                <input type="email" name="beneficiary_email" id="beneficiary_email" class="regular-text"
                                    value="<?php echo esc_attr((string)($editing->beneficiary_email ?? '')); ?>" />
                                <p class="description"><?php _e('Dùng cho Family Share', 'tmt-crm'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="seat_quota"><?php _e('Seat Quota', 'tmt-crm'); ?></label></th>
                            <td><input type="number" min="0" name="seat_quota" id="seat_quota" class="small-text"
                                    value="<?php echo esc_attr((string)($editing->seat_quota ?? 1)); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="status"><?php _e('Status', 'tmt-crm'); ?></label></th>
                            <td>
                                <select name="status" id="status">
                                    <?php
                                    $statuses = ['pending', 'active', 'revoked'];
                                    $val = $editing ? $editing->status : 'active';
                                    foreach ($statuses as $s) {
                                        printf(
                                            '<option value="%s" %s>%s</option>',
                                            esc_attr($s),
                                            selected($val, $s, false),
                                            esc_html($s)
                                        );
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="note"><?php _e('Note', 'tmt-crm'); ?></label></th>
                            <td><input type="text" name="note" id="note" class="regular-text"
                                    value="<?php echo esc_attr((string)($editing->note ?? '')); ?>" /></td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button($editing ? __('Update Allocation', 'tmt-crm') : __('Add Allocation', 'tmt-crm')); ?>
            </form>
        </div>
    <?php
    }
    private static function render_activations_panel(int $credential_id): void
    {
        global $wpdb;
        $actRepo   = new WpdbCredentialActivationRepository($wpdb);
        $allocRepo = new WpdbCredentialSeatAllocationRepository($wpdb);

        $items  = $actRepo->list_by_credential($credential_id);
        $allocs = $allocRepo->list_by_credential($credential_id); // để render select allocation

        $action_url = admin_url('admin-post.php');
    ?>
        <!-- <h2 class="nav-tab-wrapper" style="margin-top:25px;">
            <a href="#activations" class="nav-tab nav-tab-active"><?php _e('Activations', 'tmt-crm'); ?></a>
        </h2> -->

        <div id="activations" class="tab-content" style="width:100%; padding:20px 0;">
            <h2><?php _e('Device Activations', 'tmt-crm'); ?></h2>
            <?php
            $table = new ActivationListTable();
            $table->set_data($credential_id, $items);
            $table->prepare_items();
            ?>
            <form method="post">
                <?php $table->display(); ?>
            </form>

            <hr />

            <h3><?php _e('Add Activation', 'tmt-crm'); ?></h3>
            <form method="post" action="<?php echo esc_url($action_url); ?>">
                <input type="hidden" name="action" value="tmt_license_activation_add" />
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('tmt_license_activation_add')); ?>" />
                <input type="hidden" name="credential_id" value="<?php echo (int)$credential_id; ?>" />

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th><label for="allocation_id"><?php _e('Allocation', 'tmt-crm'); ?></label></th>
                            <td>
                                <select name="allocation_id" id="allocation_id">
                                    <option value=""><?php _e('— none —', 'tmt-crm'); ?></option>
                                    <?php foreach ($allocs as $a): ?>
                                        <option value="<?php echo (int)$a->id; ?>">
                                            <?php
                                            $ben = $a->beneficiary_type . ($a->beneficiary_email ? ' - ' . $a->beneficiary_email : ($a->beneficiary_id ? ' #' . (int)$a->beneficiary_id : ''));
                                            printf('%s (used %d/%d)', esc_html($ben), (int)$a->seat_used, (int)$a->seat_quota);
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="hostname"><?php _e('Hostname', 'tmt-crm'); ?></label></th>
                            <td><input type="text" name="hostname" id="hostname" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="device_fingerprint_hash"><?php _e('Fingerprint (SHA-256)', 'tmt-crm'); ?></label></th>
                            <td><input type="text" name="device_fingerprint_hash" id="device_fingerprint_hash" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="user_display"><?php _e('User Display', 'tmt-crm'); ?></label></th>
                            <td><input type="text" name="user_display" id="user_display" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="user_email"><?php _e('User Email', 'tmt-crm'); ?></label></th>
                            <td><input type="email" name="user_email" id="user_email" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="location_hint"><?php _e('Location', 'tmt-crm'); ?></label></th>
                            <td><input type="text" name="location_hint" id="location_hint" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="os_info_json"><?php _e('OS Info (JSON)', 'tmt-crm'); ?></label></th>
                            <td><textarea name="os_info_json" id="os_info_json" class="large-text code" rows="4"></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="note"><?php _e('Note', 'tmt-crm'); ?></label></th>
                            <td><input type="text" name="note" id="note" class="regular-text" /></td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button(__('Add Activation', 'tmt-crm')); ?>
            </form>

            <hr />

            <h3><?php _e('Transfer Activation', 'tmt-crm'); ?></h3>
            <form method="post" action="<?php echo esc_url($action_url); ?>">
                <input type="hidden" name="action" value="tmt_license_activation_transfer" />
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('tmt_license_activation_transfer')); ?>" />
                <input type="hidden" name="credential_id" value="<?php echo (int)$credential_id; ?>" />

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th><label for="from_activation_id"><?php _e('From Activation ID', 'tmt-crm'); ?></label></th>
                            <td><input type="number" min="1" name="from_activation_id" id="from_activation_id" class="small-text" required /></td>
                        </tr>
                        <tr>
                            <th><label for="new_allocation_id"><?php _e('New Allocation', 'tmt-crm'); ?></label></th>
                            <td>
                                <select name="new_allocation_id" id="new_allocation_id">
                                    <option value=""><?php _e('— none —', 'tmt-crm'); ?></option>
                                    <?php foreach ($allocs as $a): ?>
                                        <option value="<?php echo (int)$a->id; ?>">
                                            <?php
                                            $ben = $a->beneficiary_type . ($a->beneficiary_email ? ' - ' . $a->beneficiary_email : ($a->beneficiary_id ? ' #' . (int)$a->beneficiary_id : ''));
                                            printf('%s (used %d/%d)', esc_html($ben), (int)$a->seat_used, (int)$a->seat_quota);
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Để trống để transfer mà không gắn allocation.', 'tmt-crm'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="hostname"><?php _e('Hostname (new)', 'tmt-crm'); ?></label></th>
                            <td><input type="text" name="hostname" id="hostname" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="device_fingerprint_hash"><?php _e('Fingerprint (new)', 'tmt-crm'); ?></label></th>
                            <td><input type="text" name="device_fingerprint_hash" id="device_fingerprint_hash" class="regular-text" /></td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button(__('Transfer', 'tmt-crm')); ?>
            </form>
        </div>
<?php
    }
}
