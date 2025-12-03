<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;
use App\Models\SalesBookEntry;
use App\Models\User;
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

        $inserted = 0;
        foreach ($rows as $index => $row) {
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
            ],
        ]);

        return [
            'message' => "Libro de ventas importado: {$inserted} líneas registradas.",
            'summary' => [
                'lines' => $inserted,
                'batch_id' => $batch->id,
            ],
        ];
    }

    private function parseSheet(string $path): array
    {
        $content = @file_get_contents($path);
        if ($content === false) {
            return [];
        }

        $content = $this->normalizeEncoding($content);
        $rawRows = str_contains(strtolower($content), '<table')
            ? $this->parseHtmlTable($content)
            : $this->parseDelimited($content);

        if (empty($rawRows)) {
            return [];
        }

        $headers = array_map(fn ($header) => $this->normalizeHeader($header), array_shift($rawRows));
        $fechaKeys = array_keys(array_filter($headers, fn ($header) => $header === 'fecha'));
        $invoiceDateKey = array_shift($fechaKeys);
        $recordedDateKey = array_shift($fechaKeys);

        $rows = [];
        foreach ($rawRows as $row) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $data = [];
            foreach ($headers as $index => $header) {
                $data[$header.'__'.$index] = $row[$index] ?? null;
            }

            $mapped = [
                'legacy_number' => $this->valueFor($data, ['nro', 'numero']),
                'invoice_number' => $this->valueFor($data, ['numero_de_factura', 'numero_factura', 'nro_factura']),
                'nit_ci' => $this->valueFor($data, ['nit_ci', 'nit']),
                'razon_social' => $this->valueFor($data, ['razon_social']),
                'student_name' => $this->valueFor($data, ['nombre_estudiante', 'nombre']),
                'payment_type' => $this->valueFor($data, ['tipo_de_pago', 'tipo_pago']),
                'amount' => $this->parseAmount($this->valueFor($data, ['monto', 'importe'])),
                'account_label' => $this->valueFor($data, ['cuenta', 'cuenta_bancaria']),
                'state_label' => $this->valueFor($data, ['estado', 'estado_factura']),
                'custom_id' => $this->valueFor($data, ['id', 'codigo']),
                'bank_name' => $this->valueFor($data, ['banco']),
                'raw_payload' => $data,
            ];

            if ($invoiceDateKey !== null) {
                $mapped['invoice_date'] = $this->parseDateValue($data['fecha__'.$invoiceDateKey] ?? null);
            }

            if ($recordedDateKey !== null) {
                $mapped['recorded_date'] = $this->parseDateValue($data['fecha__'.$recordedDateKey] ?? null);
            } else {
                $mapped['recorded_date'] = $mapped['invoice_date'];
            }

            if (empty($mapped['invoice_number']) && $mapped['amount'] === 0.0) {
                continue;
            }

            $rows[] = $mapped;
        }

        return $rows;
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

            $separator = str_contains($line, "\t") ? "\t" : ',';
            $rows[] = array_map('trim', str_getcsv($line, $separator));
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

    private function normalizeHeader(string $header): string
    {
        return Str::of($header)
            ->lower()
            ->replace(['.', '-', '/', '  '], [' ', ' ', ' ', ' '])
            ->replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'])
            ->snake()
            ->value();
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
