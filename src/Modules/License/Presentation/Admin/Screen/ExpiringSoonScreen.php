<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Presentation\Admin\Screen;

use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialRepository;

final class ExpiringSoonScreen
{
    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Not allowed', 'tmt-crm'));
        }

        $days = isset($_GET['days']) ? max(1, (int)$_GET['days']) : (int) get_option('tmt_crm_license_expiring_days', 14);
        $page = isset($_GET['paged']) ? max(1, (int)$_GET['paged']) : 1;

        global $wpdb;
        $repo = new WpdbCredentialRepository($wpdb);

        // dùng search(filter) của repo
        $filter = ['expiring_within_days' => $days, 'exclude_status' => 'revoked'];
        $result = $repo->search($filter, $page, 50);

        $items = $result['items'] ?? [];
        $total = (int)($result['total'] ?? 0);
        $pages = max(1, (int)ceil($total / 50));

?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Licenses expiring soon', 'tmt-crm'); ?></h1>
            <form method="get" style="display:inline-block;margin-left:10px;">
                <input type="hidden" name="page" value="tmt-crm-licenses-expiring" />
                <label><?php _e('Days:', 'tmt-crm'); ?></label>
                <input type="number" min="1" name="days" value="<?php echo esc_attr((string)$days); ?>" class="small-text" />
                <button class="button"><?php _e('Filter', 'tmt-crm'); ?></button>
            </form>
            <hr class="wp-header-end" />

            <table class="widefat striped" style="margin-top:10px;">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'tmt-crm'); ?></th>
                        <th><?php _e('Number', 'tmt-crm'); ?></th>
                        <th><?php _e('Label', 'tmt-crm'); ?></th>
                        <th><?php _e('Status', 'tmt-crm'); ?></th>
                        <th><?php _e('Expires At', 'tmt-crm'); ?></th>
                        <th><?php _e('Seats Total', 'tmt-crm'); ?></th>
                        <th><?php _e('Actions', 'tmt-crm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="7"><?php _e('Không có license sắp hết hạn.', 'tmt-crm'); ?></td>
                        </tr>
                        <?php else: foreach ($items as $row): ?>
                            <tr>
                                <td><?php echo (int)$row->id; ?></td>
                                <td><?php echo esc_html((string)$row->number); ?></td>
                                <td><?php echo esc_html((string)$row->label); ?></td>
                                <td><?php echo esc_html((string)$row->status); ?></td>
                                <td><?php echo esc_html((string)($row->expires_at ?? '')); ?></td>
                                <td><?php echo esc_html((string)($row->seats_total ?? '-')); ?></td>
                                <td>
                                    <?php
                                    $edit_url = add_query_arg(['page' => 'tmt-crm-licenses-edit', 'id' => (int)$row->id], admin_url('admin.php'));
                                    ?>
                                    <a class="button button-small" href="<?php echo esc_url($edit_url); ?>"><?php _e('Edit', 'tmt-crm'); ?></a>
                                </td>
                            </tr>
                    <?php endforeach;
                    endif; ?>
                </tbody>
            </table>

            <?php if ($pages > 1): ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links([
                            'base'      => add_query_arg(['paged' => '%#%', 'days' => $days]),
                            'format'    => '',
                            'prev_text' => __('«'),
                            'next_text' => __('»'),
                            'total'     => $pages,
                            'current'   => $page,
                        ]);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
<?php
    }
}
