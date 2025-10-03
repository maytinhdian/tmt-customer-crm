<?php
/**
 * Partial: Hiển thị preview sau khi upload (cột + 5 dòng mẫu)
 * Biến $preview lấy từ transient 'tmt_crm_import_preview_{jobId}'
 */
if (!isset($preview) || !is_array($preview)) { return; }
$cols = array_map('sanitize_text_field', (array)($preview['columns'] ?? []));
$samples = (array)($preview['sample_rows'] ?? []);
$total = isset($preview['total']) ? (int)$preview['total'] : 0;
?>
<div class="notice notice-info" style="padding:8px 12px;">
    <p><strong>Preview:</strong> Tổng dòng: <?php echo esc_html((string)$total); ?>. Hiển thị 5 dòng mẫu bên dưới.</p>
</div>
<div class="tmt-preview-table" style="overflow:auto; max-height:360px; border:1px solid #ccd0d4; border-radius:6px;">
    <table class="widefat striped">
        <thead>
            <tr>
                <?php foreach ($cols as $c): ?>
                    <th><?php echo esc_html($c); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php
            $count = 0;
            foreach ($samples as $row):
                if ($count++ >= 5) break;
            ?>
                <tr>
                    <?php foreach ($cols as $c): ?>
                        <td><?php echo esc_html( (string) ($row[$c] ?? '') ); ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
