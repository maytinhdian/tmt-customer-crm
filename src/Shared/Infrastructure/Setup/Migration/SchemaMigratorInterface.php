<?php
declare(strict_types=1);

namespace TMT\CRM\Shared\Infrastructure\Setup\Migration;

interface SchemaMigratorInterface
{
    public static function module_key(): string;
    public static function target_version(): string;
    public function install(): void;
    public function upgrade(string $from_version): void;
}
