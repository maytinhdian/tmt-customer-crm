<?php

/**
 * File: templates/admin/companies/company-contacts-box.php
 */
/** @var int $company_id */
/** @var array $contacts  */
/** @var array $roles     */
/** @var string $nonce    */
/** @var string $action_add $action_end $action_primary $action_delete */

use TMT\CRM\Shared\Container;
use TMT\CRM\Presentation\Admin\ListTable\CompanyContactsListTable;
use TMT\CRM\Domain\Repositories\CompanyContactRepositoryInterface;


defined('ABSPATH') || exit;
?>
<div class="tmtcrm-box tmtcrm-company-contacts">
    <h2 class="title">Liên hệ (Contacts)</h2>
    <?php if (isset($_GET['cc_msg'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?=
                esc_html(match ($_GET['cc_msg']) {
                    'saved' => 'Đã lưu liên hệ.',
                    'ended' => 'Đã kết thúc liên hệ.',
                    'deleted' => 'Đã xoá liên hệ.',
                    'primary_set' => 'Đã đặt liên hệ chính.',
                    default => 'Thao tác đã thực hiện.'
                })
                ?></p>
        </div>
    <?php endif; ?>
    <?php
    /** @var int $company_id đã có sẵn từ màn hình cha */
    $add_url = add_query_arg([
        'page'       => 'tmt-crm-company',
        'tab'        => 'contacts',
        'action'     => 'add',
        'company_id' => (int)$company_id,
    ], admin_url('admin.php'));


    ?>
    <div class="company_contact">
        <h4>Danh sách liên hệ</h4>
    </div>

    <hr>

    <h3>Thêm liên hệ</h3>
    <form method="post" action="<?= esc_url($action_add); ?>">
        <?php wp_nonce_field('tmt_crm_company_contacts'); ?>
        <input type="hidden" name="company_id" value="<?= (int)$company_id; ?>">

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="contact_customer_id"><?php _e('Tên người liên hệ', 'tmt-crm'); ?></label>
                </th>
                <td>
                    <select id="contact_id"
                        name="contact_id"
                        class="regular-text js-customer-select"
                        data-initial-id="<?php echo esc_attr((string) $customer_id_selected); ?>">
                    </select>
                    <p class="description"><?php _e('Gõ để tìm tên người liên hệ (Customer).', 'tmt-crm'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="role">Vai trò</label></th>
                <td>
                    <select name="role" id="role" required>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= esc_attr($r); ?>"><?= esc_html($r); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="title">Chức vụ</label></th>
                <td><input type="text" name="title" id="title" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="start_date">Ngày bắt đầu</label></th>
                <td><input type="date" name="start_date" id="start_date" value="<?= esc_attr(date('Y-m-d')); ?>"></td>
            </tr>
            <tr>
                <th><label for="is_primary">Liên hệ chính?</label></th>
                <td><label><input type="checkbox" name="is_primary" id="is_primary" value="1"> Đặt làm liên hệ chính cho vai trò này</label></td>
            </tr>
            <tr>
                <th><label for="note">Ghi chú</label></th>
                <td><textarea name="note" id="note" rows="3" class="large-text"></textarea></td>
            </tr>
        </table>

        <p>
            <button type="submit" class="button button-primary">Lưu liên hệ</button>
        </p>
    </form>
</div>