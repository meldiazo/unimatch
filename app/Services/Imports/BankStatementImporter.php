<?php

namespace App\Services\Imports;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\ImportBatch;
use App\Models\User;
use App\Support\BinaryXlsReader;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BankStatementImporter
{
    public function __construct(private FilesystemFactory $filesystem)
    {
    }

    /**
     * @return array{message:string, summary:array}
     */
    public function handle(
        User $user,
        Bank $bank,
        UploadedFile $uploadedFile,
        ?string $statementDate
    ): array {
        $disk = $this->filesystem->disk('local');
        if (! $disk->exists('imports')) {
            $disk->makeDirectory('imports');
        }

        $extension = $uploadedFile->getClientOriginalExtension() ?: 'csv';
        $filename = Str::uuid().'.'.$extension;
        $disk->putFileAs('imports', $uploadedFile, $filename);
        $storedPath = 'imports/'.$filename;

        $rows = $this->parseRowsForBank($bank, $disk->path($storedPath));

        if (empty($rows)) {
            throw new \InvalidArgumentException('El archivo no contiene filas para importar.');
        }

        $account = $this->resolveAccountForImport($bank, $rows);

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

            $inserted = 0;
            $skipped = 0;
            $seenInBatch = [];
            foreach ($rows as $index => $row) {
                $normalizedOperation = trim((string) ($row['operation_number'] ?? ''));
                if ($normalizedOperation === '') {
                    continue;
                }

                if (isset($existingOperationMap[$normalizedOperation]) || isset($seenInBatch[$normalizedOperation])) {
                    $skipped++;
                    continue;
                }

                $operationDate = $this->castDateValue($row['operation_date'] ?? null);
                $valueDate = $this->castDateValue($row['value_date'] ?? null) ?? $operationDate;
                $reference = $row['reference'] ?? null;
                $description = $row['description'] ?? $reference;
                $office = $row['office'] ?? null;
                $transactionTime = $this->parseTimeValue($row['transaction_time'] ?? null);
                $debit = $row['debit_amount'] ?? null;
                $credit = $row['credit_amount'] ?? null;
                $runningBalance = $row['running_balance'] ?? null;
                $rawPayload = $row['raw_payload'] ?? $row;

                BankStatementLine::create([
                    'bank_statement_id' => $statement->id,
                    'line_number' => $index + 1,
                    'operation_number' => $normalizedOperation,
                    'reference' => $reference,
                    'description' => $description,
                    'office' => $office,
                    'transaction_time' => $transactionTime,
                    'operation_date' => $operationDate,
                    'value_date' => $valueDate,
                    'amount' => $this->resolveAmountValue($row),
                    'currency' => $row['currency'] ?? 'BOB',
                    'debit_amount' => $debit !== null ? $this->parseAmount($debit) : null,
                    'credit_amount' => $credit !== null ? $this->parseAmount($credit) : null,
                    'running_balance' => $runningBalance !== null ? $this->parseAmount($runningBalance) : null,
                    'raw_payload' => $rawPayload,
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

    private function parseRowsForBank(Bank $bank, string $path): array
    {
        $shortCode = strtoupper($bank->short_code);

        return match ($shortCode) {
            'BNB' => $this->parseBnbSheet($path),
            default => $this->normalizeCsvRows($bank, $this->parseCsv($path)),
        };
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

    private function normalizeCsvRows(Bank $bank, array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $mapping = array_merge([
            'operation_number' => 'operation_number',
            'description' => 'description',
            'reference' => 'reference',
            'operation_date' => 'operation_date',
            'value_date' => 'value_date',
            'amount' => 'amount',
        ], Arr::get($bank->format_config, 'columns', []));

        if (! array_key_exists('description', $mapping) && array_key_exists('reference', $mapping)) {
            $mapping['description'] = $mapping['reference'];
        }

        return array_map(function (array $row) use ($mapping) {
            return [
                'operation_number' => $row[$mapping['operation_number']] ?? null,
                'description' => $row[$mapping['description']] ?? null,
                'reference' => $row[$mapping['reference']] ?? null,
                'operation_date' => $row[$mapping['operation_date']] ?? null,
                'value_date' => $row[$mapping['value_date']] ?? null,
                'amount' => $row[$mapping['amount']] ?? null,
                'currency' => 'BOB',
                'raw_payload' => $row,
            ];
        }, $rows);
    }

    private function parseBnbSheet(string $path): array
    {
        if ($this->isBinaryXls($path)) {
            $rows = BinaryXlsReader::extractRows($path);

            return $this->normalizeBnbRows($rows);
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            return [];
        }

        $content = $this->normalizeEncoding($content);
        $rawRows = str_contains(strtolower($content), '<table')
            ? $this->parseHtmlTable($content)
            : $this->parseTabDelimited($content);

        return $this->normalizeBnbRows($rawRows);
    }

    private function parseHtmlTable(string $content): array
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($content);
        libxml_clear_errors();

        $rows = [];
        foreach ($dom->getElementsByTagName('tr') as $tr) {
            $cells = [];
            foreach ($tr->childNodes as $cell) {
                if ($cell instanceof \DOMElement && in_array(strtolower($cell->tagName), ['td', 'th'], true)) {
                    $cells[] = trim(preg_replace('/\s+/', ' ', $cell->textContent));
                }
            }
            if ($cells) {
                $rows[] = $cells;
            }
        }

        return $rows;
    }

    private function parseTabDelimited(string $content): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($content));
        $rows = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (str_contains($line, "\t")) {
                $rows[] = array_map('trim', explode("\t", $line));
                continue;
            }

            if (str_contains($line, ';')) {
                $rows[] = array_map('trim', str_getcsv($line, ';'));
                continue;
            }

            if (str_contains($line, ',')) {
                $rows[] = array_map('trim', str_getcsv($line, ','));
                continue;
            }

            $normalized = preg_replace('/\s{2,}/', "\t", $line);
            $rows[] = array_map('trim', explode("\t", $normalized));
        }

        return $rows;
    }

    private function normalizeEncoding(string $content): string
    {
        $encoding = mb_detect_encoding($content, ['UTF-8', 'UTF-16LE', 'UTF-16', 'ISO-8859-1'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            return mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        return $content;
    }

    private function isBinaryXls(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if (! $handle) {
            return false;
        }

        $signature = fread($handle, 8) ?: '';
        fclose($handle);

        return $signature === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";
    }

    private function normalizeHeader(string $header): string
    {
        return Str::of($header)
            ->lower()
            ->ascii()
            ->replace(['.', '-', '/', '  '], [' ', ' ', ' ', ' '])
            ->snake()
            ->value();
    }

    private function parseTabValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function castDateValue(null|string|Carbon $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        $value = $this->parseTabValue($value);
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseTimeValue(?string $value): ?string
    {
        $value = $this->parseTabValue($value);
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable) {
            $parts = explode(':', $value);
            if (count($parts) >= 2) {
                $parts = array_map(fn ($segment) => str_pad(preg_replace('/\D/', '', $segment), 2, '0', STR_PAD_LEFT), $parts);
                return implode(':', array_slice($parts, 0, 3));
            }

            return null;
        }
    }

    private function parseNumeric(?string $value): ?float
    {
        $value = $this->parseTabValue($value);
        if ($value === null) {
            return null;
        }

        return $this->parseAmount($value);
    }

    private function parseAmount(null|string|float $value): float
    {
        if ($value === null) {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $raw = trim(str_replace(["\xc2\xa0", ' '], '', (string) $value));
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

    private function resolveAmountValue(array $row): float
    {
        if (array_key_exists('amount', $row) && $row['amount'] !== null) {
            return $this->parseAmount($row['amount']);
        }

        $debit = $row['debit_amount'] ?? null;
        $credit = $row['credit_amount'] ?? null;

        if ($debit !== null || $credit !== null) {
            return (float) ($this->parseAmount($credit ?? 0) - $this->parseAmount($debit ?? 0));
        }

        return 0.0;
    }

    private function normalizeBnbRows(array $rawRows): array
    {
        if (empty($rawRows)) {
            return [];
        }

        $headers = null;
        while (! empty($rawRows)) {
            $candidate = array_map([$this, 'normalizeHeader'], $rawRows[0]);
            $hasFecha = in_array('fecha', $candidate, true);
            $hasOperation = in_array('codigo_de_transaccion', $candidate, true) || in_array('codigo', $candidate, true);

            if ($hasFecha && $hasOperation) {
                $headers = $candidate;
                array_shift($rawRows);
                break;
            }

            array_shift($rawRows);
        }

        if ($headers === null) {
            return [];
        }

        $normalizedRows = [];
        foreach ($rawRows as $row) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $data = [];
            foreach ($headers as $index => $header) {
                $data[$header] = $row[$index] ?? null;
            }

            $operationNumber = trim((string) ($data['codigo_de_transaccion'] ?? $data['codigo'] ?? ''));
            if ($operationNumber === '') {
                continue;
            }

            $date = $this->castDateValue($data['fecha'] ?? null);
            $time = $this->parseTimeValue($data['hora'] ?? null);
            $debit = $this->parseNumeric($data['debitos'] ?? null);
            $credit = $this->parseNumeric($data['creditos'] ?? null);
            $balance = $this->parseNumeric($data['saldo'] ?? null);

            $normalizedRows[] = [
                'operation_number' => $operationNumber,
                'description' => $data['descripcion'] ?? null,
                'reference' => $data['referencia'] ?? null,
                'operation_date' => $date,
                'value_date' => $date,
                'transaction_time' => $time,
                'office' => $data['oficina'] ?? null,
                'debit_amount' => $debit,
                'credit_amount' => $credit,
                'running_balance' => $balance,
                'currency' => 'BOB',
                'raw_payload' => $data,
            ];
        }

        return $normalizedRows;
    }

    private function resolveAccountForImport(Bank $bank, array $rows): BankAccount
    {
        $accountNumber = null;
        $candidates = ['account_number', 'cuenta', 'numero_de_cuenta', 'cuenta_destino'];

        foreach ($rows as $row) {
            foreach ($candidates as $candidate) {
                if (! empty($row[$candidate])) {
                    $value = trim((string) $row[$candidate]);
                    if ($value !== '') {
                        $accountNumber = preg_replace('/[^0-9]/', '', $value) ?: $value;
                        break 2;
                    }
                }

                if (! empty($row['raw_payload'][$candidate] ?? null)) {
                    $value = trim((string) $row['raw_payload'][$candidate]);
                    if ($value !== '') {
                        $accountNumber = preg_replace('/[^0-9]/', '', $value) ?: $value;
                        break 2;
                    }
                }
            }
        }

        if ($accountNumber) {
            $account = $bank->accounts()->where('account_number', $accountNumber)->first();
            if ($account) {
                return $account;
            }
        }

        $account = $bank->accounts()->first();
        if (! $account) {
            throw new \InvalidArgumentException("Configura una cuenta bancaria para {$bank->name} antes de importar.");
        }

        return $account;
    }
}
