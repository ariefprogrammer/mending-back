<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CustomersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected int $outletId;
    protected ?string $type;

    public function __construct(int $outletId, ?string $type = null)
    {
        $this->outletId = $outletId;
        $this->type     = $type;
    }

    public function query()
    {
        $query = Customer::where('outlet_id', $this->outletId);

        if ($this->type) {
            $query->where('customer_type', $this->type);
        }

        return $query->orderBy('name');
    }

    // Kolom IDENTIK dengan key yang dibaca CustomersImport -- jangan ubah tanpa sinkron keduanya
    public function headings(): array
    {
        return ['id', 'name', 'phone', 'email', 'address', 'customer_type', 'url_address', 'balance'];
    }

    public function map($customer): array
    {
        return [
            $customer->id,
            $customer->name,
            $customer->phone,
            $customer->email,
            $customer->address,
            $customer->customer_type,
            $customer->url_address,
            $customer->balance,
        ];
    }
}