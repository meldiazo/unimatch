<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BankController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-bank-settings')->except(['index', 'editFormat']);
    }

    public function index(): View
    {
        $banks = Bank::orderBy('name')->paginate(15);

        return view('admin.banks.index', compact('banks'));
    }

    public function create(): View
    {
        return view('admin.banks.form', [
            'bank' => new Bank(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateBank($request);

        Bank::create($data);

        return redirect()
            ->route('admin.banks.index')
            ->with('status', 'Banco creado correctamente.');
    }

    public function edit(Bank $bank): View
    {
        return view('admin.banks.form', compact('bank'));
    }

    public function update(Request $request, Bank $bank): RedirectResponse
    {
        $data = $this->validateBank($request, $bank->id);

        $bank->update($data);

        return redirect()
            ->route('admin.banks.index')
            ->with('status', 'Banco actualizado.');
    }

    public function destroy(Bank $bank): RedirectResponse
    {
        $bank->delete();

        return redirect()
            ->route('admin.banks.index')
            ->with('status', 'Banco eliminado.');
    }

    public function editFormat(Bank $bank): View
    {
        $columns = [
            'operation_date' => 'Fecha',
            'transaction_time' => 'Hora',
            'operation_number' => 'N° / Código de operación',
            'description' => 'Descripción',
            'reference' => 'Referencia / Glosa',
            'debit_amount' => 'Débito (monto con signo negativo)',
            'credit_amount' => 'Crédito (monto con signo positivo)',
            'running_balance' => 'Saldo',
        ];

        $config = $bank->format_config ?? ['columns' => [], 'date_format' => 'Y-m-d', 'strategy' => 'fixed'];
        $preset = $this->presetFormatDescription($bank->short_code);

        return view('admin.banks.format', [
            'bank' => $bank,
            'columns' => $columns,
            'config' => $config,
            'preset' => $preset,
            'canManageFormats' => Gate::allows('manage-bank-settings'),
        ]);
    }

    public function updateFormat(Request $request, Bank $bank): RedirectResponse
    {
        $validated = $request->validate([
            'strategy' => ['required', 'in:fixed,custom'],
            'columns' => ['array'],
            'columns.*' => ['nullable', 'string', 'max:100'],
            'date_format' => ['nullable', 'string', 'max:30'],
            'header_row' => ['nullable', 'integer', 'min:1', 'max:50'],
            'columns_index' => ['array'],
            'columns_index.*' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $columns = collect($validated['columns'] ?? [])
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => trim($value))
            ->all();
        $columnsIndex = collect($validated['columns_index'] ?? [])
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => (int) $value)
            ->all();

        $bank->update([
            'format_config' => [
                'strategy' => $validated['strategy'],
                'columns' => $columns,
                'date_format' => $validated['date_format'] ?: 'Y-m-d',
                'header_row' => $validated['header_row'] ?? null,
                'columns_index' => $columnsIndex,
            ],
        ]);

        return redirect()
            ->route('admin.banks.format', $bank)
            ->with('status', 'Formato actualizado.');
    }

    private function presetFormatDescription(?string $shortCode): ?array
    {
        return match (strtoupper((string) $shortCode)) {
            'BNB' => [
                'nombre' => 'Banco BNB',
                'columnas' => ['Fecha', 'Hora', 'Oficina', 'Descripción', 'Referencia', 'Código de transacción', 'Débitos', 'Créditos', 'Saldo'],
                'nota' => 'Se usa el lector fijo actual. Cambia a "Formato personalizado" si el banco cambia su layout.',
            ],
            'BE' => [
                'nombre' => 'Banco Económico',
                'columnas' => ['Fecha', 'Hora', 'No.', 'Descripción', 'Débito', 'Crédito', 'Saldo'],
                'nota' => 'Lector fijo. Solo usa el modo personalizado si el archivo cambia.',
            ],
            'BCP' => [
                'nombre' => 'BCP',
                'columnas' => ['Fecha', 'Hora', 'Glosa', 'Tipo', 'Sucursal Agencia', 'Usuario', 'Importe', 'Saldo', 'N° Operaciones'],
                'nota' => 'Lector fijo.',
            ],
            'BISA' => [
                'nombre' => 'BISA',
                'columnas' => ['Fecha', 'Hora', 'Nro. Cheque', 'Descripción', 'Importe', 'Saldo', 'Info. Complementaria', 'Sucursal', 'Canal', 'Nro. Ref.', 'Codigo'],
                'nota' => 'Lector fijo.',
            ],
            'BMSC' => [
                'nombre' => 'Banco Mercantil',
                'columnas' => ['Fecha', 'Hora', 'Cod. Bca.', 'Nro.Cheque', 'Nro/Nom.Plantilla', 'Cod.Dep.Num', 'Doc.Depositante', 'Nombre/Denominación', 'Tipo transact', 'Descripción', 'Oficina', 'Banco', 'Nom.Destinatario', 'Glosa', 'Débito', 'Crédito', 'Saldo'],
                'nota' => 'Lector fijo.',
            ],
            'BNI' => [
                'nombre' => 'Banco Unión',
                'columnas' => ['Fecha Movimiento', 'Agencia', 'Descripción', 'Nro Documento', 'Monto', 'Saldo'],
                'nota' => 'Lector fijo.',
            ],
            default => null,
        };
    }

    private function validateBank(Request $request, ?int $bankId = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_code' => ['required', 'string', 'max:10', Rule::unique('banks', 'short_code')->ignore($bankId)],
            'status' => ['required', 'in:active,inactive'],
            'format_config' => ['nullable', 'json'],
        ]);

        return [
            'name' => $validated['name'],
            'short_code' => strtoupper($validated['short_code']),
            'status' => $validated['status'],
            'format_config' => $validated['format_config']
                ? json_decode($validated['format_config'], true)
                : null,
        ];
    }
}
