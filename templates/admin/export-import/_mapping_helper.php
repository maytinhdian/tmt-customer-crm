<?php
/**
 * Admin View: helper partial - hướng dẫn chuyển mapping_text -> array
 * Không bắt buộc, chỉ là gợi ý dùng trong Controller khi nhận POST.
 *
 * $mapping_text = (string) ($_POST['mapping_text'] ?? '');
 * $mapping = [];
 * foreach (preg_split('/\r?\n/', $mapping_text) as $line) {
 *     $line = trim($line);
 *     if ($line === '' || strpos($line, ':') === false) continue;
 *     [$src, $dst] = array_map('trim', explode(':', $line, 2));
 *     if ($src !== '' && $dst !== '') { $mapping[$src] = $dst; }
 * }
 * // $mapping giờ là mảng [source_col => target_field]
 */
