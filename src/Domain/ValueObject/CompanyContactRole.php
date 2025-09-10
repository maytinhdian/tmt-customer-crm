<?php

declare(strict_types=1);

namespace TMT\CRM\Domain\ValueObject;

/**
 * Giá trị chuẩn cho vai trò liên hệ công ty
 * - Đảm bảo đồng nhất khi insert/select DB
 * - Giúp IDE auto-complete & tránh sai chính tả
 */
final class CompanyContactRole
{
    public const ACCOUNTING        = 'accounting';         // Kế toán
    public const PURCHASING        = 'purchasing';         // Thu mua
    public const INVOICE_RECIPIENT = 'invoice_recipient';  // Người nhận HĐ
    public const DECISION_MAKER    = 'decision_maker';     // Người quyết định
    public const OWNER             = 'owner';              // Chủ công ty
    public const OTHER             = 'other';              // Khác

    /** Trả về toàn bộ role hợp lệ */
    public static function all(): array
    {
        return [
            self::ACCOUNTING,
            self::PURCHASING,
            self::INVOICE_RECIPIENT,
            self::DECISION_MAKER,
            self::OWNER,
            self::OTHER,
        ];
    }
    /** Map value => label (có i18n) để hiển thị */
    public static function labels(): array
    {
        return [
            self::ACCOUNTING        => __('Kế toán', 'tmt-crm'),
            self::PURCHASING        => __('Thu mua', 'tmt-crm'),
            self::INVOICE_RECIPIENT => __('Người nhận HĐ', 'tmt-crm'),
            self::DECISION_MAKER    => __('Người quyết định', 'tmt-crm'),
            self::OWNER             => __('Chủ sở hữu', 'tmt-crm'),
            self::OTHER             => __('Khác', 'tmt-crm'),
        ];
    }

    /** Lấy label cho 1 role cụ thể (fallback về chính value nếu chưa map) */
    public static function label(string $role): string
    {
        $labels = self::labels();
        return $labels[$role] ?? $role;
    }
    /** Kiểm tra role có hợp lệ không */
    public static function is_valid(string $role): bool
    {
        return in_array($role, self::all(), true);
    }
}
