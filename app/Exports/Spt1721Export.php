<?php

namespace App\Exports;

use App\Models\Spt1721;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class Spt1721Export implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        protected Spt1721 $spt,
        protected ?string $type = null
    ) {}

    public function sheets(): array
    {
        $sheets = [];

        if ($this->type === null || $this->type === 'induk') {
            $sheets[] = new Spt1721IndukSheet($this->spt);
        }

        if ($this->type === null || $this->type === 'lampiran_i') {
            $sheets[] = new Spt1721LampiranISheet($this->spt);
        }

        if ($this->type === null || $this->type === 'lampiran_ii') {
            $sheets[] = new Spt1721LampiranIISheet($this->spt);
        }

        return $sheets;
    }

    public function getFilename(): string
    {
        $suffix = $this->type ? "_{$this->type}" : '';

        return "SPT1721_{$this->spt->tax_year}{$suffix}.xlsx";
    }
}
