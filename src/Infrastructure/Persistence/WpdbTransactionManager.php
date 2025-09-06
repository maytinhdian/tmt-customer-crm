<?php

declare(strict_types=1);

namespace TMT\CRM\Infrastructure\Persistence;

use TMT\CRM\Domain\Repositories\TransactionManagerInterface;

final class WpdbTransactionManager implements TransactionManagerInterface
{
    public function __construct(private \wpdb $db) {}
    public function begin(): void
    {
        $this->db->query('START TRANSACTION');
    }
    public function commit(): void
    {
        $this->db->query('COMMIT');
    }
    public function rollback(): void
    {
        $this->db->query('ROLLBACK');
    }
}
