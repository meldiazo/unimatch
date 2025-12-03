<?php

namespace App\Services\Imports;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\ImportBatch;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;

class BankStatementImporter
{
    public function __construct(private FilesystemFactory $filesystem)
    {
    }

    /**
     * @return array{message:string, summary:array}
     */
    public function handle(User $user, ?string $statementDate, UploadedFile $uploadedFile): array
    {
        $disk = $this->filesystem->disk('local');
        if (! $disk->exists('imports')) {
            $disk->makeDirectory('imports');
        }

        $filename = Str::uuid().'.csv';
        $disk->putFileAs('imports', $uploadedFile, $filename);
        $storedPath = 'imports/'.$filename;

        $rows = $this->parseCsv($disk->path($storedPath));

        if (empty($rows)) {
            throw new \InvalidArgumentException('El archivo no contiene filas para importar.');
        }

        [$bank, $account] = $this->resolveBankAndAccount($rows);

        $incomingOperations = collect($rows)
            ->map(fn ($row) => trim((string) ($row['operation_number'] ?? '')))
            ->filter()
            ->unique()
            ->values();

        $existingOperations = BankStatementLine::whereHas('statement', function ($query) use ($account) {
                $query->where('bank_account_id', $account->id);
            })
            ->whereIn('operation_number', $incomingOperations)
            ->pluck('operation_number')
            ->map(fn ($op) => trim((string) $op))
            ->filter()
            ->all();

        $existingOperationMap = array_flip($existingOperations);

        return DB::transaction(function () use (
            $bank,
            $account,
            $user,
            $statementDate,
            $uploadedFile,
            $rows,
            $existingOperationMap
        ) {
            $batch = ImportBatch::create([
                'import_type' => 'extractos',
                'source_name' => $uploadedFile->getClientOriginalName(),
                'uploaded_by' => $user->id,
                'status' => 'processing',
            ]);

            $statement = BankStatement::create([
                'bank_account_id' => $account->id,
                'import_batch_id' => $batch->id,
                'statement_date' => $statementDate,
                'currency' => 'BOB',
                'status' => 'processing',
            ]);

            $mapping = array_merge([
                'operation_number' => 'operation_number',
                'description' => 'description',
                'operation_date' => 'operation_date',
                'value_date' => 'value_date',
                'amount' => 'amount',
            ], Arr::get($bank->format_config, 'columns', []));

            if (! array_key_exists('description', $mapping) && array_key_exists('reference', $mapping)) {
                $mapping['description'] = $mapping['reference'];
            }

            $inserted = 0;
            $skipped = 0;
            $seenInBatch = [];
            foreach ($rows as $index => $row) {
                $operationNumber = $row[$mapping['operation_number']] ?? null;
                $amountRaw = $row[$mapping['amount']] ?? null;

                if ($operationNumber === null || $amountRaw === null) {
                    continue;
                }

                $normalizedOperation = trim((string) $operationNumber);
                if ($normalizedOperation === '') {
                    continue;
                }

                if (isset($existingOperationMap[$normalizedOperation]) || isset($seenInBatch[$normalizedOperation])) {
                    $skipped++;
                    continue;
                }

                $operationDate = $this->parseDate($row[$mapping['operation_date']] ?? null);
                $valueDate = $this->parseDate($row[$mapping['value_date']] ?? null);
                $rawDetail = Arr::get($row, $mapping['description']) ?? null;
                $detail = $rawDetail !== null ? trim((string) $rawDetail) : null;

                BankStatementLine::create([
                    'bank_statement_id' => $statement->id,
                    'line_number' => $index + 1,
                    'operation_number' => $normalizedOperation,
                    'reference' => $detail,
                    'description' => $detail,
                    'operation_date' => $operationDate,
                    'value_date' => $valueDate,
                    'amount' => $this->parseAmount($amountRaw),
                    'currency' => 'BOB',
                    'raw_payload' => $row,
                ]);
                $seenInBatch[$normalizedOperation] = true;
                $inserted++;
            }

            $statement->update([
                'status' => 'completed',
            ]);

            $batch->update([
                'status' => 'completed',
                'summary_data' => [
                    'lines' => $inserted,
                    'file' => $uploadedFile->getClientOriginalName(),
                    'duplicates_skipped' => $skipped,
                ],
            ]);

            return [
                'message' => "Extracto importado: {$inserted} líneas registradas.".($skipped ? " {$skipped} duplicados omitidos." : ''),
                'summary' => [
                    'lines' => $inserted,
                    'statement_id' => $statement->id,
                    'skipped' => $skipped,
                ],
            ];
        });
    }

    private function parseCsv(string $fullPath): array
    {
        $handle = fopen($fullPath, 'r');
        if (! $handle) {
            return [];
        }

        $rows = [];
        $headers = null;
        while (($data = fgetcsv($handle, 0, ',', '"')) !== false) {
            if ($headers === null) {
                $headers = array_map(function ($header) {
                    $normalized = Str::of($header)->trim()->snake()->value();
                    if (in_array($normalized, ['detalle', 'reference'], true)) {
                        return 'description';
                    }
                    return $normalized;
                }, $data);
                continue;
            }

            if (!$headers) {
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

    /**
     * @return array{0:\App\Models\Bank,1:\App\Models\BankAccount}
     */
    private function resolveBankAndAccount(array $rows): array
    {
        $bankCode = null;
        $accountNumber = null;

        foreach ($rows as $row) {
            if (! $bankCode && ! empty($row['bank_code'])) {
                $bankCode = strtoupper(trim((string) $row['bank_code']));
            }

            if (! $accountNumber && ! empty($row['account_number'])) {
                $accountNumber = trim((string) $row['account_number']);
            }

            if ($bankCode && $accountNumber) {
                break;
            }
        }

        if (! $bankCode) {
            throw new \InvalidArgumentException('El archivo no especifica la columna bank_code para identificar el banco.');
        }

        $bank = Bank::where('short_code', $bankCode)->first();

        if (! $bank) {
            throw new \InvalidArgumentException("No se encontró un banco con el código {$bankCode}.");
        }

        if (! $accountNumber) {
            throw new \InvalidArgumentException('El archivo no especifica la columna account_number.');
        }

        $account = BankAccount::where('bank_id', $bank->id)
            ->where('account_number', $accountNumber)
            ->first();

        if (! $account) {
            throw new \InvalidArgumentException("No se encontró la cuenta {$accountNumber} para el banco {$bank->short_code}.");
        }

        return [$bank, $account];
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (empty($value)) {
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
}
