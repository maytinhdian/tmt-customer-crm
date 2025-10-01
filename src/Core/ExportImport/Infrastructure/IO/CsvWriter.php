<?php
declare(strict_types=1); 

namespace TMT\CRM\Core\ExportImport\Infrastructure\IO;

final class CsvWriter
{
    /** @param string[] $columns @param iterable<int, array<string,mixed>> $rows */
    public static function write_temp(string $entity_type, array $columns, iterable $rows): string
    {
        $upload_dir = wp_upload_dir();
        $dir = trailingslashit($upload_dir['basedir']) . 'tmt-crm-exports/';
        if (!file_exists($dir)) { wp_mkdir_p($dir); }
        $path = $dir . sprintf('%s_%s.csv', $entity_type, gmdate('Ymd_His'));

        $fh = fopen($path, 'w');
        fputcsv($fh, $columns);
        foreach ($rows as $row) {
            $line = [];
            foreach ($columns as $c) { $line[] = $row[$c] ?? ''; }
            fputcsv($fh, $line);
        }
        fclose($fh);
        return $path;
    }
}
