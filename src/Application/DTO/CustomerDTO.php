<?php
namespace TMT\CRM\Application\DTO;
final class CustomerDTO
{
    public ?int $id;
    public string $name;
    public ?string $email;
    public ?string $phone;
    public ?string $company;
    public ?string $address;
    public ?string $note;
    public ?string $type;        // 'individual' | 'company' | 'partner' ...
    public ?int $owner_id;       // user phụ trách
    public ?string $created_at;
    public ?string $updated_at;

    public function __construct(
        ?int $id,
        string $name,
        ?string $email = null,
        ?string $phone = null,
        ?string $company = null,
        ?string $address = null,
        ?string $note = null,
        ?string $type = null,
        ?int $owner_id = null,
        ?string $created_at = null,
        ?string $updated_at = null
    ) {
        $this->id         = $id;
        $this->name       = $name;
        $this->email      = $email;
        $this->phone      = $phone;
        $this->company    = $company;
        $this->address    = $address;
        $this->note       = $note;
        $this->type       = $type;
        $this->owner_id   = $owner_id;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }
}
