<?php

namespace App\Imports;

use App\Models\ServiceFlow;
use App\Models\Satuan;
use App\Models\OutletServiceCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class ServicesImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    use SkipsFailures;

    protected int $outletId;
    protected int $importedCount = 0;
    protected int $updatedCount = 0;
    protected array $skippedRows = [];

    protected array $flowPrefixes = [
        'Cuci'    => 'cuci',
        'Kering'  => 'kering',
        'Setrika' => 'setrika',
        'Lipat'   => 'lipat',
        'Kemas'   => 'kemas',
    ];

    public function __construct(int $outletId)
    {
        $this->outletId = $outletId;
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) return;

        // 1) Preload semua satuan
        $satuanMap = Satuan::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id])
            ->toArray();

        // 2) Validasi satuan utama & per-flow
        $validRows = [];
        foreach ($rows as $data) {
            $satuanKey = strtolower(trim($data['satuan'] ?? ''));
            $satuanId  = $satuanMap[$satuanKey] ?? null;

            if (!$satuanId) {
                $this->skippedRows[] = [
                    'row'    => $data->toArray(),
                    'reason' => "Satuan '{$data['satuan']}' tidak ditemukan di master satuan",
                ];
                continue;
            }

            $flowSatuanIds = [];
            $rowFailed = false;

            foreach ($this->flowPrefixes as $flowName => $prefix) {
                $flowSatuanName = $data["{$prefix}_satuan"] ?? null;

                if (!empty($flowSatuanName)) {
                    $key = strtolower(trim($flowSatuanName));
                    if (!isset($satuanMap[$key])) {
                        $this->skippedRows[] = [
                            'row'    => $data->toArray(),
                            'reason' => "Satuan flow {$flowName} ('{$flowSatuanName}') tidak ditemukan",
                        ];
                        $rowFailed = true;
                        break;
                    }
                    $flowSatuanIds[$flowName] = $satuanMap[$key];
                } else {
                    $flowSatuanIds[$flowName] = $satuanId;
                }
            }

            if ($rowFailed) continue;

            $validRows[] = [
                'data'           => $data,
                'satuan_id'      => $satuanId,
                'flow_satuan_id' => $flowSatuanIds,
                'service_code'   => trim($data['service_code'] ?? ''),
            ];
        }

        if (empty($validRows)) return;

        // 3) Resolusi kategori secara batch (auto-create yang belum ada)
        $categoryNames = collect($validRows)
            ->map(fn ($r) => trim($r['data']['category']))
            ->unique()
            ->values();

        $existingCategories = OutletServiceCategory::where('outlet_id', $this->outletId)
            ->whereIn('name', $categoryNames)
            ->pluck('id', 'name')
            ->toArray();

        $missingNames = $categoryNames->reject(fn ($name) => isset($existingCategories[$name]))->values();

        if ($missingNames->isNotEmpty()) {
            $now = now();
            OutletServiceCategory::insert(
                $missingNames->map(fn ($name) => [
                    'outlet_id'  => $this->outletId,
                    'name'       => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->toArray()
            );

            $existingCategories = OutletServiceCategory::where('outlet_id', $this->outletId)
                ->whereIn('name', $categoryNames)
                ->pluck('id', 'name')
                ->toArray();
        }

        // 4) Cek service_code mana yang sudah ada (utk tentukan update vs insert)
        $codesProvided = collect($validRows)
            ->pluck('service_code')
            ->filter()
            ->unique()
            ->values();

        $existingServiceIds = $codesProvided->isEmpty()
            ? []
            : DB::table('services')
                ->where('outlet_id', $this->outletId)
                ->whereIn('service_code', $codesProvided)
                ->pluck('id', 'service_code')
                ->toArray();

        // 5) Proses insert/update + kumpulkan flow utk bulk insert di akhir
        $allFlowsData  = [];
        $updatedServiceIds = [];

        DB::transaction(function () use ($validRows, $existingCategories, $existingServiceIds, &$allFlowsData, &$updatedServiceIds) {
            foreach ($validRows as $row) {
                $data       = $row['data'];
                $categoryId = $existingCategories[trim($data['category'])];
                $code       = $row['service_code'];
                $existingId = $code !== '' ? ($existingServiceIds[$code] ?? null) : null;

                $payload = [
                    'name'                        => $data['name'],
                    'outlet_service_category_id' => $categoryId,
                    'satuan_id'                   => $row['satuan_id'],
                    'price'                       => $data['price'] ?? 0,
                    'duration_unit'               => $data['duration_unit'] ?? 'Hari',
                    'duration'                    => $data['duration'] ?? 1,
                    'minimum_qty'                 => $data['minimum_qty'] ?? 1,
                    'updated_at'                  => now(),
                ];

                if ($existingId) {
                    // UPDATE — data sudah ada (cocok service_code)
                    DB::table('services')->where('id', $existingId)->update($payload);
                    $serviceId = $existingId;
                    $updatedServiceIds[] = $serviceId;
                    $this->updatedCount++;
                } else {
                    // INSERT — data baru, generate service_code baru
                    $serviceId = DB::table('services')->insertGetId(array_merge($payload, [
                        'outlet_id'    => $this->outletId,
                        'service_code' => 'LND-' . now()->timestamp . rand(100, 999),
                        'created_at'   => now(),
                    ]));
                    $this->importedCount++;
                }

                $sequence = 1;
                foreach ($this->flowPrefixes as $flowName => $prefix) {
                    $komisi   = $data["{$prefix}_komisi"] ?? 0;
                    $aktifRaw = strtolower((string) ($data["{$prefix}_aktif"] ?? ''));
                    $isActive = in_array($aktifRaw, ['1', 'ya', 'yes', 'true'], true);

                    $allFlowsData[] = [
                        'service_id' => $serviceId,
                        'name'       => $flowName,
                        'sequence'   => $isActive ? $sequence++ : 0,
                        'is_active'  => $isActive,
                        'satuan_id'  => $row['flow_satuan_id'][$flowName],
                        'commission' => $komisi,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Hapus flow lama utk service yang di-UPDATE (akan diganti dgn yg baru di bawah)
            if (!empty($updatedServiceIds)) {
                ServiceFlow::whereIn('service_id', $updatedServiceIds)->delete();
            }

            // Bulk insert SEMUA flow (baik dari insert baru maupun pengganti flow yang di-update)
            foreach (array_chunk($allFlowsData, 500) as $chunk) {
                ServiceFlow::insert($chunk);
            }
        });
    }

    public function rules(): array
    {
        return [
            'service_code' => 'nullable|string|max:50',
            'name'         => 'required|string|max:255',
            'category'     => 'required|string|max:255',
            'satuan'       => 'required|string|max:255',
            'price'        => 'required|numeric|min:0',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'name.required'     => 'Nama layanan wajib diisi',
            'category.required' => 'Kategori wajib diisi',
            'satuan.required'   => 'Satuan wajib diisi',
            'price.required'    => 'Harga wajib diisi',
        ];
    }

    public function isEmptyWhen(array $row): bool
    {
        return empty(trim($row['name'] ?? '')) &&
            empty(trim($row['category'] ?? '')) &&
            empty(trim($row['satuan'] ?? ''));
    }

    public function getImportedCount(): int { return $this->importedCount; }
    public function getUpdatedCount(): int { return $this->updatedCount; }
    public function getSkippedRows(): array { return $this->skippedRows; }
}