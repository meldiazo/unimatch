<?php

namespace App\Http\Controllers\Income;

use App\Http\Controllers\Controller;
use App\Models\BankStatementLine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

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
}
