<?php
declare(strict_types=1);

namespace TMT\CRM\Application\Services;

use TMT\CRM\Domain\Repositories\SequenceRepositoryInterface;

final class NumberingService {
    public function __construct(private SequenceRepositoryInterface $seqs) {}

    public function next_code(string $type): string {
        $yyyymm = (new \DateTimeImmutable('now'))->format('Ym');
        $no = $this->seqs->increment($type, $yyyymm);
        $prefix = ['quote'=>'QUO','order'=>'SO','invoice'=>'INV','payment'=>'PAY'][$type] ?? strtoupper($type);
        return sprintf('%s-%s-%04d', $prefix, $yyyymm, $no);
    }
}
