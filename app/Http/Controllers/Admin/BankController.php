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
            'bank_code' => 'Código del banco (ej. BNB)',
            'account_number' => 'Número de cuenta',
            'operation_number' => 'N° de operación',
            'reference' => 'Referencia',
            'description' => 'Descripción',
            'operation_date' => 'Fecha de operación',
            'value_date' => 'Fecha valor',
            'amount' => 'Monto',
        ];

        $config = $bank->format_config ?? ['columns' => [], 'date_format' => 'Y-m-d'];

        return view('admin.banks.format', [
            'bank' => $bank,
            'columns' => $columns,
            'config' => $config,
            'canManageFormats' => Gate::allows('manage-bank-settings'),
        ]);
    }

    public function updateFormat(Request $request, Bank $bank): RedirectResponse
    {
        $validated = $request->validate([
            'columns' => ['array'],
            'columns.*' => ['nullable', 'string', 'max:100'],
            'date_format' => ['nullable', 'string', 'max:30'],
        ]);

        $columns = collect($validated['columns'] ?? [])
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => trim($value))
            ->all();

        $bank->update([
            'format_config' => [
                'columns' => $columns,
                'date_format' => $validated['date_format'] ?: 'Y-m-d',
            ],
        ]);

        return redirect()
            ->route('admin.banks.format', $bank)
            ->with('status', 'Formato actualizado.');
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
