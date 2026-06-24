<?php

namespace App\Exports;

use App\Models\Service;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ServicesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected int $outletId;
    protected array $flowNames   = ['Cuci', 'Kering', 'Setrika', 'Lipat', 'Kemas'];
    protected array $flowPrefixes = ['cuci', 'kering', 'setrika', 'lipat', 'kemas'];

    public function __construct(int $outletId)
    {
        $this->outletId = $outletId;
    }

    public function query()
    {
        return Service::with(['category', 'satuan', 'flows.satuan'])
            ->where('outlet_id', $this->outletId)
            ->orderBy('name');
    }

    // Kolom IDENTIK dengan key yang dibaca ServicesImport -- jangan ubah urutan/nama tanpa sinkron keduanya
    public function headings(): array
    {
        $headings = ['service_code', 'name', 'category', 'satuan', 'price', 'duration_unit', 'duration', 'minimum_qty'];
        foreach ($this->flowPrefixes as $prefix) {
            $headings[] = "{$prefix}_komisi";
            $headings[] = "{$prefix}_satuan";
            $headings[] = "{$prefix}_aktif";
        }
        return $headings;
    }

    public function map($service): array
    {
        $row = [
            $service->service_code,
            $service->name,
            $service->category?->name,
            $service->satuan?->name,
            $service->price,
            $service->duration_unit,
            $service->duration,
            $service->minimum_qty,
        ];

        foreach ($this->flowNames as $flowName) {
            $flow = $service->flows->firstWhere('name', $flowName);
            $row[] = $flow?->commission ?? 0;
            $row[] = $flow?->satuan?->name ?? '';
            $row[] = ($flow?->is_active ?? false) ? 'Ya' : 'Tidak';
        }

        return $row;
    }
}