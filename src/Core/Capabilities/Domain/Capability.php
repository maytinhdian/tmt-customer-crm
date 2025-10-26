<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Capabilities\Domain;

/**
 * Danh sách capability chuẩn của TMT CRM.
 * Gợi ý: tách theo module nghiệp vụ.
 */
final class Capability
{
    // Company
    public const COMPANY_READ   = 'tmt_crm_company_read';
    public const COMPANY_CREATE = 'tmt_crm_company_create';
    public const COMPANY_UPDATE = 'tmt_crm_company_update';
    public const COMPANY_DELETE = 'tmt_crm_company_delete'; // xoá mềm

    // Password
    public const PASSWORD_READ    = 'tmt_crm_password_read';
    public const PASSWORD_CREATE  = 'tmt_crm_password_create';
    public const PASSWORD_UPDATE  = 'tmt_crm_password_update';
    public const PASSWORD_DELETE  = 'tmt_crm_password_delete';
    public const PASSWORD_RESTORE = 'tmt_crm_password_restore';
    public const PASSWORD_REVEAL  = 'tmt_crm_password_reveal'; // hành động nhạy cảm

    //License 
    public const LICENSE_READ       = 'tmt_crm_license_read';
    public const LICENSE_CREATE     = 'tmt_crm_license_create';
    public const LICENSE_UPDATE     = 'tmt_crm_license_update';
    public const LICENSE_DELETE     = 'tmt_crm_license_delete';
    public const LICENSE_PURGE      = 'tmt_crm_license_purge';
    public const LICENSE_RESTORE    = 'tmt_crm_license_restore';
    public const LICENSE_REVEAL     = 'tmt_crm_license_reveal';

    // Customer
    public const CUSTOMER_READ   = 'tmt_crm_customer_read';
    public const CUSTOMER_CREATE = 'tmt_crm_customer_create';
    public const CUSTOMER_UPDATE = 'tmt_crm_customer_update';
    public const CUSTOMER_UPDATE_ANY = 'tmt_crm_customer_update';
    public const CUSTOMER_DELETE = 'tmt_crm_customer_delete';

    // Company Contact
    public const COMPANY_CONTACT_READ   = 'tmt_crm_company_contact_read';
    public const COMPANY_CONTACT_CREATE = 'tmt_crm_company_contact_create';
    public const COMPANY_CONTACT_UPDATE = 'tmt_crm_company_contact_update';
    public const COMPANY_CONTACT_DELETE = 'tmt_crm_company_contact_delete';

    // Notes
    public const NOTE_READ   = 'tmt_crm_note_read';
    public const NOTE_CREATE = 'tmt_crm_note_create';
    public const NOTE_UPDATE = 'tmt_crm_note_update';
    public const NOTE_DELETE = 'tmt_crm_note_delete';

    //Core Files
    public const FILE_READ   = 'tmt_crm_file_read';
    public const FILE_ATTACH = 'tmt_crm_file_attach';
    public const FILE_DELETE = 'tmt_crm_file_delete';
    public const FILE_CREATE = 'tmt_crm_file_create';

    // Quotes / Orders / Invoices / Payments
    public const QUOTE_READ   = 'tmt_crm_quote_read';
    public const QUOTE_CREATE = 'tmt_crm_quote_create';
    public const QUOTE_UPDATE = 'tmt_crm_quote_update';
    public const QUOTE_DELETE = 'tmt_crm_quote_delete';

    public const ORDER_READ   = 'tmt_crm_order_read';
    public const ORDER_CREATE = 'tmt_crm_order_create';
    public const ORDER_UPDATE = 'tmt_crm_order_update';
    public const ORDER_DELETE = 'tmt_crm_order_delete';

    public const INVOICE_READ   = 'tmt_crm_invoice_read';
    public const INVOICE_CREATE = 'tmt_crm_invoice_create';
    public const INVOICE_UPDATE = 'tmt_crm_invoice_update';
    public const INVOICE_DELETE = 'tmt_crm_invoice_delete';

    public const PAYMENT_READ   = 'tmt_crm_payment_read';
    public const PAYMENT_CREATE = 'tmt_crm_payment_create';
    public const PAYMENT_UPDATE = 'tmt_crm_payment_update';
    public const PAYMENT_DELETE = 'tmt_crm_payment_delete';

    // Admin & Records
    public const RECORDS_PURGE        = 'tmt_crm_records_purge';       // xoá vĩnh viễn
    public const SETTINGS_MANAGE      = 'tmt_crm_settings_manage';     // vào trang cài đặt CRM
    public const CAPABILITIES_MANAGE  = 'tmt_crm_capabilities_manage'; // quản trị ma trận quyền



    /** Trả về tất cả capabilities (mảng phẳng). */
    public static function all(): array
    {
        return [
            // Company
            self::COMPANY_READ,
            self::COMPANY_CREATE,
            self::COMPANY_UPDATE,
            self::COMPANY_DELETE,
            // Customer
            self::CUSTOMER_READ,
            self::CUSTOMER_CREATE,
            self::CUSTOMER_UPDATE,
            self::CUSTOMER_DELETE,
            // Company Contact
            self::COMPANY_CONTACT_READ,
            self::COMPANY_CONTACT_CREATE,
            self::COMPANY_CONTACT_UPDATE,
            self::COMPANY_CONTACT_DELETE,
            // Notes 
            self::NOTE_READ,
            self::NOTE_CREATE,
            self::NOTE_UPDATE,
            self::NOTE_DELETE,
            // Files
            self::FILE_CREATE,
            self::FILE_READ,
            self::FILE_ATTACH,
            self::FILE_DELETE,
            //Password 
            self::PASSWORD_CREATE,
            self::PASSWORD_READ,
            self::PASSWORD_RESTORE,
            self::PASSWORD_DELETE,
            self::PASSWORD_REVEAL,
            //License
            self::LICENSE_READ,
            self::LICENSE_CREATE,
            self::LICENSE_UPDATE,
            self::LICENSE_DELETE,
            self::LICENSE_PURGE,
            self::LICENSE_RESTORE,
            self::LICENSE_REVEAL,
            // Quotes/Orders/Invoices/Payments
            self::QUOTE_READ,
            self::QUOTE_CREATE,
            self::QUOTE_UPDATE,
            self::QUOTE_DELETE,
            self::ORDER_READ,
            self::ORDER_CREATE,
            self::ORDER_UPDATE,
            self::ORDER_DELETE,
            self::INVOICE_READ,
            self::INVOICE_CREATE,
            self::INVOICE_UPDATE,
            self::INVOICE_DELETE,
            self::PAYMENT_READ,
            self::PAYMENT_CREATE,
            self::PAYMENT_UPDATE,
            self::PAYMENT_DELETE,
            // Admin & Records
            self::RECORDS_PURGE,
            self::SETTINGS_MANAGE,
            self::CAPABILITIES_MANAGE,
        ];
    }

    /** Nhóm theo module để render UI đẹp hơn. */
    public static function grouped(): array
    {
        return [
            'Company' => [self::COMPANY_READ, self::COMPANY_CREATE, self::COMPANY_UPDATE, self::COMPANY_DELETE],
            'Customer' => [self::CUSTOMER_READ, self::CUSTOMER_CREATE, self::CUSTOMER_UPDATE, self::CUSTOMER_DELETE],
            'Company Contact' => [self::COMPANY_CONTACT_READ, self::COMPANY_CONTACT_CREATE, self::COMPANY_CONTACT_UPDATE, self::COMPANY_CONTACT_DELETE],
            'Notes' => [self::NOTE_READ, self::NOTE_CREATE, self::NOTE_UPDATE, self::NOTE_DELETE],
            'Files' => [self::FILE_CREATE, self::FILE_READ, self::FILE_ATTACH, self::FILE_DELETE],
            'Quote' => [self::QUOTE_READ, self::QUOTE_CREATE, self::QUOTE_UPDATE, self::QUOTE_DELETE],
            'Order' => [self::ORDER_READ, self::ORDER_CREATE, self::ORDER_UPDATE, self::ORDER_DELETE],
            'Invoice' => [self::INVOICE_READ, self::INVOICE_CREATE, self::INVOICE_UPDATE, self::INVOICE_DELETE],
            'Payment' => [self::PAYMENT_READ, self::PAYMENT_CREATE, self::PAYMENT_UPDATE, self::PAYMENT_DELETE],
            'Admin' => [self::SETTINGS_MANAGE, self::CAPABILITIES_MANAGE, self::RECORDS_PURGE],
            'Password' => [self::PASSWORD_CREATE, self::PASSWORD_READ, self::PASSWORD_RESTORE, self::PASSWORD_DELETE, self::PASSWORD_REVEAL],
            'License' => [self::LICENSE_READ, self::LICENSE_CREATE, self::LICENSE_UPDATE, self::LICENSE_DELETE, self::LICENSE_PURGE, self::LICENSE_RESTORE, self::LICENSE_REVEAL,]
        ];
    }
}
