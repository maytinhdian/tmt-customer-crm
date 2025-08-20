<?php
namespace TMT\CRM\Application\Services;

use TMT\CRM\Domain\Entities\Quotation;
use TMT\CRM\Domain\Repositories\QuotationRepositoryInterface;

final class QuotationService {
    const STATUS_DRAFT    = 'draft';
    const STATUS_SENT     = 'sent';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';

    public function __construct(private QuotationRepositoryInterface $repo) {}

    public function create(int $customer_id, float $total): int {
        $quotation = new Quotation(null, $customer_id, $total, self::STATUS_DRAFT);
        return $this->repo->create($quotation);
    }

    public function mark_sent(int $id): bool {
        return $this->repo->update_status($id, self::STATUS_SENT);
    }

    public function mark_accepted(int $id): bool {
        return $this->repo->update_status($id, self::STATUS_ACCEPTED);
    }

    public function mark_rejected(int $id): bool {
        return $this->repo->update_status($id, self::STATUS_REJECTED);
    }
}