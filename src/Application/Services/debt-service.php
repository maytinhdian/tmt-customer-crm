<?php
namespace TMT\CRM\Application\Services;

use TMT\CRM\Domain\Entities\Debt;
use TMT\CRM\Domain\Repositories\Debt_Repository_Interface;

final class Debt_Service {
    public function __construct(private Debt_Repository_Interface $repo) {}

    public function create(int $invoice_id, float $amount, string $due_date): int {
        $debt = new Debt(null, $invoice_id, $amount, $due_date, false);
        return $this->repo->create($debt);
    }

    public function mark_paid(int $id): bool {
        return $this->repo->update_status($id, true);
    }
}