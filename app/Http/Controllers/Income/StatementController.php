<?php

namespace App\Http\Controllers\Income;

use App\Http\Controllers\Controller;
use App\Models\BankStatementLine;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class StatementController extends Controller
{
    public function index(Request $request): View
    {
        $lines = BankStatementLine::with([
                'statement.account.bank',
                'statement.importBatch',
            ])
            ->latest('operation_date')
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('ingresos.statements.index', [
            'lines' => $lines,
        ]);
    }

    public function export(Request $request)
    {
        $format = strtolower($request->query('format', 'pdf'));

        $lines = BankStatementLine::with(['statement.account.bank'])
            ->orderBy('operation_date')
            ->orderBy('id')
            ->get();

        $filename = 'extractos-cargados';

        return match ($format) {
            'pdf' => $this->downloadStatementsPdf($lines, $filename.'.pdf'),
            'txt' => $this->downloadStatementsText($lines, $filename.'.txt'),
            'xls' => $this->downloadStatementsXls($lines, $filename.'.xls'),
            default => abort(400, 'Formato no válido.'),
        };
    }

    public function update(Request $request, BankStatementLine $line): RedirectResponse
    {
        $data = $request->validate(
            [
                'custom_identifier' => [
                    'nullable',
                    'string',
                    'max:100',
                    Rule::unique('bank_statement_lines', 'custom_identifier')->ignore($line->id),
                ],
                'billing_reference_date' => ['nullable', 'date'],
            ],
            [
                'custom_identifier.unique' => 'El ID ya fue utilizado en otro extracto.',
            ]
        );

        if (! empty($data['billing_reference_date']) && $line->operation_date) {
            $referenceDate = Carbon::parse($data['billing_reference_date']);
            if ($referenceDate->lt($line->operation_date)) {
                return redirect()
                    ->back()
                    ->withErrors(['billing_reference_date' => 'La fecha de facturación no puede ser anterior a la fecha del extracto.'])
                    ->withInput();
            }
        }

        $line->update($data);

        return redirect()
            ->back()
            ->with('status', 'Extracto actualizado correctamente.');
    }

    protected function downloadStatementsPdf(Collection $lines, string $filename)
    {
        $pdf = Pdf::loadView('exports.statements', [
            'lines' => $lines,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    protected function downloadStatementsText(Collection $lines, string $filename)
    {
        $header = 'Fecha;Hora;Número;Descripción;Débito;Crédito;Saldo;ID;Mes facturado';
        $rows = $lines->map(function (BankStatementLine $line) {
            return implode(';', [
                optional($line->operation_date)->format('Y-m-d') ?? '',
                $line->transaction_time ?? '',
                $line->operation_number ?? '',
                trim($line->description ?? ''),
                $this->formatDecimal($line->debit_amount),
                $this->formatDecimal($line->credit_amount),
                $this->formatDecimal($line->running_balance),
                $line->custom_identifier ?? '',
                optional($line->billing_reference_date)->format('Y-m-d') ?? '',
            ]);
        });

        $content = $header."\n".$rows->implode("\n");

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    protected function downloadStatementsXls(Collection $lines, string $filename)
    {
        $html = view('exports.tables.statements', [
            'lines' => $lines,
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function formatDecimal($value): string
    {
        if ($value === null) {
            return '';
        }

        return number_format((float) $value, 2, '.', '');
    }
}
