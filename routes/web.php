<?php

use App\Http\Controllers\Admin\BankAccountController as AdminBankAccountController;
use App\Http\Controllers\Admin\BankController as AdminBankController;
use App\Http\Controllers\Admin\ReconciliationSettingController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Import\BankStatementImportController;
use App\Http\Controllers\Import\VoucherImportController;
use App\Http\Controllers\Income\MatchingController;
use App\Http\Controllers\Import\BillingStatusImportController;
use App\Http\Controllers\Income\VoucherController as IncomeVoucherController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Student\VoucherController as StudentVoucherController;
use App\Models\Bank;
use App\Models\PaymentVoucher;
use App\Models\Student;
use App\Models\User;
use App\Support\ExecutiveDashboardBuilder;
use App\Support\MatchingDataBuilder;
use App\Support\ReconciliationSettings;
use Illuminate\Support\Facades\Route;

$renderExecutiveDashboard = function () {
    return view('dashboards.jefe', [
        'dashboard' => app(ExecutiveDashboardBuilder::class)->build(),
    ]);
};

Route::middleware(['auth', 'verified'])->group(function () use ($renderExecutiveDashboard) {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user->role === User::ROLE_JEFE_CONTABILIDAD) {
            return redirect()->route('admin.dashboard');
        }

        $studentRecord = Student::where('email', $user->email)->first();

        return match ($user->role) {
            User::ROLE_ENCARGADO_INGRESOS => (function () {
                $banks = Bank::with('accounts')->get();
                $students = Student::orderBy('full_name')->get();
                $recentVouchers = PaymentVoucher::with(['student', 'bankAccount.bank'])
                    ->latest()
                    ->take(10)
                    ->get();

                $matchingData = app(MatchingDataBuilder::class)->build($banks, $students);
                $reconciliationSettings = app(ReconciliationSettings::class)->current();

                return view('dashboards.ingresos', [
                    'banks' => $banks,
                    'students' => $students,
                    'recentVouchers' => $recentVouchers,
                    'matchingData' => $matchingData,
                    'reconciliationSettings' => $reconciliationSettings,
                    'statusOptions' => [
                        'recibido' => 'Recibido',
                        'validado' => 'Validado',
                        'demasia' => 'Pago en demasía',
                        'rechazado' => 'Rechazado',
                    ],
                ]);
            })(),
            User::ROLE_CAJERO => view('dashboards.cajero'),
            default => view('dashboards.estudiante', [
                'banks' => Bank::with('accounts')->get(),
                'studentRecord' => $studentRecord,
                'studentVouchers' => $studentRecord
                    ? PaymentVoucher::with(['bankAccount.bank'])
                        ->where('student_id', $studentRecord->id)
                        ->latest()
                        ->get()
                    : collect(),
            ]),
        };
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/reportes/pagos', [ReportController::class, 'pagos'])
        ->name('reports.pagos');

    Route::post('/ingresos/import/extractos', BankStatementImportController::class)
        ->name('imports.extracts');
    Route::post('/ingresos/import/facturacion', BillingStatusImportController::class)
        ->name('imports.billing');
    Route::post('/ingresos/import/vouchers', VoucherImportController::class)
        ->name('imports.vouchers');

    Route::post('/ingresos/vouchers', [IncomeVoucherController::class, 'store'])
        ->name('ingresos.vouchers.store');
    Route::patch('/ingresos/vouchers/{voucher}', [IncomeVoucherController::class, 'update'])
        ->name('ingresos.vouchers.update');
    Route::post('/ingresos/conciliacion/confirmar', MatchingController::class)
        ->name('ingresos.matching.confirm');

    Route::post('/estudiante/vouchers', [StudentVoucherController::class, 'store'])
        ->name('student.vouchers.store');

    Route::group([
        'prefix' => 'admin',
        'as' => 'admin.',
        'middleware' => function ($request, $next) {
            abort_unless(
                $request->user()->hasRole([User::ROLE_JEFE_CONTABILIDAD, User::ROLE_ENCARGADO_INGRESOS]),
                403
            );

            return $next($request);
        },
    ], function () use ($renderExecutiveDashboard) {
        Route::get('/', function () use ($renderExecutiveDashboard) {
            return $renderExecutiveDashboard();
        })->name('dashboard');

        Route::get('settings/reconciliation', [ReconciliationSettingController::class, 'edit'])->name('settings.reconciliation.edit');
        Route::put('settings/reconciliation', [ReconciliationSettingController::class, 'update'])->name('settings.reconciliation.update');
        Route::get('banks/{bank}/format', [AdminBankController::class, 'editFormat'])->name('banks.format');
        Route::put('banks/{bank}/format', [AdminBankController::class, 'updateFormat'])->name('banks.format.update');
        Route::resource('banks', AdminBankController::class)->except(['show']);
        Route::resource('bank-accounts', AdminBankAccountController::class)->except(['show']);
        Route::resource('students', AdminStudentController::class)->except(['show']);
        Route::resource('users', AdminUserController::class)->except(['show']);
    });
});

require __DIR__.'/auth.php';
