<?php
declare(strict_types=1);

namespace TMT\CRM\Core\ExportImport\Application\Services;

use TMT\CRM\Core\ExportImport\Application\DTO\ExportJobDTO;
use TMT\CRM\Domain\Repositories\ExportJobRepositoryInterface;
use TMT\CRM\Core\ExportImport\Infrastructure\IO\CsvWriter;
use TMT\CRM\Shared\Container\Container;

final class ExportService
{
    public function __construct(
        private ExportJobRepositoryInterface $job_repo,
        private Container $container,
    ) {}

    /** @param array $filters @param string[] $columns */
    public function start_export(string $entity_type, array $filters, array $columns, int $user_id): ExportJobDTO
    {
        $job = ExportJobDTO::new_pending($entity_type, $filters, $columns, $user_id, 'csv');
        $id = $this->job_repo->create($job);
        $job->id = $id;

        // MVP: thực thi ngay (không queue)
        $this->job_repo->update_status($id, 'running');
        try {
            $rows = $this->fetch_rows($entity_type, $filters, $columns);
            $cols = !empty($columns) ? $columns : array_keys((array)($rows[0] ?? []));
            $file_path = CsvWriter::write_temp($entity_type, $cols, $rows);
            $this->job_repo->update_status($id, 'done', $file_path);
        } catch (\Throwable $e) {
            $this->job_repo->update_status($id, 'failed', null, $e->getMessage());
        }
        return $this->job_repo->find_by_id($id) ?? $job;
    }

    /**
     * Lấy dữ liệu từ repo entity tương ứng (Company/Customer/Contact...)
     * TODO: thay bằng QueryService chung khi có.
     * @return array<int, array<string,mixed>>
     */
    private function fetch_rows(string $entity_type, array $filters, array $columns): array
    {
        // Ví dụ MVP: gọi Container để lấy repo phù hợp.
        $repo = match ($entity_type) {
            'company'  => $this->container->get('company_repository'),
            'customer' => $this->container->get('customer_repository'),
            'contact'  => $this->container->get('company_contact_repository'),
            default    => throw new \InvalidArgumentException('Unsupported entity_type: ' . $entity_type),
        };

        // Giả định: repo có phương thức search_for_export($filters, $columns): array
        return (array) $repo->search_for_export($filters, $columns);
    }
}
