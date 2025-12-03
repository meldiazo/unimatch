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

        $importBatch = ImportBatch::create([
            'import_type' => 'vouchers',
            'source_name' => $file->getClientOriginalName(),
            'uploaded_by' => $user->id,
            'status' => 'processing',
        ]);

        $voucherBatch = VoucherBatch::create([
            'import_batch_id' => $importBatch->id,
        ]);

        try {
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

            $operationNumbers = collect($rows)
                ->map(fn ($row) => trim((string) ($row[$mapping['operation_number']] ?? '')))
                ->filter()
                ->unique()
                ->values();

            $existingOperations = PaymentVoucher::whereIn('operation_number', $operationNumbers)
                ->where('status', '!=', 'rechazado')
                ->pluck('operation_number')
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->all();

            $existingOperationMap = array_flip($existingOperations);
            $seenInBatch = [];

            $inserted = 0;
            $duplicateSkips = 0;
            $failedRows = 0;
            foreach ($rows as $row) {
                $amountRaw = $row[$mapping['amount']] ?? null;
                $operation = $row[$mapping['operation_number']] ?? null;

                if ($amountRaw === null || ! $operation) {
                    continue;
                }

                $normalizedOperation = trim((string) $operation);
                if ($normalizedOperation === '') {
                    continue;
                }

                if (isset($existingOperationMap[$normalizedOperation]) || isset($seenInBatch[$normalizedOperation])) {
                    $duplicateSkips++;
                    continue;
                }

                $studentCode = $row[$mapping['student_code']] ?? null;
                $student = $this->resolveStudent($studentCode);

                $bankAccount = null;
                $bank = null;
                $accountNumber = $row[$mapping['account_number']] ?? null;
                if ($accountNumber) {
                    $bankAccount = BankAccount::whereHas('bank', function ($query) use ($row, $mapping) {
                        $bankCode = $row[$mapping['bank_code']] ?? null;
                        if ($bankCode) {
                            $query->where('short_code', strtoupper($bankCode));
                        }
                    })->where('account_number', $accountNumber)->first();
                    $bank = $bankAccount?->bank;
                }

                if (! $bank) {
                    $bankCode = $row[$mapping['bank_code']] ?? null;
                    if ($bankCode) {
                        $bank = Bank::where('short_code', strtoupper($bankCode))->first();
                    }
                }

                try {
                    PaymentVoucher::create([
                        'voucher_batch_id' => $voucherBatch->id,
                        'student_id' => $student?->id,
                        'bank_id' => $bank?->id,
                        'bank_account_id' => $bankAccount?->id,
                        'payment_type' => $row[$mapping['payment_type']] ?? 'Transferencia',
                        'operation_number' => $normalizedOperation,
                        'amount' => $this->parseAmount($amountRaw),
                        'currency' => $row[$mapping['currency']] ?? 'BOB',
                        'paid_at' => $this->parseDate($row[$mapping['paid_at']] ?? null),
                        'received_at' => now(),
                        'status' => $row[$mapping['status']] ?? 'recibido',
                        'billing_status' => 'pendiente',
                        'account_reference' => $row[$mapping['account_number']] ?? null,
                        'raw_payload' => $row,
                    ]);
                    $seenInBatch[$normalizedOperation] = true;
                    $inserted++;
                } catch (\Throwable $e) {
                    report($e);
                    $failedRows++;
                    continue;
                }
            }

            $importBatch->update([
                'status' => 'completed',
                'summary_data' => [
                    'lines' => $inserted,
                    'duplicates_skipped' => $duplicateSkips,
                    'failed_rows' => $failedRows,
                ],
            ]);

            $message = "Vouchers importados: {$inserted} líneas registradas.";
            if ($duplicateSkips) {
                $message .= " {$duplicateSkips} duplicados omitidos.";
            }
            if ($failedRows) {
                $message .= " {$failedRows} filas con errores.";
            }

            return [
                'message' => $message,
                'summary' => [
                    'lines' => $inserted,
                    'batch_id' => $voucherBatch->id,
                    'duplicates' => $duplicateSkips,
                    'failed_rows' => $failedRows,
                ],
            ];
        } catch (\Throwable $e) {
            $importBatch->update(['status' => 'failed']);
            throw $e;
        }
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
        $raw = trim(str_replace(["\xc2\xa0", ' '], '', $value));
        if ($raw === '') {
            return 0.0;
        }

        $hasComma = str_contains($raw, ',');
        $hasDot = str_contains($raw, '.');

        if ($hasComma && $hasDot) {
            $lastComma = strrpos($raw, ',');
            $lastDot = strrpos($raw, '.');
            if ($lastComma > $lastDot) {
                $raw = str_replace('.', '', $raw);
                $raw = str_replace(',', '.', $raw);
            } else {
                $raw = str_replace(',', '', $raw);
            }
        } elseif ($hasComma) {
            $raw = str_replace(',', '.', $raw);
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', $raw) ?: '0';

        return (float) $normalized;
    }

    private function resolveStudent(?string $identifier): ?Student
    {
        if (! $identifier) {
            return null;
        }

        $identifier = trim((string) $identifier);
        if ($identifier === '') {
            return null;
        }

        $student = Student::query()
            ->where('code', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if ($student) {
            return $student;
        }

        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $code = $isEmail ? strtoupper(Str::random(10)) : $identifier;
        $email = $isEmail ? strtolower($identifier) : null;

        return Student::create([
            'code' => $code,
            'full_name' => $this->deriveStudentName($identifier),
            'email' => $email,
        ]);
    }

    private function deriveStudentName(string $identifier): string
    {
        if (str_contains($identifier, '@')) {
            $base = Str::of($identifier)
                ->before('@')
                ->replace(['.', '_', '-'], ' ')
                ->title()
                ->trim();

            return $base->isEmpty() ? 'Estudiante' : (string) $base;
        }

        return $identifier;
    }
}
