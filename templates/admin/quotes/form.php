<?php

use TMT\CRM\Presentation\Admin\QuoteScreen;

$back_url = admin_url('admin.php?page=' . QuoteScreen::PAGE_SLUG);
$action_url = admin_url('admin-post.php?action=' . QuoteScreen::ACTION_SAVE);
?>
<div class="wrap tmtcrm">
    <h1 class="wp-heading-inline"><?php _e('Tạo/Chỉnh sửa Báo giá', 'tmt-crm'); ?></h1>
    <a href="<?php echo esc_url($back_url); ?>" class="page-title-action"><?php _e('Quay lại danh sách', 'tmt-crm'); ?></a>
    <hr class="wp-header-end" />

    <div class="card">
        <div class="toolbar">
            <div class="left">
                <strong><?php _e('Báo giá', 'tmt-crm'); ?></strong>
                <span class="pill draft" id="quoteStatusPill">draft</span>
            </div>
            <div class="right">
                <button class="btn warn" type="button" id="btnSendQuote"><?php _e('Gửi báo giá', 'tmt-crm'); ?></button>
                <button class="btn ok" type="button" id="btnAcceptQuote"><?php _e('Đánh dấu chấp nhận', 'tmt-crm'); ?></button>
            </div>
        </div>

        <form method="post" action="<?php echo esc_url($action_url); ?>">
            <?php wp_nonce_field('tmt_crm_quote_form'); ?>

            <div class="panel">
                <div class="grid col3">
                    <div>
                        <label><?php _e('Khách hàng', 'tmt-crm'); ?></label>
                        <input type="text" name="customer_text" placeholder="CTY Minh Anh" />
                        <input type="hidden" name="customer_id" value="101" />
                    </div>
                    <div>
                        <label><?php _e('Công ty (hoá đơn)', 'tmt-crm'); ?></label>
                        <input type="text" name="company_text" placeholder="Công ty TNHH Minh Anh" />
                        <input type="hidden" name="company_id" value="201" />
                    </div>
                    <div>
                        <label><?php _e('Nhân viên phụ trách', 'tmt-crm'); ?></label>
                        <input type="number" name="owner_id" value="2" />
                    </div>
                    <div>
                        <label><?php _e('Tiền tệ', 'tmt-crm'); ?></label>
                        <select id="qCurrency" name="currency">
                            <option value="VND">VND</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                    <div>
                        <label><?php _e('Ngày hết hạn', 'tmt-crm'); ?></label>
                        <input type="date" name="expires_at" id="qExpire" />
                    </div>
                    <div>
                        <label><?php _e('Ghi chú', 'tmt-crm'); ?></label>
                        <input type="text" name="note" id="qNote" placeholder="<?php esc_attr_e('Điều khoản thanh toán, thời gian giao hàng…', 'tmt-crm'); ?>" />
                    </div>
                </div>
            </div>

            <div class="panel">
                <h3><?php _e('Danh sách hàng hoá/dịch vụ', 'tmt-crm'); ?></h3>
                <div class="table-wrap">
                    <table id="itemsTable" class="widefat striped">
                        <thead>
                            <tr>
                                <th style="width:110px">SKU</th>
                                <th><?php _e('Tên hàng', 'tmt-crm'); ?></th>
                                <th style="width:80px">SL</th>
                                <th style="width:120px"><?php _e('Đơn giá', 'tmt-crm'); ?></th>
                                <th style="width:110px"><?php _e('CK (tiền)', 'tmt-crm'); ?></th>
                                <th style="width:100px">VAT %</th>
                                <th style="width:120px;text-align:right"><?php _e('Thành tiền', 'tmt-crm'); ?></th>
                                <th style="width:70px"></th>
                            </tr>
                        </thead>
                        <tbody id="itemBody">
                            <tr>
                                <td><input type="text" name="sku[]" class="sku" placeholder="SKU" /></td>
                                <td><input type="text" name="name[]" class="name" placeholder="<?php esc_attr_e('Tên hàng/dịch vụ', 'tmt-crm'); ?>" /></td>
                                <td><input type="number" name="qty[]" class="qty" min="0" step="1" value="1" /></td>
                                <td><input type="number" name="unit_price[]" class="price" min="0" step="1000" value="0" /></td>
                                <td><input type="number" name="discount[]" class="discount" min="0" step="1000" value="0" /></td>
                                <td><input type="number" name="tax_rate[]" class="vat" min="0" step="1" value="10" /></td>
                                <td style="text-align:right"><span class="line_total">0</span></td>
                                <td><button type="button" class="btn small danger btn-remove"><?php _e('Xoá', 'tmt-crm'); ?></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="panel" style="padding-top:0">
                    <button type="button" class="btn" id="btnAddRow">+ <?php _e('Thêm dòng', 'tmt-crm'); ?></button>
                </div>
                <div class="panel" style="padding-top:0">
                    <div class="totals">
                        <div class="row"><strong><?php _e('Tạm tính (Subtotal):', 'tmt-crm'); ?></strong> <span id="subtotal">0</span></div>
                        <div class="row"><strong><?php _e('Chiết khấu (Tổng):', 'tmt-crm'); ?></strong> <span id="discount_total">0</span></div>
                        <div class="row"><strong><?php _e('Thuế (VAT):', 'tmt-crm'); ?></strong> <span id="tax_total">0</span></div>
                        <div class="hr"></div>
                        <div class="row"><strong><?php _e('Tổng cộng (Grand total):', 'tmt-crm'); ?></strong> <span id="grand_total">0</span></div>
                    </div>
                </div>
            </div>

            <div class="panel right">
                <button class="btn" type="submit" name="submit_action" value="save_draft"><?php _e('Lưu nháp', 'tmt-crm'); ?></button>
                <button class="btn primary" type="submit" name="submit_action" value="save_and_send"><?php _e('Lưu & gửi khách', 'tmt-crm'); ?></button>
            </div>
        </form>
    </div>
</div>

<script type="text/template" id="tmt-row-template">
    <tr>
  <td><input type="text" name="sku[]" class="sku" placeholder="SKU"/></td>
  <td><input type="text" name="name[]" class="name" placeholder="<?php esc_attr_e('Tên hàng/dịch vụ', 'tmt-crm'); ?>"/></td>
  <td><input type="number" name="qty[]"  class="qty"  min="0" step="1" value="1"/></td>
  <td><input type="number" name="unit_price[]" class="price" min="0" step="1000" value="0"/></td>
  <td><input type="number" name="discount[]"   class="discount" min="0" step="1000" value="0"/></td>
  <td><input type="number" name="tax_rate[]"   class="vat" min="0" step="1" value="10"/></td>
  <td style="text-align:right"><span class="line_total">0</span></td>
  <td><button type="button" class="btn small danger btn-remove"><?php _e('Xoá', 'tmt-crm'); ?></button></td>
</tr>
</script>