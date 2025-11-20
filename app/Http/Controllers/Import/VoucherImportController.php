<?php

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use App\Services\Imports\VoucherImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VoucherImportController extends Controller
{
    public function __construct(private VoucherImporter $importer)
    {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'voucher_file' => ['required', 'file', 'mimetypes:text/csv,text/plain,application/vnd.ms-excel,application/csv'],
        ]);

        $result = $this->importer->handle(
            file: $validated['voucher_file'],
            user: $request->user(),
        );

        return back()
            ->with('status', $result['message'])
            ->with('import_summary', $result['summary']);
    }
}
