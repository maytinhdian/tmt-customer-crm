<?php
// src/Domain/Entity/CompanyContact.php
namespace TMT\CRM\Domain\Entities;

final class CompanyContact
{
    private ?int $id;
    private int $company_id;
    private int $customer_id;
    private string $role;
    private bool $is_primary;
    private string $position;
    private bool $active;
    private string $note;
    private ?string $start_date;
    private ?string $end_date;
    private int $owner_id;

    public function __construct(
        ?int $id,
        int $company_id,
        int $customer_id,
        string $role,
        bool $is_primary,
        string $position,
        bool $active,
        string $note,
        ?string $start_date,
        ?string $end_date,
        int $owner_id
    ) {
        $this->id          = $id;
        $this->company_id  = $company_id;
        $this->customer_id = $customer_id;
        $this->role        = $role;
        $this->is_primary  = $is_primary;
        $this->position    = $position;
        $this->active      = $active;
        $this->note        = $note;
        $this->start_date  = $start_date;
        $this->end_date    = $end_date;
        $this->owner_id    = $owner_id;
    }

    public function id(): ?int
    {
        return $this->id;
    }
    public function company_id(): int
    {
        return $this->company_id;
    }
    public function customer_id(): int
    {
        return $this->customer_id;
    }
    public function role(): string
    {
        return $this->role;
    }
    public function is_primary(): bool
    {
        return $this->is_primary;
    }
    public function position(): string
    {
        return $this->position;
    }
    public function active(): bool
    {
        return $this->active;
    }
    public function note(): string
    {
        return $this->note;
    }
    public function start_date(): ?string
    {
        return $this->start_date;
    }
    public function end_date(): ?string
    {
        return $this->end_date;
    }
    public function owner_id(): int
    {
        return $this->owner_id;
    }

    public function mark_primary(): void
    {
        $this->is_primary = true;
    }

    public function unmark_primary(): void
    {
        $this->is_primary = false;
    }

    public function deactivate(?string $end_date = null): void
    {
        $this->active   = false;
        $this->end_date = $end_date ?? date('Y-m-d');
    }
}
