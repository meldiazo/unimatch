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

        return DB::transaction(function () use ($bank, $account, $user, $statementDate, $uploadedFile, $rows) {
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
                'reference' => 'reference',
                'description' => 'description',
                'operation_date' => 'operation_date',
                'value_date' => 'value_date',
                'amount' => 'amount',
            ], Arr::get($bank->format_config, 'columns', []));

            $inserted = 0;
            foreach ($rows as $index => $row) {
                $operationNumber = $row[$mapping['operation_number']] ?? null;
                $amountRaw = $row[$mapping['amount']] ?? null;

                if ($operationNumber === null || $amountRaw === null) {
                    continue;
                }

                $operationDate = $this->parseDate($row[$mapping['operation_date']] ?? null);
                $valueDate = $this->parseDate($row[$mapping['value_date']] ?? null);

                BankStatementLine::create([
                    'bank_statement_id' => $statement->id,
                    'line_number' => $index + 1,
                    'operation_number' => trim((string) $operationNumber),
                    'reference' => Arr::get($row, $mapping['reference']) ?? null,
                    'description' => Arr::get($row, $mapping['description']) ?? null,
                    'operation_date' => $operationDate,
                    'value_date' => $valueDate,
                    'amount' => $this->parseAmount($amountRaw),
                    'currency' => 'BOB',
                    'raw_payload' => $row,
                ]);
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
                ],
            ]);

            return [
                'message' => "Extracto importado: {$inserted} líneas registradas.",
                'summary' => [
                    'lines' => $inserted,
                    'statement_id' => $statement->id,
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
                $headers = array_map(fn ($header) => Str::of($header)->trim()->snake()->value(), $data);
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
        $normalized = str_replace(['.', ','], ['', '.'], $value);
        if (! is_numeric($normalized)) {
            $normalized = preg_replace('/[^0-9.-]/', '', $value) ?: '0';
        }

        return (float) $normalized;
    }
}
