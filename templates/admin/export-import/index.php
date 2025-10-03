<?php

/**
 * Admin View: Core/ExportImport - Index (Enhanced)
 */
defined('ABSPATH') || exit;

$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'export';
$download   = isset($_GET['download']) ? base64_decode((string)$_GET['download']) : null;
$notice     = isset($_GET['notice']) ? sanitize_text_field($_GET['notice']) : null;

$entities = $entities ?? [
    'company'  => 'Company',
    'customer' => 'Customer',
    'contact'  => 'Company Contact',
];
$default_columns = $default_columns ?? [
    'company'  => ['id', 'name', 'tax_code', 'email', 'phone', 'address', 'created_at'],
    'customer' => ['id', 'first_name', 'last_name', 'email', 'phone', 'created_at'],
    'contact'  => ['id', 'full_name', 'company_id', 'email', 'phone', 'created_at'],
];
?>
<div class="wrap">
    <h1><?php echo esc_html($title ?? 'Export / Import'); ?></h1>

    <?php if ($notice === 'export_failed') : ?>
        <div class="notice notice-error">
            <p>Export thất bại. Vui lòng thử lại.</p>
        </div>
    <?php endif; ?>

    <?php if ($download && file_exists($download)) : ?>
        <div class="notice notice-success">
            <p>File export đã sẵn sàng: <a href="<?php echo esc_url(wp_get_upload_dir()['baseurl'] . '/tmt-crm-exports/' . basename($download)); ?>" target="_blank">Tải về</a></p>
        </div>
    <?php endif; ?>

    <h2 class="nav-tab-wrapper">
        <a href="<?php echo esc_url(add_query_arg(['page' => 'tmt-crm-export-import', 'tab' => 'export'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $active_tab === 'export' ? 'nav-tab-active' : ''; ?>">Export</a>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'tmt-crm-export-import', 'tab' => 'import'], admin_url('admin.php'))); ?>" class="nav-tab <?php echo $active_tab === 'import' ? 'nav-tab-active' : ''; ?>">Import</a>
    </h2>

    <?php if ($active_tab === 'export') : ?>
        <div class="card" style="padding:16px; max-width:1080px;">
            <h2>Export CSV</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('tmt_crm_export_start'); ?>
                <input type="hidden" name="action" value="tmt_crm_export_start">

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="entity_type">Entity</label></th>
                            <td>
                                <select name="entity_type" id="entity_type">
                                    <?php foreach ($entities as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="columns">Cột xuất</label></th>
                            <td>
                                <p class="description">Điền danh sách cột, cách nhau bởi dấu phẩy. Để trống dùng mặc định theo entity.</p>
                                <input type="text" id="columns" name="columns_text" class="regular-text" placeholder="id,name,email,phone" />
                                <p class="description"><em>Mặc định:</em></p>
                                <ul>
                                    <?php foreach ($default_columns as $ek => $cols): ?>
                                        <li><strong><?php echo esc_html($ek); ?></strong>: <code><?php echo esc_html(implode(',', $cols)); ?></code></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Bộ lọc</th>
                            <td>
                                <fieldset>
                                    <label>Ngày tạo từ: <input type="date" name="filters[created_from]" /></label>
                                    &nbsp;&nbsp;
                                    <label>Đến: <input type="date" name="filters[created_to]" /></label>
                                    <p class="description">MVP demo filter cơ bản. Có thể mở rộng sau.</p>
                                </fieldset>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button('Bắt đầu Export'); ?>
            </form>
        </div>
    <?php else: ?>
        <div class="card" style="padding:16px; max-width:1080px;">
            <h2>Import CSV</h2>
            <?php
            $step = isset($_GET['step']) ? sanitize_text_field($_GET['step']) : 'upload';
            $job_id = isset($_GET['import_job']) ? (int) $_GET['import_job'] : 0;

            if ($step === 'map') {
                $preview = $job_id ? get_transient('tmt_crm_import_preview_' . $job_id) : null;
                if ($preview && is_array($preview)) {
                    include __DIR__ . '/partials/preview-panel.php';
                }
            ?>
                <p><strong>Bước 2/2:</strong> Ánh xạ cột &rarr; Trường dữ liệu.</p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('tmt_crm_import_commit'); ?>
                    <input type="hidden" name="action" value="tmt_crm_import_commit">
                    <input type="hidden" name="job_id" value="<?php echo esc_attr($job_id); ?>">

                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">Mapping</th>
                                <td>
                                    <p class="description">Điền theo dạng <code>source_col:target_field</code> mỗi dòng một cặp. Ví dụ: <code>name:name</code></p>
                                    <textarea name="mapping_text" rows="8" class="large-text" placeholder="name:name&#10;email:email"></textarea>
                                    <p class="description">Gợi ý thêm: <code>phone:phone</code>, <code>address:address</code>, <code>tax_code:tax_code</code>...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <?php submit_button('Thực thi Import'); ?>
                </form>
            <?php } else { ?>
                <p><strong>Bước 1/2:</strong> Tải lên file CSV.</p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field('tmt_crm_import_preview'); ?>
                    <input type="hidden" name="action" value="tmt_crm_import_preview">

                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><label for="entity_type_i">Entity</label></th>
                                <td>
                                    <select name="entity_type" id="entity_type_i">
                                        <?php foreach ($entities as $key => $label): ?>
                                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="file">File CSV</label></th>
                                <td>
                                    <input type="file" name="file" id="file" accept=".csv,text/csv" required>
                                    <p><label><input type="checkbox" name="has_header" checked> Dòng đầu là header</label></p>
                                    <p class="description">Sau khi tải lên, hệ thống sẽ hiển thị **cột** và **mẫu 5 dòng** để bạn kiểm tra trước khi map.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <?php submit_button('Xem trước'); ?>
                </form>
            <?php } ?>
        </div>
    <?php endif; ?>
</div>