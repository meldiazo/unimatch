<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ReconciliationSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReconciliationSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-reconciliation-settings')->only('update');
    }

    public function edit(ReconciliationSettings $settings): View
    {
        $record = $settings->current();

        return view('admin.settings.reconciliation', [
            'settings' => $record,
            'canManageSettings' => auth()->user()?->can('manage-reconciliation-settings'),
        ]);
    }

    public function update(Request $request, ReconciliationSettings $settings): RedirectResponse
    {
        $validated = $request->validate([
            'difference_alert_threshold' => ['required', 'numeric', 'min:0'],
            'shortage_alert_threshold' => ['required', 'numeric', 'min:0'],
            'credit_max_amount' => ['required', 'numeric', 'min:0'],
            'voucher_statuses' => ['nullable', 'string'],
            'voucher_rules' => ['nullable', 'string'],
            'voucher_template_help' => ['nullable', 'string'],
        ]);

        $rawStatuses = $validated['voucher_statuses'] ?? '';
        $states = collect(preg_split('/\r\n|[\r\n]/', $rawStatuses))
            ->map(fn ($value) => trim($value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $record = $settings->current();

        $record->update([
            'difference_alert_threshold' => $validated['difference_alert_threshold'],
            'shortage_alert_threshold' => $validated['shortage_alert_threshold'],
            'credit_max_amount' => $validated['credit_max_amount'],
            'voucher_statuses' => $states ?: $record->voucher_statuses,
            'voucher_rules' => $validated['voucher_rules'],
            'voucher_template_help' => $validated['voucher_template_help'],
        ]);

        $settings->refresh();

        return back()->with('status', 'Parámetros de conciliación actualizados.');
    }
}
