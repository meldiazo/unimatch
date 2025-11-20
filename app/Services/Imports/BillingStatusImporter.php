<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;
use App\Models\PaymentVoucher;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BillingStatusImporter
{
    public function handle(UploadedFile $file, User $user): array
    {
        $disk = Storage::disk('local');
        if (! $disk->exists('imports')) {
            $disk->makeDirectory('imports');
        }

        $filename = Str::uuid().'.csv';
        $disk->putFileAs('imports', $file, $filename);
        $storedPath = 'imports/'.$filename;

        return DB::transaction(function () use ($file, $storedPath, $user, $disk) {
            $importBatch = ImportBatch::create([
                'import_type' => 'facturacion_estado',
                'source_name' => $file->getClientOriginalName(),
                'uploaded_by' => $user->id,
                'status' => 'processing',
            ]);

            $rows = $this->parseCsv($disk->path($storedPath));
            $updated = 0;

            foreach ($rows as $row) {
                $operationNumber = $row['operation_number'] ?? null;
                if (! $operationNumber) {
                    continue;
                }

                $voucher = PaymentVoucher::where('operation_number', trim($operationNumber))->first();
                if (! $voucher) {
                    continue;
                }

                $status = strtolower($row['billing_status'] ?? 'facturado');
                if (! in_array($status, ['facturado', 'pendiente', 'rechazado'], true)) {
                    $status = 'pendiente';
                }

                $voucher->update([
                    'billing_status' => $status,
                    'billed_at' => $this->parseDate($row['billed_at'] ?? null) ?? now(),
                    'billed_by' => $user->id,
                ]);

                $updated++;
            }

            $importBatch->update([
                'status' => 'completed',
                'summary_data' => [
                    'lines' => $updated,
                ],
            ]);

            return [
                'message' => "Estado de facturación actualizado para {$updated} registros.",
                'summary' => [
                    'lines' => $updated,
                ],
            ];
        });
    }

    private function parseCsv(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $handle = fopen($path, 'r');
        if (! $handle) {
            return [];
        }

        $rows = [];
        $headers = null;
        while (($data = fgetcsv($handle, 0, ',', '"')) !== false) {
            if ($headers === null) {
                $headers = array_map(fn ($header) => Str::of($header)->trim()->snake()->value(), $data);
                continue;
            }

            if (! $headers) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = $data[$index] ?? null;
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
