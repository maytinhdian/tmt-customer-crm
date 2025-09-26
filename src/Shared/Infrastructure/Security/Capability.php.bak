<?php

declare(strict_types=1);

namespace TMT\CRM\Shared\Infrastructure\Security;

/**
 * Khai báo tập capability theo Resource + Action (+ Scope khi cần).
 * Dùng hằng và hàm trợ giúp để module con có thể tái sử dụng.
 */
final class Capability
{
    // Resource: Customer
    public const CUSTOMER_READ         = 'tmt_crm_customer_read';
    public const CUSTOMER_CREATE       = 'tmt_crm_customer_create';
    public const CUSTOMER_UPDATE       = 'tmt_crm_customer_update';       // logic cap (được map sang own/any)
    public const CUSTOMER_DELETE       = 'tmt_crm_customer_delete';       // logic cap (được map sang own/any)
    public const CUSTOMER_UPDATE_OWN   = 'tmt_crm_customer_update_own';
    public const CUSTOMER_UPDATE_ANY   = 'tmt_crm_customer_update_any';
    public const CUSTOMER_DELETE_OWN   = 'tmt_crm_customer_delete_own';
    public const CUSTOMER_DELETE_ANY   = 'tmt_crm_customer_delete_any';
    public const CUSTOMER_ASSIGN       = 'tmt_crm_customer_assign';
    public const CUSTOMER_EXPORT       = 'tmt_crm_customer_export';

    // Resource: Company
    public const COMPANY_READ          = 'tmt_crm_company_read';
    public const COMPANY_CREATE        = 'tmt_crm_company_create';
    public const COMPANY_UPDATE        = 'tmt_crm_company_update';
    public const COMPANY_DELETE        = 'tmt_crm_company_delete';

    //Resource: Quote
    public const QUOTE_READ   = 'tmt_crm_quotes_read';
    public const QUOTE_CREATE = 'tmt_crm_quotes_create';
    public const QUOTE_UPDATE = 'tmt_crm_quotes_update';
    public const QUOTE_DELETE = 'tmt_crm_quotes_delete';


    // Settings / Admin
    public const SETTINGS_MANAGE       = 'tmt_crm_settings_manage';

    /** Gói full cho Manager */
    public static function pack_manager(): array
    {
        return [
            // Customer
            self::CUSTOMER_READ       => true,
            self::CUSTOMER_CREATE     => true,
            self::CUSTOMER_UPDATE     => true,
            self::CUSTOMER_DELETE     => true,
            self::CUSTOMER_UPDATE_OWN => true,
            self::CUSTOMER_UPDATE_ANY => true,
            self::CUSTOMER_DELETE_OWN => true,
            self::CUSTOMER_DELETE_ANY => true,
            self::CUSTOMER_ASSIGN     => true,
            self::CUSTOMER_EXPORT     => true,

            // Company
            self::COMPANY_READ        => true,
            self::COMPANY_CREATE      => true,
            self::COMPANY_UPDATE      => true,
            self::COMPANY_DELETE      => true,

            //Resource: Quote
            self::QUOTE_READ   => true,
            self::QUOTE_CREATE => true,
            self::QUOTE_UPDATE => true,
            self::QUOTE_DELETE => true,


            // Settings
            self::SETTINGS_MANAGE     => true,
        ];
    }

    /** Gói cho Staff (không xoá “any”, cho phép sửa “own”) */
    public static function pack_staff(): array
    {
        return [
            self::CUSTOMER_READ       => true,
            self::CUSTOMER_CREATE     => true,
            self::CUSTOMER_UPDATE     => true,    // sẽ map sang *_own
            self::CUSTOMER_DELETE     => false,   // không cho xóa logic → map sẽ fail
            self::CUSTOMER_UPDATE_OWN => true,
            self::CUSTOMER_UPDATE_ANY => false,
            self::CUSTOMER_DELETE_OWN => false,
            self::CUSTOMER_DELETE_ANY => false,
            self::CUSTOMER_ASSIGN     => false,
            self::CUSTOMER_EXPORT     => false,

            self::COMPANY_READ        => true,
            self::COMPANY_CREATE      => false,
            self::COMPANY_UPDATE      => false,
            self::COMPANY_DELETE      => false,

            //Resource: Quote
            self::QUOTE_READ   => true,
            self::QUOTE_CREATE => true,
            self::QUOTE_UPDATE => true,
            self::QUOTE_DELETE => false,

            self::SETTINGS_MANAGE     => false,
        ];
    }

    /** Gói chỉ xem */
    public static function pack_viewer(): array
    {
        return [
            self::CUSTOMER_READ   => true,
            self::COMPANY_READ    => true,
            self::QUOTE_READ => true,
        ];
    }
}
