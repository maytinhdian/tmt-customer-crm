<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Support;

/**
 * View helper cho admin:
 * - Cấu trúc module: templates/admin/{module}/{file}.php
 * - Layouts: templates/admin/{module}/layouts/{file}.php
 * - Partials: templates/admin/{module}/partials/{file}.php
 *
 * An toàn:
 * - Sanitize đường dẫn tương đối, chặn ../
 * - Kiểm tra realpath phải NẰM TRONG base admin path
 * - Log _doing_it_wrong khi file không tồn tại (để dễ debug)
 *
 * Lưu ý: Hàm đặt snake-case theo quy ước của bạn.
 */
final class View
{
    /** Cache base path để tránh tính nhiều lần */
    private static ?string $cached_base_admin = null;

    /**
     * Render 1 file PHP tuyệt đối.
     * - $return = true → trả về string; false → echo trực tiếp.
     */
    // public static function render(string $abs_path, array $vars = [], bool $return = false): ?string
    // {
    //     if ($abs_path === '' || !self::is_safe_inside_base($abs_path)) {
    //         self::doing_it_wrong('render', 'Invalid or unsafe view path: ' . $abs_path);
    //         return null;
    //     }

    //     if (!is_file($abs_path)) {
    //         self::doing_it_wrong('render', 'View file not found: ' . $abs_path);
    //         return null;
    //     }

    //     if ($return) {
    //         ob_start();
    //         extract($vars, EXTR_SKIP);
    //         include $abs_path;
    //         return (string) ob_get_clean();
    //     }

    //     extract($vars, EXTR_SKIP);
    //     include $abs_path;
    //     return null;
    // }


    public static function render(string $abs_path, array $vars = [], bool $return = false): ?string
    {
        if (!defined('TMT_CRM_PATH')) {
            self::doing_it_wrong('render', 'TMT_CRM_PATH is not defined.');
            return null;
        }
        $base = rtrim(str_replace('\\', '/', (string) TMT_CRM_PATH), '/') . '/templates/admin/';
        $abs  = rtrim(str_replace('\\', '/', $abs_path), '/');

        if (strpos($abs, $base) !== 0) {
            self::doing_it_wrong('render', 'Invalid or unsafe view path: ' . $abs_path);
            return null;
        }
        if (!is_file($abs)) {
            self::doing_it_wrong('render', 'View file not found: ' . $abs);
            return null;
        }

        if ($return) {
            ob_start();
            extract($vars, EXTR_SKIP);
            include $abs;
            return (string) ob_get_clean();
        }
        extract($vars, EXTR_SKIP);
        include $abs;
        return null;
    }


    /**
     * Render file tương đối trong templates/admin.
     * Ví dụ: render_admin('company/index')
     */
    public static function render_admin(string $relative, array $vars = [], bool $return = false): ?string
    {
        $abs = self::resolve_admin_path($relative);
        return self::render($abs, $vars, $return);
    }

    /**
     * Render: templates/admin/{module}/{file}.php
     */
    public static function render_admin_module(string $module, string $file, array $vars = [], bool $return = false): ?string
    {
        $rel = self::sanitize_rel($module) . '/' . self::ensure_ext($file);
        return self::render_admin($rel, $vars, $return);
    }

    /**
     * Render: templates/admin/{module}/partials/{file}.php
     */
    public static function render_admin_partial(string $module, string $file, array $vars = [], bool $return = false): ?string
    {
        $rel = self::sanitize_rel($module) . '/partials/' . self::ensure_ext($file);
        return self::render_admin($rel, $vars, $return);
    }

    /**
     * Render: templates/admin/{module}/layouts/{file}.php
     */
    public static function render_admin_layout(string $module, string $file, array $vars = [], bool $return = false): ?string
    {
        $rel = self::sanitize_rel($module) . '/layouts/' . self::ensure_ext($file);
        return self::render_admin($rel, $vars, $return);
    }

    /**
     * Kiểm tra tồn tại file tương đối trong admin
     */
    public static function exists_admin(string $relative): bool
    {
        $abs = self::resolve_admin_path($relative);
        return is_file($abs);
    }

    // ------------------------
    // Nội bộ
    // ------------------------

    /** Base path: {PLUGIN}/templates/admin/ (có cache + filter) */
    private static function base_admin(): string
    {
        if (self::$cached_base_admin !== null) {
            return self::$cached_base_admin;
        }

        if (!defined('TMT_CRM_PATH')) {
            // Nếu thiếu, log để dev biết set TMT_CRM_PATH
            self::doing_it_wrong('base_admin', 'TMT_CRM_PATH is not defined.');
            self::$cached_base_admin = '';
            return '';
        }

        $base = rtrim((string) TMT_CRM_PATH, '/\\') . '/templates/admin/';

        // Cho phép override qua filter nếu tương lai muốn support skin/override
        if (function_exists('apply_filters')) {
            $base = (string) apply_filters('tmt_crm_view_admin_base_path', $base);
        }

        // Chuẩn hoá slash + luôn có dấu / cuối
        $base = rtrim($base, '/\\') . '/';
        self::$cached_base_admin = $base;
        return $base;
    }

    /** Thêm .php nếu thiếu đuôi */
    private static function ensure_ext(string $file): string
    {
        $file = ltrim($file, '/\\');
        return (pathinfo($file, PATHINFO_EXTENSION) === '') ? ($file . '.php') : $file;
    }

    /**
     * Sanitize đường dẫn tương đối (module/file)
     * - Chỉ cho phép [a-zA-Z0-9/_-]
     * - Loại bỏ chuỗi rỗng, '.' , '..'
     */
    // private static function sanitize_rel(string $relative): string
    // {
    //     $relative = str_replace('\\', '/', trim($relative, "/ \t\n\r\0\x0B"));

    //     $clean = [];
    //     foreach (explode('/', $relative) as $seg) {
    //         if ($seg === '' || $seg === '.' || $seg === '..') {
    //             continue;
    //         }
    //         // Chỉ cho ký tự an toàn
    //         $seg = preg_replace('~[^a-zA-Z0-9_-]~', '', $seg);
    //         if ($seg !== '') {
    //             $clean[] = $seg;
    //         }
    //     }

    //     return implode('/', $clean);
    // }

    private static function sanitize_rel(string $relative): string
    {
        $relative = str_replace('\\', '/', trim($relative, "/ \t\n\r\0\x0B"));
        $parts    = explode('/', $relative);
        $count    = count($parts);

        $clean = [];
        foreach ($parts as $i => $seg) {
            if ($seg === '' || $seg === '.' || $seg === '..') {
                continue;
            }

            // Segment cuối (file): cho phép dấu chấm để giữ extension
            if ($i === $count - 1) {
                // Chỉ cho [a-zA-Z0-9_.-]
                $seg = preg_replace('~[^a-zA-Z0-9_.-]~', '', $seg);

                // Nếu có extension mà KHÔNG phải php → cắt bỏ extension lạ
                $ext = pathinfo($seg, PATHINFO_EXTENSION);
                if ($ext !== '' && strtolower($ext) !== 'php') {
                    $seg = preg_replace('/\..*$/', '', $seg);
                }
            } else {
                // Các segment thư mục: chỉ [a-zA-Z0-9_-]
                $seg = preg_replace('~[^a-zA-Z0-9_-]~', '', $seg);
            }

            if ($seg !== '') {
                $clean[] = $seg;
            }
        }

        return implode('/', $clean);
    }

    /** Ghép và resolve đường dẫn tuyệt đối trong admin */
    private static function resolve_admin_path(string $relative): string
    {
        $base = self::base_admin();
        if ($base === '') {
            return '';
        }

        $rel  = self::ensure_ext(self::sanitize_rel($relative));
        $path = $base . $rel;

        // Nếu tồn tại → realpath để xác thực nằm trong base
        if (is_file($path)) {
            $real = realpath($path);
            if ($real && self::is_inside_base($real, $base)) {
                return $real;
            }
        }

        // Chưa tồn tại file: vẫn trả về path để render() log lỗi hữu ích
        return $path;
    }

    /** Kiểm tra path nằm trong base (dùng khi đã realpath) */
    private static function is_inside_base(string $real, string $base): bool
    {
        $real = str_replace('\\', '/', $real);
        $base = str_replace('\\', '/', $base);
        return strpos($real, rtrim($base, '/')) === 0;
    }

    /** Cho phép check nhanh 1 path tuyệt đối có an toàn và trong base admin */
    private static function is_safe_inside_base(string $abs): bool
    {
        $base = self::base_admin();
        if ($base === '' || $abs === '') {
            return false;
        }
        $real = realpath($abs);
        return $real ? self::is_inside_base($real, $base) : false;
    }

    /** Log dev warning chuẩn WP */
    private static function doing_it_wrong(string $function, string $message): void
    {
        if (function_exists('_doing_it_wrong')) {
            _doing_it_wrong(__CLASS__ . '::' . $function, $message, '1.0.0');
        } else {
            // Fallback: error_log
            error_log('[TMT CRM][View] ' . $function . ': ' . $message);
        }
    }
}
