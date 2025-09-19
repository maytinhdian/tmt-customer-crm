<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Domain;

final class EventKeys
{
    public const COMPANY_CREATED = 'CompanyCreated';
    public const COMPANY_SOFT_DELETED = 'CompanySoftDeleted';
    public const QUOTE_SENT = 'QuoteSent';
}
