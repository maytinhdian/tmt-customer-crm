<?php
declare(strict_types=1);
/**
 * LƯU Ý: Nếu bạn muốn tuân thủ chặt PSR-4 như quy ước "use TMT\CRM\Domain\Repositories\",
 * hãy DI CHUYỂN file interface này sang đường dẫn: src/Domain/Repositories/ (namespace TMT\CRM\Domain\Repositories).
 * Ở đây tạm thời để trong module cho thuận tiện triển khai.
 */
namespace TMT\CRM\Domain\Repositories;

use TMT\CRM\Core\Records\Application\DTO\ArchiveDTO;

interface ArchiveRepositoryInterface
{
    public function store_snapshot(ArchiveDTO $dto): int;
}
