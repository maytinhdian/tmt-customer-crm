<?php
// src/Shared/status.php
namespace TMT\CRM\Shared;

final class Status {
    // Quotation
    public const QUOTATION_DRAFT    = 'draft';
    public const QUOTATION_SENT     = 'sent';
    public const QUOTATION_ACCEPTED = 'accepted';
    public const QUOTATION_REJECTED = 'rejected';

    // Invoice
    public const INVOICE_UNPAID  = 'unpaid';
    public const INVOICE_PARTIAL = 'partial';
    public const INVOICE_PAID    = 'paid';
}