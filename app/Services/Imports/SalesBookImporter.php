<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;
use App\Models\SalesBookEntry;
use App\Models\User;
use App\Support\BinaryXlsReader;
use App\Support\SimpleXlsxReader;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SalesBookImporter
{
    public function __construct(private FilesystemFactory $filesystem)
    {
    }

    /**
     * @return array{message:string, summary:array}
     */
    public function handle(User $user, UploadedFile $uploadedFile): array
    {
        $disk = $this->filesystem->disk('local');
        if (! $disk->exists('imports')) {
            $disk->makeDirectory('imports');
        }

        $extension = $uploadedFile->getClientOriginalExtension() ?: 'csv';
        $filename = Str::uuid().'.'.$extension;
        $disk->putFileAs('imports', $uploadedFile, $filename);
        $storedPath = 'imports/'.$filename;

        $rows = $this->parseSheet($disk->path($storedPath));

        if (empty($rows)) {
            throw new \InvalidArgumentException('El archivo no contiene filas válidas.');
        }

        $batch = ImportBatch::create([
            'import_type' => 'sales_book',
            'source_name' => $uploadedFile->getClientOriginalName(),
            'uploaded_by' => $user->id,
            'status' => 'processing',
        ]);

        $invoiceNumbers = collect($rows)
            ->map(fn ($row) => $this->normalizeInvoiceNumber($row['invoice_number'] ?? null))
            ->filter()
            ->unique();

        $existingInvoices = $invoiceNumbers->isNotEmpty()
            ? SalesBookEntry::whereIn('invoice_number', $invoiceNumbers->all())
                ->pluck('invoice_number')
                ->map(fn ($value) => $this->normalizeInvoiceNumber($value))
                ->filter()
                ->flip()
                ->all()
            : [];

        $seenInFile = [];
        $inserted = 0;
        $duplicates = 0;
        foreach ($rows as $index => $row) {
            $normalizedInvoice = $this->normalizeInvoiceNumber($row['invoice_number'] ?? null);
            if ($normalizedInvoice) {
                if (isset($existingInvoices[$normalizedInvoice]) || isset($seenInFile[$normalizedInvoice])) {
                    $duplicates++;
                    continue;
                }

                $seenInFile[$normalizedInvoice] = true;
            }

            SalesBookEntry::create([
                'import_batch_id' => $batch->id,
                'row_number' => $index + 1,
                'legacy_number' => $row['legacy_number'],
                'invoice_date' => $row['invoice_date'],
                'invoice_number' => $row['invoice_number'],
                'nit_ci' => $row['nit_ci'],
                'razon_social' => $row['razon_social'],
                'student_name' => $row['student_name'],
                'payment_type' => $row['payment_type'],
                'amount' => $row['amount'],
                'account_label' => $row['account_label'],
                'state_label' => $row['state_label'],
                'custom_id' => $row['custom_id'],
                'bank_name' => $row['bank_name'],
                'recorded_date' => $row['recorded_date'],
                'raw_payload' => $row['raw_payload'],
            ]);
            $inserted++;
        }

        $batch->update([
            'status' => 'completed',
            'summary_data' => [
                'lines' => $inserted,
                'duplicates_omitted' => $duplicates,
            ],
        ]);

        $message = "Libro de ventas importado: {$inserted} líneas registradas.";
        if ($duplicates > 0) {
            $message .= " Duplicados omitidos: {$duplicates}.";
        }

        return [
            'message' => $message,
            'summary' => [
                'lines' => $inserted,
                'batch_id' => $batch->id,
                'duplicates_omitted' => $duplicates,
            ],
        ];
    }

    private function parseSheet(string $path): array
    {
        $rawRows = $this->extractRows($path);

        if (empty($rawRows)) {
            return [];
        }

        $headers = array_map(fn ($header) => $this->normalizeHeader($header), array_shift($rawRows));
        $requiredMap = [
            'nro' => 'legacy_number',
            'fecha' => 'invoice_date',
            'numero_factura' => 'invoice_number',
            'numero_de_factura' => 'invoice_number',
            'numero_factura_' => 'invoice_number',
            'numero_factura__' => 'invoice_number',
            'nit_ci' => 'nit_ci',
            'nit_c_i' => 'nit_ci',
            'razon_social' => 'razon_social',
            'nombre_estudiante' => 'student_name',
            'tipo_pago' => 'payment_type',
            'monto' => 'amount',
            'cuenta' => 'account_label',
            'estado' => 'state_label',
        ];

        $positions = [];
        foreach ($headers as $index => $header) {
            if (isset($requiredMap[$header]) && ! isset($positions[$requiredMap[$header]])) {
                $positions[$requiredMap[$header]] = $index;
            }
        }

        $requiredFields = ['invoice_date', 'invoice_number', 'amount'];
        foreach ($requiredFields as $field) {
            if (! isset($positions[$field])) {
                throw new \InvalidArgumentException('El archivo no coincide con el formato del reporte diario.');
            }
        }

        $rows = [];
        foreach ($rawRows as $row) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $mapped = [
                'legacy_number' => $this->valueFromPositions($row, $positions, 'legacy_number'),
                'invoice_number' => $this->valueFromPositions($row, $positions, 'invoice_number'),
                'nit_ci' => $this->valueFromPositions($row, $positions, 'nit_ci'),
                'razon_social' => $this->valueFromPositions($row, $positions, 'razon_social'),
                'student_name' => $this->valueFromPositions($row, $positions, 'student_name'),
                'payment_type' => $this->valueFromPositions($row, $positions, 'payment_type'),
                'amount' => $this->parseAmount($this->valueFromPositions($row, $positions, 'amount')),
                'account_label' => $this->valueFromPositions($row, $positions, 'account_label'),
                'state_label' => $this->valueFromPositions($row, $positions, 'state_label'),
                'invoice_date' => $this->parseDateValue($this->valueFromPositions($row, $positions, 'invoice_date')),
                'recorded_date' => null,
                'custom_id' => null,
                'bank_name' => null,
                'operation_reference' => null,
                'raw_payload' => $row,
            ];

            if (empty($mapped['invoice_number']) && $mapped['amount'] === 0.0) {
                continue;
            }

            $rows[] = $mapped;
        }

        return $rows;
    }

    private function normalizeInvoiceNumber(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
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

    private function parseDelimited(string $content): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($content));
        $rows = [];
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            if (str_contains($line, "\t")) {
                $separator = "\t";
            } elseif (str_contains($line, ';')) {
                $separator = ';';
            } else {
                $separator = ',';
            }

            $rows[] = array_map('trim', str_getcsv($line, $separator));
        }

        return $rows;
    }

    private function extractRows(string $path): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === 'xlsx') {
            return SimpleXlsxReader::extractRows($path);
        }

        if ($extension === 'xls') {
            return BinaryXlsReader::extractRows($path);
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            return [];
        }

        $content = $this->normalizeEncoding($content);

        return str_contains(strtolower($content), '<table')
            ? $this->parseHtmlTable($content)
            : $this->parseDelimited($content);
    }

    private function valueFromPositions(array $row, array $positions, string $key): ?string
    {
        if (! isset($positions[$key])) {
            return null;
        }

        $index = $positions[$key];

        return isset($row[$index]) ? trim((string) $row[$index]) : null;
    }

    private function normalizeEncoding(string $content): string
    {
        $encoding = mb_detect_encoding($content, ['UTF-8', 'UTF-16LE', 'UTF-16', 'ISO-8859-1'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            return mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        return $content;
    }

    private function normalizeHeader(string $header): string
    {
        $clean = Str::of($header)
            ->lower()
            ->replace(["\xc2\xa0"], ' ')
            ->replace(['º', '°'], 'o')
            ->replace(['.', '-', '/', '  '], [' ', ' ', ' ', ' '])
            ->replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'])
            ->replace(['#'], 'numero');

        $clean = preg_replace('/[^a-z0-9\s]/', '', $clean) ?? '';

        return Str::of($clean)->snake()->value();
    }

    private function valueFor(array $row, array $candidates): ?string
    {
        foreach ($row as $key => $value) {
            [$name] = explode('__', $key);
            if (in_array($name, $candidates, true) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function parseDateValue(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        $value = trim($value);

        if (is_numeric($value)) {
            $excelSerial = (float) $value;
            if ($excelSerial > 0) {
                try {
                    return Carbon::createFromTimestamp((int) round(($excelSerial - 25569) * 86400));
                } catch (\Throwable) {
                    // Ignorar y probar otros formatos
                }
            }
        }

        $formats = [
            'd/m/y H:i:s',
            'd/m/Y H:i:s',
            'd/m/y H:i',
            'd/m/Y H:i',
            'd/m/y',
            'd/m/Y',
            'Y-m-d H:i:s',
            'Y-m-d',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseAmount(?string $value): float
    {
        if ($value === null) {
            return 0.0;
        }

        $raw = trim(str_replace(["\xc2\xa0", ' '], '', (string) $value));
        if ($raw === '') {
            return 0.0;
        }

        $raw = str_replace(['.', ','], ['', '.'], $raw);
        $normalized = preg_replace('/[^0-9.\-]/', '', $raw) ?: '0';

        return (float) $normalized;
    }
}
