<?php

namespace App\Support;

use App\Models\Bank;
use App\Models\PaymentVoucher;
use App\Models\Transaction;
use Carbon\Carbon;

class ExecutiveDashboardBuilder
{
    public function build(): array
    {
        $today = Carbon::today();

        $totals = [
            'facturado_hoy' => (float) PaymentVoucher::whereDate('paid_at', $today)
                ->where('billing_status', 'facturado')
                ->sum('amount'),
            'operaciones_facturadas' => PaymentVoucher::where('billing_status', 'facturado')->count(),
            'operaciones_sin_factura' => PaymentVoucher::where('billing_status', '!=', 'facturado')->count(),
            'alertas' => Transaction::where(function ($query) {
                $query->whereNotNull('difference_amount')
                    ->where('difference_amount', '!=', 0);
            })->count(),
        ];

        $alertas = Transaction::with(['line.statement.bank', 'voucher.student'])
            ->where(function ($query) {
                $query->whereNotNull('difference_amount')
                    ->where('difference_amount', '!=', 0);
            })
            ->latest('matched_at')
            ->limit(5)
            ->get()
            ->map(function (Transaction $transaction) {
                $difference = (float) ($transaction->difference_amount ?? 0);

                return [
                    'id' => $transaction->id,
                    'bank' => $transaction->line?->statement?->bank?->name ?? 'Banco no identificado',
                    'student' => $transaction->voucher?->student?->full_name ?? 'Sin estudiante',
                    'amount' => (float) ($transaction->line?->amount ?? 0),
                    'difference' => $difference,
                    'status' => $transaction->status ?? 'flagged',
                    'date' => optional($transaction->matched_at ?? $transaction->created_at)?->format('d/m/Y') ?? now()->format('d/m/Y'),
                ];
            });

        $bankSummaries = Bank::withSum('vouchers as total_amount', 'amount')
            ->withSum(['vouchers as facturado_amount' => function ($query) {
                $query->where('billing_status', 'facturado');
            }], 'amount')
            ->get()
            ->map(function (Bank $bank) {
                return [
                    'bank' => $bank->name,
                    'short_code' => $bank->short_code,
                    'total' => (float) ($bank->total_amount ?? 0),
                    'facturado' => (float) ($bank->facturado_amount ?? 0),
                ];
            });

        $trend = $this->buildTrend();

        return [
            'totals' => $totals,
            'alerts' => $alertas,
            'bankSummaries' => $bankSummaries,
            'trend' => $trend,
        ];
    }

    private function buildTrend(int $days = 7): array
    {
        $start = Carbon::today()->subDays($days - 1);
        $labels = [];
        $seriesFacturado = [];
        $seriesPendiente = [];

        for ($i = 0; $i < $days; $i++) {
            $date = (clone $start)->addDays($i);
            $labels[] = $date->format('d/m');

            $seriesFacturado[] = (float) PaymentVoucher::whereDate('paid_at', $date)
                ->where('billing_status', 'facturado')
                ->sum('amount');

            $seriesPendiente[] = (float) PaymentVoucher::whereDate('paid_at', $date)
                ->where('billing_status', '!=', 'facturado')
                ->sum('amount');
        }

        return [
            'labels' => $labels,
            'facturado' => $seriesFacturado,
            'pendiente' => $seriesPendiente,
        ];
    }
}
