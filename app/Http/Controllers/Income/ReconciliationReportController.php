<?php

namespace App\Http\Controllers\Income;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReconciliationReportController extends Controller
{
    public function index(Request $request): View
    {
        $transactions = Transaction::with([
            'salesEntry',
            'line.statement.account.bank',
            'matchedBy',
        ])
            ->when($request->filled('start_date'), fn($q) => $q->whereDate('matched_at', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn($q) => $q->whereDate('matched_at', '<=', $request->end_date))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('bank'), function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->whereHas('salesEntry', fn($q2) => $q2->where('bank_name', $request->bank))
                        ->orWhereHas('line.statement.account.bank', fn($q3) => $q3->where('name', $request->bank));
                });
            })
            ->latest('matched_at')
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        $banks = Bank::orderBy('name')->pluck('name');
        $statuses = ['conciliado', 'demasia', 'rechazado'];

        return view('ingresos.reconciliations.index', [
            'transactions' => $transactions,
            'banks' => $banks,
            'statuses' => $statuses,
        ]);
    }

    public function export(Request $request)
    {
        $format = strtolower($request->query('format', 'pdf'));

        $transactions = Transaction::with([
            'salesEntry',
            'line.statement.account.bank',
            'matchedBy',
        ])
            ->when($request->filled('start_date'), fn($q) => $q->whereDate('matched_at', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn($q) => $q->whereDate('matched_at', '<=', $request->end_date))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('bank'), function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->whereHas('salesEntry', fn($q2) => $q2->where('bank_name', $request->bank))
                        ->orWhereHas('line.statement.account.bank', fn($q3) => $q3->where('name', $request->bank));
                });
            })
            ->orderBy('matched_at')
            ->orderBy('id')
            ->get();

        $filename = 'reporte-conciliaciones';

        return match ($format) {
            'pdf' => $this->downloadPdf($transactions, $filename.'.pdf'),
            'txt' => $this->downloadText($transactions, $filename.'.txt'),
            'xls' => $this->downloadXls($transactions, $filename.'.xls'),
            default => abort(400, 'Formato no válido.'),
        };
    }

    protected function downloadPdf(Collection $transactions, string $filename)
    {
        $pdf = Pdf::loadView('exports.reconciliations', [
            'transactions' => $transactions,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    protected function downloadText(Collection $transactions, string $filename)
    {
        $header = implode(';', [
            'Nro',
            'Fecha',
            'Número factura',
            'NIT/C.I.',
            'Razón social',
            'Nombre estudiante',
            'Tipo pago',
            'Monto',
            'Cuenta',
            'Estado',
            'ID',
            'Banco',
            'Fecha conciliación',
            'Operación',
        ]);

        $rows = $transactions->map(function (Transaction $transaction) {
            [$entry, $line, $bank, $account] = $this->resolveTransactionParts($transaction);

            return implode(';', [
                $transaction->id,
                optional($entry?->invoice_date)->format('Y-m-d') ?? '',
                $entry?->invoice_number ?? '',
                $entry?->nit_ci ?? '',
                $entry?->razon_social ?? '',
                $entry?->student_name ?? $transaction->student?->full_name ?? '',
                $entry?->payment_type ?? '',
                $this->formatDecimal($entry?->amount ?? $line?->amount ?? 0),
                $account,
                ucfirst($transaction->status ?? 'desconocido'),
                $entry?->custom_id ?? '',
                $entry?->bank_name ?? $bank?->name ?? '',
                optional($transaction->matched_at)->format('Y-m-d H:i') ?? '',
                $entry?->operation_reference ?? $line?->operation_number ?? '',
            ]);
        });

        $content = $header."\n".$rows->implode("\n");

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    protected function downloadXls(Collection $transactions, string $filename)
    {
        $html = view('exports.tables.reconciliations', [
            'transactions' => $transactions,
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * @return array{0: mixed, 1: mixed, 2: mixed, 3: string}
     */
    protected function resolveTransactionParts(Transaction $transaction): array
    {
        $entry = $transaction->salesEntry;
        $line = $transaction->line;
        $bank = optional($line?->statement?->account?->bank);
        $accountLabel = $entry->account_label
            ?? $line?->statement?->account?->number
            ?? '—';

        return [$entry, $line, $bank, $accountLabel];
    }

    private function formatDecimal($value): string
    {
        if ($value === null) {
            return '';
        }

        return number_format((float) $value, 2, '.', '');
    }
}
