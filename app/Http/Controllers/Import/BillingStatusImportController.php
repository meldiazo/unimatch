<?php

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use App\Services\Imports\BillingStatusImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BillingStatusImportController extends Controller
{
    public function __construct(private BillingStatusImporter $importer)
    {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'billing_file' => ['required', 'file', 'mimetypes:text/csv,text/plain,application/vnd.ms-excel,application/csv'],
        ]);

        $result = $this->importer->handle(
            file: $validated['billing_file'],
            user: $request->user(),
        );

        return back()
            ->with('status', $result['message'])
            ->with('import_summary', $result['summary']);
    }
}
