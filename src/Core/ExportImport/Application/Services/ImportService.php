<?php
declare(strict_types=1);

namespace TMT\CRM\Core\ExportImport\Application\Services;

use TMT\CRM\Core\ExportImport\Application\DTO\ImportJobDTO;
use TMT\CRM\Domain\Repositories\ImportJobRepositoryInterface;
use TMT\CRM\Core\ExportImport\Infrastructure\IO\CsvReader;
use TMT\CRM\Shared\Container\Container;

final class ImportService
{
    public function __construct(
        private ImportJobRepositoryInterface $job_repo,
        private ValidationService $validator,
    ) {}

    public function create_job(string $entity_type, string $uploaded_path, bool $has_header, int $user_id): ImportJobDTO
    {
        $dto = new ImportJobDTO(
            id: null,
            entity_type: $entity_type,
            source_file: $uploaded_path,
            format: 'csv',
            mapping: [],
            has_header: $has_header,
            status: 'pending',
            created_by: $user_id,
            created_at: gmdate('Y-m-d H:i:s'),
        );
        $dto->id = $this->job_repo->create($dto);
        return $dto;
    }

    /** @return array{columns:string[], sample_rows:array<int,array<string,mixed>>, total:int} */
    public function preview(ImportJobDTO $job, array $mapping): array
    {
        $reader = CsvReader::from_file($job->source_file, $job->has_header);
        $columns = $reader->get_columns();
        $samples = iterator_to_array($reader->read_rows(10));
        $total   = $reader->count_rows();

        $job->mapping = $mapping;
        $job->status  = 'previewed';
        $this->job_repo->update($job);

        return [
            'columns' => $columns,
            'sample_rows' => $samples,
            'total' => $total,
        ];
    }

    public function commit(ImportJobDTO $job): ImportJobDTO
    {
        $this->job_repo->update_status($job->id ?? 0, 'running');
        $reader = CsvReader::from_file($job->source_file, $job->has_header);

        $total = $ok = $err = 0;
        foreach ($reader->read_rows() as $row) {
            $total++;
            $data = $this->apply_mapping($row, $job->mapping);
            if (!$this->validator->validate_row($job->entity_type, $data)) {
                $err++;
                continue;
            }
            try {
                $this->persist_row($job->entity_type, $data);
                $ok++;
            } catch (\Throwable) {
                $err++;
            }
            if (($total % 50) === 0) {
                $this->job_repo->update_counters($job->id ?? 0, $total, $ok, $err);
            }
        }

        $job->total_rows = $total;
        $job->success_rows = $ok;
        $job->error_rows = $err;
        $job->status = 'done';
        $this->job_repo->update($job);
        return $job;
    }

    public function find_job_by_id(int $id): ?ImportJobDTO
    {
        return $this->job_repo->find_by_id($id);
    }

    private function apply_mapping(array $row, array $mapping): array
    {
        $out = [];
        foreach ($mapping as $source => $target) {
            $out[$target] = $row[$source] ?? null;
        }
        return $out;
    }

    private function persist_row(string $entity_type, array $data): void
    {
        $service = match ($entity_type) {
            'company'  => Container::get('company_service'),
            'customer' => Container::get('customer_service'),
            'contact'  => Container::get('company_contact_service'),
            default    => throw new \InvalidArgumentException('Unsupported entity_type: ' . $entity_type),
        };
        // Giả định service có upsert_from_array()
        $service->upsert_from_array($data);
    }
}
