<?php

namespace App\Services\Imports;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\ImportBatch;
use App\Models\User;
use App\Support\BinaryXlsReader;
use App\Support\SimpleXlsxReader;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

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

        $incomingOperationsForQuery = collect($rows)
            ->map(fn ($row) => trim((string) ($row['operation_number'] ?? '')))
            ->filter()
            ->unique()
            ->values();

        $existingOperations = BankStatementLine::whereHas('statement', function ($query) use ($account) {
                $query->where('bank_account_id', $account->id);
            })
            ->whereIn('operation_number', $incomingOperationsForQuery)
            ->pluck('operation_number')
            ->map(fn ($op) => $this->normalizeOperationKey($op))
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
                $rawOperation = trim((string) ($row['operation_number'] ?? ''));
                $operationKey = $this->normalizeOperationKey($rawOperation);
                if ($operationKey === '') {
                    continue;
                }

                if (isset($existingOperationMap[$operationKey]) || isset($seenInBatch[$operationKey])) {
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

                $rawPayload = $row['raw_payload'] ?? $row;

                $rawDebit = $this->valueFromRow($rawPayload, ['debitos', 'debito', 'debito', 'cargos', 'debit']);
                if ($rawDebit !== null) {
                    $debit = $rawDebit;
                }

                $rawCredit = $this->valueFromRow($rawPayload, ['creditos', 'credito', 'credit', 'abonos']);
                if ($rawCredit !== null) {
                    $credit = $rawCredit;
                }

                if ($debit === null && $credit === null && array_key_exists('amount', $row) && $row['amount'] !== null) {
                    $signedAmount = $this->parseAmount($row['amount']);
                    if ($signedAmount < 0) {
                        $debit = abs($signedAmount);
                    } elseif ($signedAmount > 0) {
                        $credit = $signedAmount;
                    }
                }

                $runningBalance = $row['running_balance'] ?? null;
                $rawBalance = $this->valueFromRow($rawPayload, ['saldo', 'balance', 'running_balance']);
                if ($rawBalance !== null) {
                    $runningBalance = $rawBalance;
                }

                BankStatementLine::create([
                    'bank_statement_id' => $statement->id,
                    'line_number' => $index + 1,
                    'operation_number' => $rawOperation !== '' ? $rawOperation : $operationKey,
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

                $seenInBatch[$operationKey] = true;
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

        $strategy = Arr::get($bank->format_config, 'strategy');
        if ($strategy === 'custom') {
            $custom = $this->parseCustomSheet($bank, $path);
            if (! empty($custom)) {
                return $custom;
            }
        }

        return match ($shortCode) {
            'BNB' => $this->parseBnbSheet($path),
            'BE' => $this->parseEconomicoSheet($path),
            'BCP' => $this->parseBcpSheet($path),
            'BISA' => $this->parseBisaSheet($path),
            'BMSC' => $this->parseMercantilSheet($path),
            'BNI' => $this->parseUnionSheet($path),
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
            'running_balance' => 'running_balance',
            'debit_amount' => 'debit_amount',
            'credit_amount' => 'credit_amount',
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
                'debit_amount' => $this->valueFromRow($row, [
                    $mapping['debit_amount'] ?? null,
                    'debit',
                    'debito',
                    'cargos',
                ]),
                'credit_amount' => $this->valueFromRow($row, [
                    $mapping['credit_amount'] ?? null,
                    'credit',
                    'credito',
                    'creditos',
                    'abonos',
                ]),
                'running_balance' => $this->valueFromRow($row, [
                    $mapping['running_balance'] ?? null,
                    'running_balance',
                    'balance',
                    'saldo',
                ]),
                'currency' => 'BOB',
                'raw_payload' => $row,
            ];
        }, $rows);
    }

    private function valueFromRow(array $row, array $candidates): mixed
    {
        foreach ($candidates as $candidate) {
            if ($candidate === null) {
                continue;
            }

            if (! array_key_exists($candidate, $row)) {
                continue;
            }

            $value = $row[$candidate];

            if (is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    continue;
                }

                return $trimmed;
            }

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function parseBnbSheet(string $path): array
    {
        $rows = $this->parseExcelRows($path);
        if (empty($rows)) {
            $content = @file_get_contents($path);
            if ($content === false) {
                return [];
            }

            $content = $this->normalizeEncoding($content);
            $rows = str_contains(strtolower($content), '<table')
                ? $this->parseHtmlTable($content)
                : $this->parseTabDelimited($content);
        }

        if (empty($rows)) {
            return [];
        }

        $headers = array_map([$this, 'normalizeHeader'], array_shift($rows));
        $required = [
            'fecha' => 'operation_date',
            'hora' => 'transaction_time',
            'oficina' => 'office',
            'descripcion' => 'description',
            'referencia' => 'reference',
            'codigo_de_transaccion' => 'operation_number',
            'debitos' => 'debit',
            'creditos' => 'credit',
            'saldo' => 'balance',
        ];

        $positions = [];
        foreach ($required as $header => $alias) {
            $index = array_search($header, $headers, true);
            if ($index === false) {
                throw new InvalidArgumentException('El archivo no coincide con el formato de Banco BNB.');
            }
            $positions[$alias] = $index;
        }

        $normalized = [];
        foreach ($rows as $row) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $operationNumber = trim((string) ($row[$positions['operation_number']] ?? ''));
            if ($operationNumber === '') {
                continue;
            }

            $normalized[] = [
                'operation_number' => $operationNumber,
                'description' => $row[$positions['description']] ?? null,
                'reference' => $row[$positions['reference']] ?? null,
                'operation_date' => $this->castDateValue($row[$positions['operation_date']] ?? null),
                'value_date' => $this->castDateValue($row[$positions['operation_date']] ?? null),
                'transaction_time' => $this->parseTimeValue($row[$positions['transaction_time']] ?? null),
                'office' => $row[$positions['office']] ?? null,
                'debit_amount' => $this->parseNumeric($row[$positions['debit']] ?? null),
                'credit_amount' => $this->parseNumeric($row[$positions['credit']] ?? null),
                'running_balance' => $this->parseNumeric($row[$positions['balance']] ?? null),
                'raw_payload' => $row,
            ];
        }

        return $normalized;
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

    private function parseExcelRows(string $path): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === 'xlsx' || $this->isXlsx($path)) {
            $rows = SimpleXlsxReader::extractRows($path);
            if (! empty($rows)) {
                return $rows;
            }
        }

        if ($extension === 'xls' || $this->isBinaryXls($path)) {
            $rows = BinaryXlsReader::extractRows($path);
            if (! empty($rows)) {
                return $rows;
            }
        }

        return [];
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

    private function isXlsx(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if (! $handle) {
            return false;
        }

        $signature = fread($handle, 4) ?: '';
        fclose($handle);

        return $signature === "PK\x03\x04";
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

    /**
     * @param array<string, array<int, string>> $aliases
     * @param callable(array<string, mixed>, array<int, string>): ?array $converter
     */
    private function parseStructuredXls(
        string $path,
        array $aliases,
        callable $converter,
        ?int $minMatches = null,
        bool $strictHeaders = false,
        ?string $formatLabel = null
    ): array {
        $rows = $this->parseExcelRows($path);
        if (empty($rows)) {
            return [];
        }

        $minMatches = $minMatches ?? min(3, count($aliases));
        $headerIndex = null;
        $positions = [];

        foreach ($rows as $index => $row) {
            $normalized = array_map([$this, 'normalizeHeader'], $row);
            $positionCandidate = [];
            $matches = 0;

            foreach ($aliases as $field => $candidates) {
                foreach ((array) $candidates as $candidate) {
                    $columnIndex = array_search($candidate, $normalized, true);
                    if ($columnIndex !== false) {
                        $positionCandidate[$field] = $columnIndex;
                        $matches++;
                        break;
                    }
                }
            }

            if ($matches >= $minMatches) {
                $headerIndex = $index;
                $positions = $positionCandidate;
                break;
            }
        }

        if ($headerIndex === null) {
            if ($strictHeaders) {
                $label = $formatLabel ?: 'el formato esperado';
                throw new InvalidArgumentException("El archivo no coincide con el formato de {$label}.");
            }

            return [];
        }

        $normalizedRows = [];
        for ($i = $headerIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $mapped = [];
            foreach ($positions as $field => $columnIndex) {
                $mapped[$field] = $row[$columnIndex] ?? null;
            }

            $normalizedRow = $converter($mapped, $row, $i - $headerIndex);
            if ($normalizedRow) {
                $normalizedRows[] = $normalizedRow;
            }
        }

        return $normalizedRows;
    }

    private function parseEconomicoSheet(string $path): array
    {
        $rows = $this->parseExcelRows($path);
        if (empty($rows)) {
            return [];
        }

        $headers = array_map([$this, 'normalizeHeader'], array_shift($rows));

        $required = [
            'fecha' => 'operation_date',
            'hora' => 'transaction_time',
            'no' => 'operation_number',
            'descripcion' => 'description',
            'debito' => 'debit',
            'credito' => 'credit',
            'saldo' => 'balance',
        ];

        $positions = [];
        foreach ($required as $header => $alias) {
            $index = array_search($header, $headers, true);
            if ($index === false) {
                throw new InvalidArgumentException('El archivo no coincide con el formato de Banco Económico.');
            }

            $positions[$alias] = $index;
        }

        $normalized = [];
        foreach ($rows as $row) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $operationNumber = trim((string) ($row[$positions['operation_number']] ?? ''));
            if ($operationNumber === '') {
                continue;
            }

            $normalized[] = [
                'operation_number' => $operationNumber,
                'description' => $row[$positions['description']] ?? null,
                'operation_date' => $this->castDateValue($row[$positions['operation_date']] ?? null),
                'value_date' => $this->castDateValue($row[$positions['operation_date']] ?? null),
                'transaction_time' => $this->parseTimeValue($row[$positions['transaction_time']] ?? null),
                'debit_amount' => $this->parseNumeric($row[$positions['debit']] ?? null),
                'credit_amount' => $this->parseNumeric($row[$positions['credit']] ?? null),
                'running_balance' => $this->parseNumeric($row[$positions['balance']] ?? null),
                'raw_payload' => $row,
            ];
        }

        return $normalized;
    }

    private function parseBcpSheet(string $path): array
    {
        return $this->parseStructuredXls(
            $path,
            [
                'operation_date' => ['fecha'],
                'transaction_time' => ['hora'],
                'glosa' => ['glosa'],
                'tipo' => ['tipo'],
                'sucursal' => ['sucursal_agencia', 'sucursal'],
                'usuario' => ['usuario'],
                'amount' => ['importe', 'monto'],
                'balance' => ['saldo'],
                'operation_ref' => ['n_operaciones', 'numero_operaciones'],
            ],
            function (array $mapped, array $rawRow, int $position) {
                $amount = $this->parseAmount($mapped['amount'] ?? 0);
                $debit = null;
                $credit = null;
                if ($amount < 0) {
                    $debit = abs($amount);
                } else {
                    $credit = $amount;
                }

                $operationNumber = trim((string) ($mapped['operation_ref'] ?? ''));
                if ($operationNumber === '') {
                    $operationNumber = strtoupper(substr(sha1(implode('|', [
                        $mapped['operation_date'] ?? '',
                        $mapped['transaction_time'] ?? '',
                        $mapped['glosa'] ?? '',
                        $mapped['amount'] ?? '',
                    ])), 0, 16));
                }

                $description = trim(implode(' · ', array_filter([
                    $mapped['glosa'] ?? null,
                    $mapped['tipo'] ?? null,
                ], fn ($part) => trim((string) $part) !== '')));

                $referenceParts = array_filter([
                    $mapped['sucursal'] ?? null,
                    $mapped['usuario'] ?? null,
                ], fn ($part) => trim((string) $part) !== '');

                return [
                    'operation_number' => $operationNumber,
                    'description' => $description ?: null,
                    'reference' => $referenceParts ? implode(' / ', $referenceParts) : null,
                    'operation_date' => $this->castDateValue($mapped['operation_date'] ?? null),
                    'value_date' => $this->castDateValue($mapped['operation_date'] ?? null),
                    'transaction_time' => $this->parseTimeValue($mapped['transaction_time'] ?? null),
                    'office' => $mapped['sucursal'] ?? null,
                    'debit_amount' => $debit,
                    'credit_amount' => $credit,
                    'running_balance' => $this->parseNumeric($mapped['balance'] ?? null),
                    'currency' => 'BOB',
                    'raw_payload' => $mapped,
                ];
            },
            minMatches: 6,
            strictHeaders: true,
            formatLabel: 'Banco de Crédito BCP'
        );
    }

    private function parseBisaSheet(string $path): array
    {
        return $this->parseStructuredXls(
            $path,
            [
                'operation_date' => ['fecha'],
                'transaction_time' => ['hora'],
                'reference' => ['nro_ref', 'codigo'],
                'cheque' => ['nro_cheque'],
                'description' => ['descripcion'],
                'amount' => ['importe', 'monto'],
                'balance' => ['saldo'],
                'complement' => ['info_complementaria'],
                'sucursal' => ['sucursal'],
                'canal' => ['canal'],
            ],
            function (array $mapped, array $rawRow, int $position) {
                $amount = $this->parseAmount($mapped['amount'] ?? 0);
                $debit = null;
                $credit = null;
                if ($amount < 0) {
                    $debit = abs($amount);
                } else {
                    $credit = $amount;
                }

                $operationNumber = trim(
                    (string) ($mapped['reference'] ?? $mapped['cheque'] ?? '')
                );

                if ($operationNumber === '') {
                    return null;
                }

                $description = trim((string) ($mapped['description'] ?? ''));
                if (! empty($mapped['complement'])) {
                    $description = trim($description.' · '.$mapped['complement']);
                }

                $referenceParts = array_filter([
                    $mapped['sucursal'] ?? null,
                    $mapped['canal'] ?? null,
                ], fn ($part) => trim((string) $part) !== '');

                return [
                    'operation_number' => $operationNumber,
                    'description' => $description ?: null,
                    'reference' => $referenceParts ? implode(' / ', $referenceParts) : null,
                    'operation_date' => $this->castDateValue($mapped['operation_date'] ?? null),
                    'value_date' => $this->castDateValue($mapped['operation_date'] ?? null),
                    'transaction_time' => $this->parseTimeValue($mapped['transaction_time'] ?? null),
                    'office' => $mapped['sucursal'] ?? null,
                    'debit_amount' => $debit,
                    'credit_amount' => $credit,
                    'running_balance' => $this->parseNumeric($mapped['balance'] ?? null),
                    'currency' => 'BOB',
                    'raw_payload' => $mapped,
                ];
            },
            strictHeaders: true,
            formatLabel: 'Banco BISA'
        );
    }

    private function parseMercantilSheet(string $path): array
    {
        $rows = $this->parseExcelRows($path);
        if (empty($rows)) {
            return [];
        }

        $headers = array_map([$this, 'normalizeHeader'], array_shift($rows));

        $required = [
            'fecha' => 'operation_date',
            'hora' => 'transaction_time',
            'cod_bca' => 'operation_number',
            'doc_depositante' => 'document',
            'nombre_denominacion_depositante' => 'depositante',
            'tipo_transact' => 'tipo',
            'descripcion' => 'description',
            'glosa' => 'glosa',
            'oficina' => 'office',
            'nom_destinatario' => 'destinatario',
            'debito' => 'debit',
            'credito' => 'credit',
            'saldo' => 'balance',
        ];

        $positions = [];
        foreach ($required as $header => $alias) {
            $index = array_search($header, $headers, true);
            if ($index === false) {
                throw new InvalidArgumentException('El archivo no coincide con el formato de Banco Mercantil.');
            }
            $positions[$alias] = $index;
        }

        $normalized = [];
        foreach ($rows as $row) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $operationNumber = trim((string) ($row[$positions['operation_number']] ?? ''));
            if ($operationNumber === '') {
                continue;
            }

            $descriptionParts = array_filter([
                $row[$positions['description']] ?? null,
                $row[$positions['glosa']] ?? null,
                $row[$positions['tipo']] ?? null,
            ], fn ($part) => trim((string) $part) !== '');

            $referenceParts = array_filter([
                $row[$positions['depositante']] ?? null,
                $row[$positions['destinatario']] ?? null,
                $row[$positions['office']] ?? null,
            ], fn ($part) => trim((string) $part) !== '');

            $normalized[] = [
                'operation_number' => $operationNumber,
                'description' => $descriptionParts ? implode(' · ', $descriptionParts) : null,
                'reference' => $referenceParts ? implode(' / ', $referenceParts) : null,
                'operation_date' => $this->castDateValue($row[$positions['operation_date']] ?? null),
                'value_date' => $this->castDateValue($row[$positions['operation_date']] ?? null),
                'transaction_time' => $this->parseTimeValue($row[$positions['transaction_time']] ?? null),
                'office' => $row[$positions['office']] ?? null,
                'debit_amount' => $this->parseNumeric($row[$positions['debit']] ?? null),
                'credit_amount' => $this->parseNumeric($row[$positions['credit']] ?? null),
                'running_balance' => $this->parseNumeric($row[$positions['balance']] ?? null),
                'raw_payload' => $row,
            ];
        }

        return $normalized;
    }

    private function parseUnionSheet(string $path): array
    {
        return $this->parseStructuredXls(
            $path,
            [
                'operation_date' => ['fecha_movimiento', 'fecha'],
                'ag' => ['ag', 'agencia'],
                'description' => ['descripcion'],
                'operation_number' => ['nro_documento', 'documento'],
                'amount' => ['monto'],
                'balance' => ['saldo'],
            ],
            function (array $mapped, array $rawRow, int $position) {
                $amount = $this->parseAmount($mapped['amount'] ?? 0);
                $description = strtolower(trim((string) ($mapped['description'] ?? '')));

                $isDebit = $amount < 0
                    || str_contains($description, 'n/d')
                    || str_contains($description, 'debito')
                    || str_contains($description, 'cargo');

                $debit = $isDebit ? abs($amount) : null;
                $credit = $isDebit ? null : $amount;

                $operationNumber = trim((string) ($mapped['operation_number'] ?? ''));
                if ($operationNumber === '') {
                    $operationNumber = strtoupper(substr(sha1(implode('|', [
                        $mapped['operation_date'] ?? '',
                        $mapped['ag'] ?? '',
                        $mapped['description'] ?? '',
                        $mapped['amount'] ?? '',
                        $position,
                    ])), 0, 16));
                }

                return [
                    'operation_number' => $operationNumber,
                    'description' => $mapped['description'] ?? null,
                    'reference' => $mapped['ag'] ?? null,
                    'operation_date' => $this->castDateValue($mapped['operation_date'] ?? null),
                    'value_date' => $this->castDateValue($mapped['operation_date'] ?? null),
                    'transaction_time' => null,
                    'office' => $mapped['ag'] ?? null,
                    'debit_amount' => $debit,
                    'credit_amount' => $credit,
                    'running_balance' => $this->parseNumeric($mapped['balance'] ?? null),
                    'currency' => 'BOB',
                    'raw_payload' => $mapped,
                ];
            },
            minMatches: 4,
            strictHeaders: true,
            formatLabel: 'Banco Unión'
        );
    }

    private function parseCustomSheet(Bank $bank, string $path): array
    {
        $config = $bank->format_config ?? [];
        $columns = Arr::get($config, 'columns', []);
        if (empty($columns)) {
            return [];
        }
        $columnsIndex = Arr::get($config, 'columns_index', []);

        $rows = $this->parseExcelRows($path);
        if (empty($rows)) {
            $rows = $this->parseCsv($path);
        }

        if (empty($rows)) {
            return [];
        }

        $headerRow = max(0, (int) (($config['header_row'] ?? 1) - 1));
        $headers = $rows[$headerRow] ?? null;
        if (! $headers) {
            return [];
        }

        $normalizedHeaders = array_map([$this, 'normalizeHeader'], $headers);
        $positions = [];
        foreach ($columns as $field => $headerName) {
            $indexCandidate = $columnsIndex[$field] ?? null;
            if ($indexCandidate !== null && (int) $indexCandidate > 0) {
                $positions[$field] = ((int) $indexCandidate) - 1;
                continue;
            }
            if ($headerName) {
                $normalizedTarget = $this->normalizeHeader($headerName);
                $idx = array_search($normalizedTarget, $normalizedHeaders, true);
                if ($idx !== false) {
                    $positions[$field] = $idx;
                }
            }
        }

        if (! isset($positions['operation_number'])) {
            throw new InvalidArgumentException('El formato personalizado debe indicar la columna de número de operación.');
        }

        $normalized = [];
        for ($i = $headerRow + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $get = function (string $key) use ($positions, $row) {
                return isset($positions[$key]) ? ($row[$positions[$key]] ?? null) : null;
            };

            $operationNumber = trim((string) $get('operation_number'));
            if ($operationNumber === '') {
                continue;
            }

            $operationDateRaw = $get('operation_date');
            $operationDate = $this->castCustomDate($operationDateRaw, $config['date_format'] ?? null);

            $debit = $get('debit_amount');
            $credit = $get('credit_amount');
            $amount = $get('amount');
            if ($debit === null && $credit === null && $amount !== null) {
                $signed = $this->parseAmount($amount);
                if ($signed < 0) {
                    $debit = abs($signed);
                } else {
                    $credit = $signed;
                }
            }

            $normalized[] = [
                'operation_number' => $operationNumber,
                'description' => $get('description') ?? $get('reference'),
                'reference' => $get('reference'),
                'operation_date' => $operationDate,
                'value_date' => $operationDate,
                'transaction_time' => $this->parseTimeValue($get('transaction_time')),
                'office' => $get('office'),
                'debit_amount' => $debit !== null ? $this->parseAmount($debit) : null,
                'credit_amount' => $credit !== null ? $this->parseAmount($credit) : null,
                'running_balance' => $get('running_balance') !== null ? $this->parseAmount($get('running_balance')) : null,
                'currency' => 'BOB',
                'raw_payload' => $row,
            ];
        }

        return $normalized;
    }

    private function castCustomDate($value, ?string $format): ?Carbon
    {
        if ($format) {
            try {
                return Carbon::createFromFormat($format, (string) $value);
            } catch (\Throwable) {
                // fallback
            }
        }

        return $this->castDateValue($value);
    }

    private function normalizeOperationKey(null|string $value): string
    {
        if ($value === null) {
            return '';
        }

        $clean = Str::of($value)
            ->ascii()
            ->lower()
            ->replace(' ', '')
            ->value();

        if ($clean === '') {
            return '';
        }

        if (preg_match('/^-?[0-9.,]+$/', $clean)) {
            $numeric = str_replace([',', '.'], '', $clean);
            $numeric = ltrim($numeric, '0');

            return $numeric === '' ? '0' : $numeric;
        }

        return $clean;
    }

    private function parseTabValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function castDateValue(null|string|int|float|Carbon $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (is_numeric($value)) {
            $numeric = (float) $value;
            if ($numeric <= 0) {
                return null;
            }

            $timestamp = ($numeric - 25569) * 86400;

            return Carbon::createFromTimestampUTC($timestamp);
        }

        $value = $this->parseTabValue($value);
        if (! $value) {
            return null;
        }

        try {
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{2}$/', $value)) {
                return Carbon::createFromFormat('d/m/y', $value);
            }

            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
                return Carbon::createFromFormat('d/m/Y', $value);
            }

            if (preg_match('/^\d{1,2}-\d{1,2}-\d{2}$/', $value)) {
                return Carbon::createFromFormat('d-m-y', $value);
            }

            if (preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $value)) {
                return Carbon::createFromFormat('d-m-Y', $value);
            }

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
