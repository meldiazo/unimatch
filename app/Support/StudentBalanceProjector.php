<?php

namespace App\Support;

use App\Models\StudentBalance;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class StudentBalanceProjector
{
    /**
     * Sincroniza la tabla de saldos con las conciliaciones marcadas como demasía.
     */
    public function sync(): void
    {
        $this->attachMissingStudents();
        $this->projectBalances();
    }

    private function attachMissingStudents(): void
    {
        Transaction::with('salesEntry')
            ->where('status', 'demasia')
            ->whereNull('student_id')
            ->chunkById(100, function ($transactions) {
                foreach ($transactions as $transaction) {
                    $entry = $transaction->salesEntry;
                    if (! $entry) {
                        continue;
                    }

                    $student = StudentResolver::resolveFromEntry($entry, true);
                    if ($student) {
                        $transaction->student_id = $student->id;
                        $transaction->save();
                    }
                }
            });
    }

    private function projectBalances(): void
    {
        $totals = Transaction::where('status', 'demasia')
            ->whereNotNull('student_id')
            ->select('student_id', DB::raw('SUM(difference_amount) as total'))
            ->groupBy('student_id')
            ->get();

        foreach ($totals as $row) {
            $amount = max(0, (float) $row->total);
            if ($amount <= 0) {
                continue;
            }

            $balance = StudentBalance::firstOrCreate(
                ['student_id' => $row->student_id, 'currency' => 'BOB'],
                ['balance_amount' => 0]
            );

            if ($balance->wasRecentlyCreated || (float) $balance->balance_amount + 0.01 < $amount) {
                $balance->balance_amount = $amount;
                $balance->save();
            }
        }
    }
}
