<?php

namespace App\Services\Imports;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\ImportBatch;
use App\Models\PaymentVoucher;
use App\Models\Student;
use App\Models\User;
use App\Models\VoucherBatch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoucherImporter
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
                'import_type' => 'vouchers',
                'source_name' => $file->getClientOriginalName(),
                'uploaded_by' => $user->id,
                'status' => 'processing',
            ]);

            $voucherBatch = VoucherBatch::create([
                'import_batch_id' => $importBatch->id,
            ]);

            $rows = $this->parseCsv($disk->path($storedPath));
            $mapping = [
                'student_code' => 'student_code',
                'bank_code' => 'bank_code',
                'account_number' => 'account_number',
                'operation_number' => 'operation_number',
                'amount' => 'amount',
                'currency' => 'currency',
                'paid_at' => 'paid_at',
                'status' => 'status',
                'payment_type' => 'payment_type',
            ];

            $inserted = 0;
            foreach ($rows as $row) {
                $amountRaw = $row[$mapping['amount']] ?? null;
                $operation = $row[$mapping['operation_number']] ?? null;

                if ($amountRaw === null || ! $operation) {
                    continue;
                }

                $student = null;
                $studentCode = $row[$mapping['student_code']] ?? null;
                if ($studentCode) {
                    $student = Student::where('code', $studentCode)
                        ->orWhere('email', $studentCode)
                        ->first();
                }

                $bank = null;
                $bankCode = $row[$mapping['bank_code']] ?? null;
                if ($bankCode) {
                    $bank = Bank::where('short_code', strtoupper($bankCode))->first();
                }

                $bankAccount = null;
                $accountNumber = $row[$mapping['account_number']] ?? null;
                if ($bank && $accountNumber) {
                    $bankAccount = BankAccount::where('bank_id', $bank->id)
                        ->where('account_number', $accountNumber)
                        ->first();
                }

                try {
                    PaymentVoucher::create([
                        'voucher_batch_id' => $voucherBatch->id,
                        'student_id' => $student?->id,
                        'bank_id' => $bank?->id,
                        'bank_account_id' => $bankAccount?->id,
                        'payment_type' => $row[$mapping['payment_type']] ?? 'Transferencia',
                        'operation_number' => trim($operation),
                        'amount' => $this->parseAmount($amountRaw),
                        'currency' => $row[$mapping['currency']] ?? 'BOB',
                        'paid_at' => $this->parseDate($row[$mapping['paid_at']] ?? null),
                        'received_at' => now(),
                        'status' => $row[$mapping['status']] ?? 'recibido',
                        'billing_status' => 'pendiente',
                        'account_reference' => $row[$mapping['account_number']] ?? null,
                        'raw_payload' => $row,
                    ]);
                    $inserted++;
                } catch (\Throwable $e) {
                    // Duplicates or validation issues are skipped to keep el flujo.
                    continue;
                }
            }

            $importBatch->update([
                'status' => 'completed',
                'summary_data' => [
                    'lines' => $inserted,
                ],
            ]);

            return [
                'message' => "Vouchers importados: {$inserted} líneas registradas.",
                'summary' => [
                    'lines' => $inserted,
                    'batch_id' => $voucherBatch->id,
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

    private function parseAmount(string $value): float
    {
        $normalized = str_replace(['.', ','], ['', '.'], $value);
        if (! is_numeric($normalized)) {
            $normalized = preg_replace('/[^0-9.-]/', '', $value) ?: '0';
        }

        return (float) $normalized;
    }
}
