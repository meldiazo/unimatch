<?php

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use App\Services\Imports\SalesBookImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SalesBookImportController extends Controller
{
    public function __construct(private SalesBookImporter $importer)
    {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sales_book_file' => ['required', 'file', 'mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ]);

        try {
            $result = $this->importer->handle(
                user: $request->user(),
                uploadedFile: $validated['sales_book_file']
            );
        } catch (\InvalidArgumentException $exception) {
            return back()
                ->withErrors(['sales_book_file' => $exception->getMessage()])
                ->withInput();
        }

        return back()
            ->with('status', $result['message'])
            ->with('sales_book_summary', $result['summary']);
    }
}
