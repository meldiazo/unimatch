<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\BankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-bank-settings')->except('index');
    }

    public function index(): View
    {
        $accounts = BankAccount::with('bank')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.bank_accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        return view('admin.bank_accounts.form', [
            'account' => new BankAccount(),
            'banks' => Bank::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateAccount($request);

        BankAccount::create($data);

        return redirect()
            ->route('admin.bank-accounts.index')
            ->with('status', 'Cuenta bancaria creada.');
    }

    public function edit(BankAccount $bank_account): View
    {
        return view('admin.bank_accounts.form', [
            'account' => $bank_account,
            'banks' => Bank::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, BankAccount $bank_account): RedirectResponse
    {
        $data = $this->validateAccount($request, $bank_account->id);

        $bank_account->update($data);

        return redirect()
            ->route('admin.bank-accounts.index')
            ->with('status', 'Cuenta bancaria actualizada.');
    }

    public function destroy(BankAccount $bank_account): RedirectResponse
    {
        $bank_account->delete();

        return redirect()
            ->route('admin.bank-accounts.index')
            ->with('status', 'Cuenta eliminada.');
    }

    private function validateAccount(Request $request, ?int $accountId = null): array
    {
        $validated = $request->validate([
            'bank_id' => ['required', 'exists:banks,id'],
            'account_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('bank_accounts', 'account_number')
                    ->where(fn ($query) => $query->where('bank_id', $request->input('bank_id')))
                    ->ignore($accountId),
            ],
            'currency' => ['required', 'string', 'max:8'],
            'active' => ['nullable', 'boolean'],
        ]);

        return [
            'bank_id' => $validated['bank_id'],
            'account_number' => $validated['account_number'],
            'currency' => strtoupper($validated['currency']),
            'active' => $request->boolean('active'),
        ];
    }
}
