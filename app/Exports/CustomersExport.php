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

    public function headings(): array
    {
        return ['Nama', 'No. WhatsApp', 'Email', 'Alamat', 'Tipe Pelanggan', 'Link Lokasi', 'Saldo', 'Tanggal Dibuat'];
    }

    public function map($customer): array
    {
        return [
            $customer->name,
            $customer->phone,
            $customer->email,
            $customer->address,
            $customer->customer_type,
            $customer->url_address,
            $customer->balance,
            $customer->created_at?->format('d-m-Y H:i'),
        ];
    }
}