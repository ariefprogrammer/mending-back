<?php

namespace App\Imports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;

class CustomersImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    protected int $outletId;
    protected int $importedCount = 0;
    protected array $skippedDuplicates = [];

    public function __construct(int $outletId)
    {
        $this->outletId = $outletId;
    }

    public function model(array $row)
    {
        // Cek duplikat berdasarkan phone ATAU email di outlet yang sama
        $duplicate = Customer::where('outlet_id', $this->outletId)
            ->where(function ($q) use ($row) {
                if (!empty($row['phone'])) {
                    $q->orWhere('phone', $row['phone']);
                }
                if (!empty($row['email'])) {
                    $q->orWhere('email', $row['email']);
                }
            })
            ->exists();

        if ($duplicate) {
            $this->skippedDuplicates[] = [
                'row'    => $row,
                'reason' => 'Nomor HP atau email sudah terdaftar di outlet ini',
            ];
            return null; // tidak di-insert
        }

        $this->importedCount++;

        return new Customer([
            'outlet_id'     => $this->outletId,
            'customer_type' => $row['customer_type'] ?? 'umum',
            'name'          => $row['name'],
            'phone'         => $row['phone'] ?? null,
            'email'         => $row['email'] ?? null,
            'address'       => $row['address'] ?? null,
            'url_address'   => $row['url_address'] ?? null,
            'balance'       => $row['balance'] ?? 0,
        ]);
    }

    public function rules(): array
    {
        return [
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi',
            'email.email'   => 'Format email tidak valid',
        ];
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedDuplicates(): array
    {
        return $this->skippedDuplicates;
    }
}