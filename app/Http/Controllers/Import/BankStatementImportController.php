<?php

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
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
            'statement_date' => ['nullable', 'date'],
            'file' => ['required', 'file', 'mimetypes:text/csv,text/plain,application/vnd.ms-excel,application/csv'],
        ]);

        try {
            $result = $this->importer->handle(
                user: $request->user(),
                statementDate: $validated['statement_date'] ?? null,
                uploadedFile: $validated['file']
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
