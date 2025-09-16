<?php

/**
 * Template: Quote Form (full-width, company-first with Select2)
 * Vị trí gợi ý: templates/admin/quote/form.php
 */

declare(strict_types=1);

use TMT\CRM\Modules\Quotation\Presentation\Admin\Controller\QuoteController;

/** @var object|array|null $quote (được truyền từ QuoteScreen::render_form()) */
$Q = is_array($quote ?? null) ? (object)$quote : ($quote ?? null);

$id          = isset($Q->id) ? (int)$Q->id : 0;
$company_id  = isset($Q->company_id) ? (int)$Q->company_id : 0;          // công ty thay cho customer
$owner_id    = isset($Q->owner_id) ? (int)$Q->owner_id : get_current_user_id();
$note        = isset($Q->note) ? (string)$Q->note : '';
$items       = [];

// Dữ liệu items
if (!empty($Q->items) && is_array($Q->items)) {
    foreach ($Q->items as $row) {
        $items[] = [
            'sku'        => sanitize_text_field($row['sku'] ?? ($row->sku ?? '')),
            'name'       => sanitize_text_field($row['name'] ?? ($row->name ?? '')),
            'qty'        => (float)($row['qty'] ?? ($row->qty ?? 1)),
            'unit_price' => (float)($row['unit_price'] ?? ($row->unit_price ?? 0)),
            'discount'   => (float)($row['discount'] ?? ($row->discount ?? 0)),
            'tax_rate'   => (float)($row['tax_rate'] ?? ($row->tax_rate ?? 0)),
        ];
    }
}
if (empty($items)) {
    $items[] = ['sku' => '', 'name' => '', 'qty' => 1, 'unit_price' => 0, 'discount' => 0, 'tax_rate' => 0];
}

/** ===== NGƯỜI LIÊN HỆ (mặc định lấy set_primary của công ty) ===== */
$contact_name  = isset($Q->contact_name)  ? (string)$Q->contact_name  : '';
$contact_email = isset($Q->contact_email) ? (string)$Q->contact_email : '';
$contact_phone = isset($Q->contact_phone) ? (string)$Q->contact_phone : '';
$company_name  = isset($Q->company_name)  ? (string)$Q->company_name  : '';

// Nếu chưa có, thử lấy từ service (nếu dự án đã có)
if ($company_id > 0 && ($company_name === '' || ($contact_name === '' && $contact_email === '' && $contact_phone === ''))) {
    try {
        $svc = \TMT\CRM\Shared\Container\Container::get('company-service');
        if (is_object($svc)) {
            // Lấy tên công ty
            if (method_exists($svc, 'find')) {
                $c = $svc->find($company_id);
                if ($c) {
                    $company_name = sanitize_text_field(is_array($c) ? ($c['name'] ?? '') : ($c->name ?? '')) ?: $company_name;
                }
            }
            // Lấy liên hệ chính
            $pc = null;
            if (method_exists($svc, 'get_primary_contact')) {
                $pc = $svc->get_primary_contact($company_id);
            } elseif (method_exists($svc, 'find_primary_contact')) {
                $pc = $svc->find_primary_contact($company_id);
            }
            if ($pc) {
                $contact_name  = sanitize_text_field(is_array($pc) ? ($pc['name']  ?? '') : ($pc->name  ?? '')) ?: $contact_name;
                $contact_email = sanitize_text_field(is_array($pc) ? ($pc['email'] ?? '') : ($pc->email ?? '')) ?: $contact_email;
                $contact_phone = sanitize_text_field(is_array($pc) ? ($pc['phone'] ?? '') : ($pc->phone ?? '')) ?: $contact_phone;
            }
        }
    } catch (\Throwable $e) {
        // bỏ qua nếu thiếu service
    }
}

/** ===== SĐT người phụ trách (owner) ===== */
$owner_phone = isset($Q->owner_phone) ? (string)$Q->owner_phone : '';
if ($owner_phone === '' && $owner_id > 0) {
    // cố gắng lấy từ user meta
    $owner_phone = get_user_meta($owner_id, 'phone', true);
    if (!$owner_phone) $owner_phone = get_user_meta($owner_id, 'billing_phone', true);
    $owner_phone = is_string($owner_phone) ? $owner_phone : '';
}

/** Nonce cho AJAX company search */
$company_search_nonce = wp_create_nonce('tmt_crm_company_search');
?>
<style>
    /* === FULL WIDTH cho màn Quote form (chỉ scope ở trang quotes) === */
    body.crm_page_tmt-crm-quotes .wrap.tmt-quote-form {
        max-width: none;
        margin-right: 0;
        padding-right: 0;
    }

    body.crm_page_tmt-crm-quotes #wpbody-content {
        padding-bottom: 0;
    }

    /* Vùng chứa chính full-bleed */
    body.crm_page_tmt-crm-quotes .tmt-quote-form__container {
        width: 100%;
        max-width: none;
    }

    /* Lưới 2 cột (trái nội dung, phải summary) */
    body.crm_page_tmt-crm-quotes .tmt-grid-2 {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 16px;
    }

    /* Field row gọn và thẳng hàng */
    body.crm_page_tmt-crm-quotes .field-row {
        display: grid;
        grid-template-columns: 220px 1fr;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    /* Bảng items chiếm đủ rộng */
    body.crm_page_tmt-crm-quotes .widefat,
    body.crm_page_tmt-crm-quotes table.form-table {
        width: 100%;
    }

    /* Card summary nhỏ gọn */
    body.crm_page_tmt-crm-quotes .tmt-card {
        background: #fff;
        border: 1px solid #dcdcde;
        border-radius: 6px;
        padding: 12px;
    }

    body.crm_page_tmt-crm-quotes .tmt-card h3 {
        margin: 0 0 8px;
    }

    /* Nút hành động */
    body.crm_page_tmt-crm-quotes .tmt-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    /* Select2 full width + theme hoà nhập WP Admin */
    body.crm_page_tmt-crm-quotes .select2-container {
        width: 100% !important;
    }

    body.crm_page_tmt-crm-quotes .select2-container .select2-selection--single {
        height: 30px;
        border-color: #8c8f94;
    }

    body.crm_page_tmt-crm-quotes .select2-selection__rendered {
        line-height: 28px;
    }

    body.crm_page_tmt-crm-quotes .select2-selection__arrow {
        height: 28px;
    }

    /* Responsive */
    @media (max-width:1024px) {
        body.crm_page_tmt-crm-quotes .tmt-grid-2 {
            grid-template-columns: 1fr;
        }

        body.crm_page_tmt-crm-quotes .field-row {
            grid-template-columns: 1fr;
        }
    }

    /* Metabox (nếu dùng) full rộng */
    body.crm_page_tmt-crm-quotes .metabox-holder,
    body.crm_page_tmt-crm-quotes .postbox {
        max-width: none;
    }
</style>

<div class="wrap tmt-quote-form">
    <h1 class="wp-heading-inline">
        <?php echo esc_html($id > 0 ? __('Sửa báo giá', 'tmt-crm') : __('Tạo báo giá', 'tmt-crm')); ?>
    </h1>
    <hr class="wp-header-end" />

    <div class="tmt-quote-form__container">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="<?php echo esc_attr(QuoteController::ACTION_SAVE); ?>">
            <?php wp_nonce_field(QuoteController::ACTION_SAVE); ?>
            <?php if ($id > 0): ?>
                <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>">
            <?php endif; ?>

            <div class="tmt-grid-2">
                <!-- Cột trái: Thông tin & Items -->
                <div class="tmt-col-left">

                    <div class="tmt-card" style="margin-bottom:12px;">
                        <h3><?php esc_html_e('Thông tin công ty & liên hệ', 'tmt-crm'); ?></h3>

                        <div class="field-row">
                            <label for="company_id"><strong><?php esc_html_e('Công ty', 'tmt-crm'); ?></strong></label>
                            <select id="company_id"
                                data-placeholder="Chọn công ty..."
                                data-ajax-action="tmt_crm_search_companies"
                                data-initial-id="1">
                            </select>
                        </div>

                        <div class="field-row">
                            <label><strong><?php esc_html_e('Người liên hệ (mặc định: liên hệ chính)', 'tmt-crm'); ?></strong></label>
                            <div>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                                    <input type="text" name="contact_name" class="regular-text" placeholder="<?php esc_attr_e('Họ tên', 'tmt-crm'); ?>"
                                        value="<?php echo esc_attr($contact_name); ?>">
                                    <input type="text" name="contact_email" class="regular-text" placeholder="<?php esc_attr_e('Email', 'tmt-crm'); ?>"
                                        value="<?php echo esc_attr($contact_email); ?>">
                                </div>
                                <div style="margin-top:8px;">
                                    <input type="text" name="contact_phone" class="regular-text" placeholder="<?php esc_attr_e('Số điện thoại liên hệ', 'tmt-crm'); ?>"
                                        value="<?php echo esc_attr($contact_phone); ?>">
                                </div>
                                <p style="margin-top:8px;">
                                    <button type="button" class="button" id="tmt-refresh-contact"><?php esc_html_e('Sử dụng liên hệ chính của công ty', 'tmt-crm'); ?></button>
                                </p>
                                <p class="description" style="margin:4px 0 0;">
                                    <?php esc_html_e('Khi chọn công ty, hệ thống sẽ tự đề xuất liên hệ chính nếu API trả về primary_contact.', 'tmt-crm'); ?>
                                </p>
                            </div>
                        </div>

                        <div class="field-row">
                            <label for="owner_id"><strong><?php esc_html_e('Người phụ trách (User ID)', 'tmt-crm'); ?></strong></label>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px;">
                                <input type="number" min="1" class="regular-text" id="owner_id" name="owner_id"
                                    value="<?php echo esc_attr($owner_id ?: ''); ?>" placeholder="<?php esc_attr_e('Nhập User ID phụ trách', 'tmt-crm'); ?>">
                                <input type="text" class="regular-text" id="owner_phone" name="owner_phone"
                                    value="<?php echo esc_attr($owner_phone); ?>" placeholder="<?php esc_attr_e('SĐT người phụ trách', 'tmt-crm'); ?>">
                            </div>
                            <p class="description" style="margin:4px 0 0;">
                                <?php esc_html_e('SĐT người phụ trách cố gắng lấy từ user meta (phone/billing_phone) nếu để trống.', 'tmt-crm'); ?>
                            </p>
                        </div>

                        <div class="field-row">
                            <label for="note"><strong><?php esc_html_e('Ghi chú', 'tmt-crm'); ?></strong></label>
                            <textarea id="note" name="note" rows="4" class="large-text" placeholder="<?php esc_attr_e('Ghi chú nội bộ…', 'tmt-crm'); ?>"><?php echo esc_textarea($note); ?></textarea>
                        </div>
                    </div>

                    <div class="tmt-card">
                        <h3><?php esc_html_e('Sản phẩm/Dịch vụ', 'tmt-crm'); ?></h3>

                        <table class="widefat fixed striped" id="quote-items">
                            <thead>
                                <tr>
                                    <th style="width:120px;"><?php esc_html_e('SKU', 'tmt-crm'); ?></th>
                                    <th><?php esc_html_e('Tên', 'tmt-crm'); ?></th>
                                    <th style="width:90px; text-align:right;"><?php esc_html_e('SL', 'tmt-crm'); ?></th>
                                    <th style="width:140px; text-align:right;"><?php esc_html_e('Đơn giá', 'tmt-crm'); ?></th>
                                    <th style="width:120px; text-align:right;"><?php esc_html_e('Giảm', 'tmt-crm'); ?></th>
                                    <th style="width:110px; text-align:right;"><?php esc_html_e('Thuế %', 'tmt-crm'); ?></th>
                                    <th style="width:36px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $i => $row): ?>
                                    <tr>
                                        <td><input type="text" name="items[<?php echo $i; ?>][sku]" value="<?php echo esc_attr($row['sku']); ?>" class="regular-text"></td>
                                        <td><input type="text" name="items[<?php echo $i; ?>][name]" value="<?php echo esc_attr($row['name']); ?>" class="regular-text"></td>
                                        <td style="text-align:right;"><input type="number" step="0.001" min="0" name="items[<?php echo $i; ?>][qty]" value="<?php echo esc_attr($row['qty']); ?>" style="width:100%; text-align:right;"></td>
                                        <td style="text-align:right;"><input type="number" step="1" min="0" name="items[<?php echo $i; ?>][unit_price]" value="<?php echo esc_attr($row['unit_price']); ?>" style="width:100%; text-align:right;"></td>
                                        <td style="text-align:right;"><input type="number" step="1" min="0" name="items[<?php echo $i; ?>][discount]" value="<?php echo esc_attr($row['discount']); ?>" style="width:100%; text-align:right;"></td>
                                        <td style="text-align:right;"><input type="number" step="0.001" min="0" name="items[<?php echo $i; ?>][tax_rate]" value="<?php echo esc_attr($row['tax_rate']); ?>" style="width:100%; text-align:right;"></td>
                                        <td><button type="button" class="button-link-delete tmt-remove-row" title="<?php esc_attr_e('Xoá dòng', 'tmt-crm'); ?>">✕</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <p style="margin-top:10px;">
                            <button type="button" class="button" id="tmt-add-row"><?php esc_html_e('Thêm dòng', 'tmt-crm'); ?></button>
                        </p>
                    </div>

                </div>

                <!-- Cột phải: Tổng quan / Hành động -->
                <div class="tmt-col-right">
                    <div class="tmt-card">
                        <h3><?php esc_html_e('Hành động', 'tmt-crm'); ?></h3>
                        <div class="tmt-actions">
                            <button type="submit" class="button button-primary button-large">
                                <?php echo esc_html($id > 0 ? __('Cập nhật', 'tmt-crm') : __('Tạo báo giá', 'tmt-crm')); ?>
                            </button>
                            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=tmt-crm-quotes')); ?>">
                                <?php esc_html_e('Về danh sách', 'tmt-crm'); ?>
                            </a>
                        </div>
                    </div>

                    <div class="tmt-card" style="margin-top:12px;">
                        <h3><?php esc_html_e('Tổng quan', 'tmt-crm'); ?></h3>
                        <p style="margin:0;"><?php esc_html_e('Tổng tiền sẽ được tính khi lưu, theo QuoteService.', 'tmt-crm'); ?></p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    (function() {
        // ===== Helpers cho bảng items =====
        const table = document.getElementById('quote-items');
        const btnAdd = document.getElementById('tmt-add-row');

        function nextIndex() {
            const rows = table.tBodies[0].querySelectorAll('tr');
            return rows.length;
        }

        function addRow(data = {}) {
            const i = nextIndex();
            const esc = (s) => String(s || '').replace(/"/g, '&quot;');
            const num = (v, d = 0) => isNaN(Number(v)) ? d : Number(v);
            const tr = document.createElement('tr');
            tr.innerHTML = `
      <td><input type="text" name="items[${i}][sku]" value="${esc(data.sku)}" class="regular-text"></td>
      <td><input type="text" name="items[${i}][name]" value="${esc(data.name)}" class="regular-text"></td>
      <td style="text-align:right;"><input type="number" step="0.001" min="0" name="items[${i}][qty]" value="${num(data.qty,1)}" style="width:100%; text-align:right;"></td>
      <td style="text-align:right;"><input type="number" step="1" min="0" name="items[${i}][unit_price]" value="${num(data.unit_price)}" style="width:100%; text-align:right;"></td>
      <td style="text-align:right;"><input type="number" step="1" min="0" name="items[${i}][discount]" value="${num(data.discount)}" style="width:100%; text-align:right;"></td>
      <td style="text-align:right;"><input type="number" step="0.001" min="0" name="items[${i}][tax_rate]" value="${num(data.tax_rate)}" style="width:100%; text-align:right;"></td>
      <td><button type="button" class="button-link-delete tmt-remove-row" title="<?php echo esc_js(__('Xoá dòng', 'tmt-crm')); ?>">✕</button></td>
    `;
            table.tBodies[0].appendChild(tr);
        }

        function onRemove(e) {
            const btn = e.target.closest('.tmt-remove-row');
            if (!btn) return;
            e.preventDefault();
            const tr = btn.closest('tr');
            if (tr && confirm('<?php echo esc_js(__('Xoá dòng này?', 'tmt-crm')); ?>')) {
                tr.remove();
            }
        }
        if (btnAdd) btnAdd.addEventListener('click', function(e) {
            e.preventDefault();
            addRow({});
        });
        table.addEventListener('click', onRemove, false);
    })();
</script>