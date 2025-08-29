<?php
use TMT\CRM\Presentation\Admin\QuoteScreen;
/** Action submit */
$action_url = admin_url('admin-post.php?action=' . QuoteScreen::ACTION_SAVE);
?>
<div class="wrap">
  <h1><?php esc_html_e('Tạo/Chỉnh sửa Báo giá', 'tmt-crm'); ?></h1>

  <form method="post" action="<?php echo esc_url($action_url); ?>">
    <?php wp_nonce_field('tmt_crm_quote_form'); ?>

    <table class="form-table" role="presentation">
      <tbody>
      <tr>
        <th><label for="customer_id"><?php _e('Khách hàng', 'tmt-crm'); ?></label></th>
        <td><input id="customer_id" name="customer_id" type="number" class="regular-text" required /></td>
      </tr>
      <tr>
        <th><label for="company_id"><?php _e('Công ty (hoá đơn)', 'tmt-crm'); ?></label></th>
        <td><input id="company_id" name="company_id" type="number" class="regular-text" /></td>
      </tr>
      <tr>
        <th><label for="owner_id"><?php _e('Nhân viên phụ trách', 'tmt-crm'); ?></label></th>
        <td><input id="owner_id" name="owner_id" type="number" class="regular-text" required /></td>
      </tr>
      <tr>
        <th><label for="currency"><?php _e('Tiền tệ', 'tmt-crm'); ?></label></th>
        <td>
          <select id="currency" name="currency">
            <option value="VND">VND</option>
            <option value="USD">USD</option>
          </select>
        </td>
      </tr>
      <tr>
        <th><label for="note"><?php _e('Ghi chú', 'tmt-crm'); ?></label></th>
        <td><textarea id="note" name="note" class="large-text" rows="3"></textarea></td>
      </tr>
      </tbody>
    </table>

    <h2><?php _e('Danh sách hàng hoá/dịch vụ', 'tmt-crm'); ?></h2>
    <table class="widefat striped">
      <thead>
        <tr>
          <th>SKU</th>
          <th><?php _e('Tên hàng', 'tmt-crm'); ?></th>
          <th>SL</th>
          <th><?php _e('Đơn giá', 'tmt-crm'); ?></th>
          <th><?php _e('CK (tiền)', 'tmt-crm'); ?></th>
          <th>VAT %</th>
          <th><?php _e('Thành tiền', 'tmt-crm'); ?></th>
          <th></th>
        </tr>
      </thead>
      <tbody id="tmt-quote-items">
        <tr>
          <td><input name="sku[]" type="text" /></td>
          <td><input name="name[]" type="text" class="regular-text" /></td>
          <td><input name="qty[]" type="number" step="1" value="1" /></td>
          <td><input name="unit_price[]" type="number" step="1000" value="0" /></td>
          <td><input name="discount[]" type="number" step="1000" value="0" /></td>
          <td><input name="tax_rate[]" type="number" step="1" value="10" /></td>
          <td class="tmt-line-total">0</td>
          <td><button type="button" class="button tmt-row-del">–</button></td>
        </tr>
      </tbody>
    </table>
    <p><button type="button" class="button" id="tmt-row-add">+ <?php _e('Thêm dòng', 'tmt-crm'); ?></button></p>

    <h3><?php _e('Tổng cộng', 'tmt-crm'); ?></h3>
    <table class="form-table">
      <tbody>
        <tr><th>Subtotal</th><td><span id="tmt-subtotal">0</span></td></tr>
        <tr><th><?php _e('Chiết khấu', 'tmt-crm'); ?></th><td><span id="tmt-discount-total">0</span></td></tr>
        <tr><th>VAT</th><td><span id="tmt-tax-total">0</span></td></tr>
        <tr><th><strong><?php _e('Grand total', 'tmt-crm'); ?></strong></th><td><strong id="tmt-grand-total">0</strong></td></tr>
      </tbody>
    </table>

    <p class="submit">
      <button type="submit" class="button button-primary"><?php _e('Lưu nháp', 'tmt-crm'); ?></button>
    </p>
  </form>
</div>
