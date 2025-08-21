<?php

namespace TMT\CRM\Infrastructure\Security;

final class Capability
{
    public const MANAGE = 'manage_tmt_crm';   // xem menu, xem list
    public const CREATE = 'create_tmt_crm';   // tạo customer
    public const EDIT   = 'edit_tmt_crm';     // sửa customer
    public const DELETE = 'delete_tmt_crm';   // xoá customer

    /** Gom nhóm đầy đủ quyền cho role quản lý */
    public static function fullSet(): array
    {
        return [
            self::MANAGE => true,
            self::CREATE => true,
            self::EDIT   => true,
            self::DELETE => true,
        ];
    }

    /** Quyền cho nhân viên CRM (không được xoá) */
    public static function staffSet(): array
    {
        return [
            self::MANAGE => true,
            self::CREATE => true,
            self::EDIT   => true,
            self::DELETE => false,
        ];
    }
}
