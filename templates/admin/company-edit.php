<?php

/** @var \TMT\CRM\Domain\Entity\Company|null $item */
/** @var string|null $error */
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo $item ? 'Sửa công ty' : 'Thêm công ty'; ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=tmt-crm-companies')); ?>" class="page-title-action">Quay lại</a>
    <hr class="wp-header-end">


    <?php if (!empty($error)): ?>
        <div class="notice notice-error">
            <p><?php echo esc_html($error); ?></p>
        </div>
    <?php endif; ?>


    <form method="post">
        <?php wp_nonce_field('tmt_company_save'); ?>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="company-name">Tên công ty *</label></th>
                <td><input id="company-name" class="regular-text" name="name" required value="<?php echo esc_attr($item->name ?? ''); ?>" /></td>
            </tr>
            <tr>
                <th><label for="company-tax-code">Mã số thuế</label></th>
                <td><input id="company-tax-code" class="regular-text" name="tax_code" value="<?php echo esc_attr($item->taxCode ?? ''); ?>" /></td>
            </tr>
            <tr>
                <th><label for="company-phone">Điện thoại</label></th>
                <td><input id="company-phone" class="regular-text" name="phone" value="<?php echo esc_attr($item->phone ?? ''); ?>" /></td>
            </tr>
            <tr>
                <th><label for="company-email">Email</label></th>
                <td><input id="company-email" class="regular-text" name="email" value="<?php echo esc_attr($item->email ?? ''); ?>" /></td>
            </tr>
            <tr>
                <th><label for="company-website">Website</label></th>
                <td><input id="company-website" class="regular-text" name="website" value="<?php echo esc_attr($item->website ?? ''); ?>" /></td>
            </tr>
            <tr>
                <th><label for="company-address">Địa chỉ</label></th>
                <td><textarea id="company-address" name="address" class="large-text" rows="3"><?php echo esc_textarea($item->address ?? ''); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="company-note">Ghi chú</label></th>
                <td><textarea id="company-note" name="note" class="large-text" rows="4"><?php echo esc_textarea($item->note ?? ''); ?></textarea></td>
            </tr>
        </table>
        <p class="submit">
            <button type="submit" class="button button-primary">Lưu</button>
        </p>
    </form>
</div>