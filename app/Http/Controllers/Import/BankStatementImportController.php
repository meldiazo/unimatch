<?php

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Services\Imports\BankStatementImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BankStatementImportController extends Controller
{
    public function __construct(private BankStatementImporter $importer)
    {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bank_id' => ['required', 'exists:banks,id'],
            'statement_date' => ['nullable', 'date'],
            'file' => ['required', 'file', 'mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ]);

        $bank = Bank::findOrFail($validated['bank_id']);

        try {
            $result = $this->importer->handle(
                user: $request->user(),
                bank: $bank,
                uploadedFile: $validated['file'],
                statementDate: $validated['statement_date'] ?? null,
            );
        } catch (\InvalidArgumentException $exception) {
            return back()
                ->withErrors(['file' => $exception->getMessage()])
                ->withInput();
        }

        return back()
            ->with('status', $result['message'])
            ->with('import_summary', $result['summary']);
    }
}
