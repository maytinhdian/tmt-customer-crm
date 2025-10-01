<?php
declare(strict_types=1);

namespace TMT\CRM\Core\ExportImport\Infrastructure\IO;

final class CsvReader
{
    private function __construct(
        private \SplFileObject $file,
        private bool $has_header,
        private array $header = [],
    ) {}

    public static function from_file(string $path, bool $has_header): self
    {
        $f = new \SplFileObject($path, 'r');
        $f->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
        $self = new self($f, $has_header);
        if ($has_header && !$f->eof()) {
            $self->header = array_map('trim', (array) $f->fgetcsv());
        }
        return $self;
    }

    /** @return string[] */
    public function get_columns(): array
    {
        return $this->has_header ? $this->header : [];
    }

    /** @return \Generator<int, array<string,mixed>> */
    public function read_rows(int $limit = 0): \Generator
    {
        $count = 0;
        while (!$this->file->eof()) {
            $row = $this->file->fgetcsv();
            if ($row === [null] || $row === false) { continue; }
            $row = array_map('trim', (array) $row);
            yield $this->has_header ? array_combine($this->header, $row) : $row;
            if ($limit > 0 && (++$count) >= $limit) { break; }
        }
    }

    public function count_rows(): int
    {
        $pos = $this->file->ftell();
        $this->file->rewind();
        if ($this->has_header) { $this->file->fgetcsv(); }
        $n = 0; while (!$this->file->eof()) { $row = $this->file->fgetcsv(); if ($row !== [null] && $row !== false) { $n++; } }
        $this->file->fseek($pos);
        return $n;
    }
}
