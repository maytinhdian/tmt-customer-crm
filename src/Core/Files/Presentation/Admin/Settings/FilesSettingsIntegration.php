<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Files\Presentation\Admin\Settings;

use TMT\CRM\Core\Settings\SettingsSectionInterface;

final class FilesSettingsIntegration implements SettingsSectionInterface
{
    /** Gọi 1 lần ở bootstrap của FilesModule */
    public static function register(): void
    {
        add_filter('tmt_crm_settings_sections', static function (array $sections) {
            $sections[] = new self();
            return $sections;
        });
    }

    public function section_id(): string
    {
        return 'files';
    }

    public function section_title(): string
    {
        return __('Files', 'tmt-crm');
    }

    /**
     * Khớp interface: tự add_settings_field cho từng field
     */
    public function register_fields(string $page_slug, string $option_key): void
    {
        $fields = $this->fields_def();

        foreach ($fields as $field) {
            add_settings_field(
                $this->section_id() . '_' . $field['id'],
                esc_html($field['label']),
                function () use ($field, $option_key) {
                    $this->render_field($field, $option_key);
                },
                $page_slug,
                $this->section_id()
            );
        }
    }

    /**
     * Defaults cho toàn section (keys trùng id trong fields_def)
     */
    public function get_defaults(): array
    {
        $out = [];
        foreach ($this->fields_def() as $f) {
            $out[$f['id']] = $f['default'];
        }
        return $out;
    }

    /**
     * Sanitize input riêng của section "files"
     * $current_all: toàn bộ settings hiện tại (để merge nếu cần)
     */
    public function sanitize(array $input, array $current_all): array
    {
        $clean = [];
        $defs  = $this->fields_def();

        // Build map for quick lookup of sanitize cb
        $byId = [];
        foreach ($defs as $f) {
            $byId[$f['id']] = $f;
        }

        foreach ($input as $key => $val) {
            if (!isset($byId[$key])) {
                // ignore unknown keys
                continue;
            }
            $f = $byId[$key];
            if (isset($f['sanitize_cb']) && is_callable($f['sanitize_cb'])) {
                $clean[$key] = call_user_func($f['sanitize_cb'], $val);
            } else {
                // fallback theo type
                $clean[$key] = $this->fallback_sanitize($f['type'], $val);
            }
        }

        // Bảo đảm có đủ keys (nếu thiếu thì gán default)
        foreach ($defs as $f) {
            if (!array_key_exists($f['id'], $clean)) {
                $clean[$f['id']] = $f['default'];
            }
        }

        return $clean;
    }

    /* -------------------- Helpers -------------------- */

    /**
     * Khai báo field một chỗ (id, label, type, options, default, description, sanitize_cb)
     */
    private function fields_def(): array
    {
        return [
            [
                'id'          => 'driver',
                'label'       => __('Storage driver', 'tmt-crm'),
                'type'        => 'select',
                'options'     => ['wp_uploads' => 'WP Uploads'], // mở rộng sau: s3, gcs...
                'default'     => 'wp_uploads',
                'description' => __('Where files are physically stored.', 'tmt-crm'),
                'sanitize_cb' => function ($v) {
                    $v = (string)$v;
                    return in_array($v, ['wp_uploads'], true) ? $v : 'wp_uploads';
                },
            ],
            [
                'id'          => 'visibility_default',
                'label'       => __('Default visibility', 'tmt-crm'),
                'type'        => 'select',
                'options'     => ['private' => 'Private', 'public' => 'Public'],
                'default'     => 'private',
                'description' => __('Default file visibility when uploading.', 'tmt-crm'),
                'sanitize_cb' => function ($v) {
                    $v = (string)$v;
                    return in_array($v, ['private', 'public'], true) ? $v : 'private';
                },
            ],
            [
                'id'          => 'max_size_mb',
                'label'       => __('Max file size (MB)', 'tmt-crm'),
                'type'        => 'number',
                'default'     => 25,
                'description' => __('Maximum allowed file size per upload.', 'tmt-crm'),
                'sanitize_cb' => fn($v) => max(1, (int)$v),
            ],
            [
                'id'          => 'allowed_mime',
                'label'       => __('Allowed MIME (comma-separated)', 'tmt-crm'),
                'type'        => 'text',
                'default'     => 'image/jpeg,image/png,application/pdf,text/csv,application/zip',
                'description' => __('Comma-separated list of allowed MIME types.', 'tmt-crm'),
                'sanitize_cb' => fn($v) => sanitize_text_field((string)$v),
            ],
            [
                'id'          => 'keep_days',
                'label'       => __('Auto-purge soft-deleted after (days)', 'tmt-crm'),
                'type'        => 'number',
                'default'     => 0,
                'description' => __('0 = never auto-purge soft-deleted files.', 'tmt-crm'),
                'sanitize_cb' => fn($v) => max(0, (int)$v),
            ],
        ];
    }

    private function render_field(array $field, string $option_key): void
    {
        $all = (array) get_option($option_key, []);
        $val = $all[$this->section_id()][$field['id']] ?? $field['default'];
        $name = $option_key . '[' . $this->section_id() . '][' . $field['id'] . ']';
        $id   = $this->section_id() . '_' . $field['id'];

        switch ($field['type']) {
            case 'select':
                echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">';
                foreach (($field['options'] ?? []) as $k => $label) {
                    echo '<option value="' . esc_attr((string)$k) . '" ' . selected((string)$val, (string)$k, false) . '>' . esc_html((string)$label) . '</option>';
                }
                echo '</select>';
                break;

            case 'number':
                echo '<input type="number" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr((string)$val) . '" class="small-text" />';
                break;

            default: // text
                echo '<input type="text" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr((string)$val) . '" class="regular-text" />';
                break;
        }

        if (!empty($field['description'])) {
            echo '<p class="description">' . esc_html((string)$field['description']) . '</p>';
        }
    }

    private function fallback_sanitize(string $type, mixed $v): mixed
    {
        return match ($type) {
            'number' => (int)$v,
            default  => sanitize_text_field((string)$v),
        };
    }
}
