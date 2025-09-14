<?php

use TMT\CRM\Modules\Quotation\Presentation\Admin\Screen\QuoteScreen;
use TMT\CRM\Modules\Quotation\Presentation\Admin\Controller\QuoteController;

$back_url = admin_url('admin.php?page=' . QuoteScreen::PAGE_SLUG);
$action_url = admin_url('admin-post.php?action=' . QuoteController::ACTION_SAVE);
?>
<?php
// templates/admin/quote/form.php
?>
<style>
    /* === FULL WIDTH cho màn Quote form (chỉ scope ở trang quotes) === */
    body.crm_page_tmt-crm-quotes .wrap.tmt-quote-form {
        max-width: none;
        /* bỏ mọi giới hạn chiều ngang */
        margin-right: 0;
        padding-right: 0;
    }

    body.crm_page_tmt-crm-quotes #wpbody-content {
        padding-bottom: 0;
        /* gọn gàng phần dưới */
    }

    /* vùng chứa chính full-bleed */
    body.crm_page_tmt-crm-quotes .tmt-quote-form__container {
        width: 100%;
        max-width: none;
    }

    /* Nếu bạn có layout 2 cột, dùng grid để kéo giãn hết chiều ngang */
    body.crm_page_tmt-crm-quotes .tmt-grid-2 {
        display: grid;
        grid-template-columns: 2fr 1fr;
        /* trái rộng hơn phải */
        gap: 16px;
    }

    /* Bảng & form chiếm đủ ngang */
    body.crm_page_tmt-crm-quotes .widefat,
    body.crm_page_tmt-crm-quotes table.form-table {
        width: 100%;
    }

    /* Hàng field gọn, responsive */
    body.crm_page_tmt-crm-quotes .field-row {
        display: grid;
        grid-template-columns: 180px 1fr;
        align-items: center;
        gap: 12px;
    }

    @media (max-width: 1024px) {
        body.crm_page_tmt-crm-quotes .tmt-grid-2 {
            grid-template-columns: 1fr;
            /* về 1 cột trên màn hẹp */
        }

        body.crm_page_tmt-crm-quotes .field-row {
            grid-template-columns: 1fr;
        }
    }

    /* Nếu dùng .postbox/metabox thì cho full rộng */
    body.crm_page_tmt-crm-quotes .metabox-holder,
    body.crm_page_tmt-crm-quotes .postbox {
        max-width: none;
    }
</style>

<div class="wrap tmt-quote-form">
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
                    <div class="form-field">
                        <?php
                        // Prefill: ưu tiên POST (sau khi submit lỗi) → fallback dữ liệu cũ (khi edit)
                        $company_id_prefill   = isset($_POST['company_id']) ? (int) $_POST['company_id'] : (int) ($company_id_old ?? 0);
                        $company_text_prefill = isset($_POST['company_text'])
                            ? sanitize_text_field(wp_unslash($_POST['company_text']))
                            : (string) ($company_name_old ?? '');

                        // (Tuỳ chọn) nếu bạn muốn cung cấp nonce qua form thay vì wp_localize_script:
                        // $ajax_nonce = wp_create_nonce('tmt_crm_select2');
                        ?>
                        <label for="company_id"><?php _e('Công ty (hoá đơn)', 'tmt-crm'); ?></label>

                        <select
                            id="company_id"
                            name="company_id"
                            class="js-select2-company"
                            data-ajax-action="tmt_crm_search_companies"
                            data-placeholder="<?php esc_attr_e('Chọn công ty…', 'tmt-crm'); ?>"
                            data-initial-id="<?php echo esc_attr($company_id_prefill); ?>"
                            data-initial-text="<?php echo esc_attr($company_text_prefill); ?>"
                            data-min-length="1"
                            data-allow-clear="1"
                            style="min-width: 350px"
                            <?php /* required */ ?>></select>

                        <input type="hidden" id="company_text" name="company_text"
                            value="<?php echo esc_attr($company_text_prefill); ?>" />

                        <?php
                        // (Tuỳ chọn) nếu muốn gửi nonce qua request dễ dàng, thêm hidden để JS đọc:
                        // echo '<input type="hidden" id="_ajax_nonce" value="'. esc_attr($ajax_nonce) .'">';
                        ?>

                        <p class="description">
                            <?php esc_html_e('Gõ vài ký tự để tìm công ty, ví dụ: "TMT", "Minh Anh"…', 'tmt-crm'); ?>
                        </p>
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