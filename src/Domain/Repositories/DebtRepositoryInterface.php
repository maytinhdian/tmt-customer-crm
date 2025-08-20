<?php
// src/Domain/Repositories/debt-repository-interface.php
namespace TMT\CRM\Domain\Repositories;

interface DebtRepositoryInterface {
    public function mark_paid(int $debt_id): bool;
}