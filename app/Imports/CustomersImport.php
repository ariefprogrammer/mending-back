<?php

namespace App\Imports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Row;

class CustomersImport implements OnEachRow, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    use SkipsFailures;

    protected int $outletId;
    protected int $importedCount = 0;
    protected int $updatedCount = 0;
    protected array $skippedDuplicates = [];

    public function __construct(int $outletId)
    {
        $this->outletId = $outletId;
    }

    public function onRow(Row $row)
    {
        $data = $row->toArray();

        $id    = trim((string) ($data['id'] ?? ''));
        $phone = $data['phone'] ?? null;
        $email = $data['email'] ?? null;

        // Cari record existing berdasarkan id (hanya dalam outlet yang sama)
        $existingCustomer = $id !== ''
            ? Customer::where('outlet_id', $this->outletId)->where('id', $id)->first()
            : null;

        // Cek duplikat phone/email -- kecualikan record yang sedang diupdate sendiri
        $duplicate = false;
        if (!empty($phone) || !empty($email)) {
            $dupQuery = Customer::where('outlet_id', $this->outletId)
                ->where(function ($q) use ($phone, $email) {
                    $q->where(function ($inner) use ($phone) {
                        if (!empty($phone)) {
                            $inner->where('phone', $phone);
                        }
                    });
                    if (!empty($email)) {
                        $q->orWhere('email', $email);
                    }
                });

            if ($existingCustomer) {
                $dupQuery->where('id', '!=', $existingCustomer->id);
            }

            $duplicate = $dupQuery->exists();
        }

        if ($duplicate) {
            $this->skippedDuplicates[] = [
                'row'    => $data,
                'reason' => 'Nomor HP atau email sudah terdaftar di outlet ini',
            ];
            return;
        }

        $payload = [
            'customer_type' => $data['customer_type'] ?? 'umum',
            'name'          => $data['name'],
            'phone'         => $phone,
            'email'         => $email,
            'address'       => $data['address'] ?? null,
            'url_address'   => $data['url_address'] ?? null,
            'balance'       => $data['balance'] ?? 0, // <-- diperbaiki, sebelumnya hardcode 0
        ];

        if ($existingCustomer) {
            $existingCustomer->update($payload);
            $this->updatedCount++;
        } else {
            Customer::create(array_merge($payload, [
                'outlet_id' => $this->outletId,
            ]));
            $this->importedCount++;
        }
    }

    public function rules(): array
    {
        return [
            'id'    => 'nullable|string',
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

    public function getImportedCount(): int { return $this->importedCount; }
    public function getUpdatedCount(): int { return $this->updatedCount; }
    public function getSkippedDuplicates(): array { return $this->skippedDuplicates; }

    public function isEmptyWhen(array $row): bool
    {
        return empty(trim($row['name'] ?? '')) &&
            empty(trim($row['phone'] ?? '')) &&
            empty(trim($row['email'] ?? ''));
    }
}