<?php

namespace App\Support;

use App\Models\Bank;
use App\Models\SalesBookEntry;
use App\Models\StudentBalance;
use App\Models\Transaction;
use App\Support\StudentBalanceProjector;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExecutiveDashboardBuilder
{
    public function build(): array
    {
        app(StudentBalanceProjector::class)->sync();
        $today = Carbon::today();

        $conciliatedStates = ['conciliado', 'demasia'];

        $totals = [
            'facturado_hoy' => (float) SalesBookEntry::whereDate('invoice_date', $today)
                ->whereIn('state_label', $conciliatedStates)
                ->sum('amount'),
            'operaciones_facturadas' => SalesBookEntry::whereIn('state_label', $conciliatedStates)->count(),
            'operaciones_sin_factura' => SalesBookEntry::whereNotIn('state_label', $conciliatedStates)->count(),
            'alertas' => Transaction::whereNotNull('difference_amount')
                ->where('difference_amount', '!=', 0)
                ->count(),
        ];

        $alertas = Transaction::with(['line.statement.account.bank', 'salesEntry'])
            ->where(function ($query) {
                $query->whereNotNull('difference_amount')
                    ->where('difference_amount', '!=', 0);
            })
            ->latest('matched_at')
            ->limit(5)
            ->get()
            ->map(function (Transaction $transaction) {
                $difference = (float) ($transaction->difference_amount ?? 0);
                $entry = $transaction->salesEntry;

                return [
                    'id' => $transaction->id,
                    'bank' => $transaction->line?->statement?->account?->bank?->name ?? 'Banco no identificado',
                    'student' => $entry?->student_name ?? 'Sin estudiante',
                    'amount' => (float) ($entry?->amount ?? $transaction->line?->amount ?? 0),
                    'difference' => $difference,
                    'status' => $transaction->status ?? 'flagged',
                    'date' => optional($transaction->matched_at ?? $transaction->created_at)?->format('d/m/Y') ?? now()->format('d/m/Y'),
                ];
            });

        $bankSummaries = $this->buildBankSummaries();

        $studentRows = $this->buildStudentOverpayments();

        $trend = $this->buildTrend();

        return [
            'totals' => $totals,
            'alerts' => $alertas,
            'bankSummaries' => $bankSummaries,
            'students' => $studentRows,
            'trend' => $trend,
        ];
    }

    private function buildTrend(int $days = 7): array
    {
        $start = Carbon::today()->subDays($days - 1);
        $labels = [];
        $seriesFacturado = [];
        $seriesPendiente = [];
        $conciliatedStates = ['conciliado', 'demasia'];

        for ($i = 0; $i < $days; $i++) {
            $date = (clone $start)->addDays($i);
            $labels[] = $date->format('d/m');

            $seriesFacturado[] = (float) SalesBookEntry::whereDate('invoice_date', $date)
                ->whereIn('state_label', $conciliatedStates)
                ->sum('amount');

            $seriesPendiente[] = (float) SalesBookEntry::whereDate('invoice_date', $date)
                ->whereNotIn('state_label', $conciliatedStates)
                ->sum('amount');
        }

        return [
            'labels' => $labels,
            'facturado' => $seriesFacturado,
            'pendiente' => $seriesPendiente,
        ];
    }

    private function buildBankSummaries(): array
    {
        $latestPerAccount = DB::table('bank_statement_lines as l')
            ->join('bank_statements as s', 'l.bank_statement_id', '=', 's.id')
            ->select('s.bank_account_id', DB::raw('MAX(l.id) as latest_line_id'))
            ->groupBy('s.bank_account_id');

        $latestLines = DB::table('bank_statement_lines as l')
            ->join('bank_statements as s', 'l.bank_statement_id', '=', 's.id')
            ->joinSub($latestPerAccount, 'latest', function ($join) {
                $join->on('s.bank_account_id', '=', 'latest.bank_account_id')
                    ->on('l.id', '=', 'latest.latest_line_id');
            })
            ->join('bank_accounts as ba', 's.bank_account_id', '=', 'ba.id')
            ->select('ba.bank_id', 'l.running_balance')
            ->get();

        $balancesByBank = $latestLines
            ->groupBy('bank_id')
            ->map(function ($rows) {
                return (float) $rows->reduce(function ($carry, $item) {
                    return $carry + (float) ($item->running_balance ?? 0);
                }, 0);
            });

        return Bank::orderBy('name')
            ->get()
            ->map(function (Bank $bank) use ($balancesByBank) {
                return [
                    'bank' => $bank->name,
                    'short_code' => $bank->short_code,
                    'balance' => $balancesByBank->get($bank->id, 0.0),
                ];
            })
            ->values()
            ->all();
    }

    private function buildStudentOverpayments(): array
    {
        return StudentBalance::with('student')
            ->orderByDesc('updated_at')
            ->take(25)
            ->get()
            ->filter(fn (StudentBalance $balance) => (float) $balance->balance_amount > 0)
            ->map(function (StudentBalance $balance) {
                return [
                    'name' => $balance->student?->full_name ?? '—',
                    'balance' => (float) $balance->balance_amount,
                    'credited_at' => optional($balance->updated_at)->format('d/m/Y') ?? '—',
                ];
            })
            ->values()
            ->all();
    }
}
