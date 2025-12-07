<?php

use App\Http\Controllers\Admin\BankAccountController as AdminBankAccountController;
use App\Http\Controllers\Admin\BankController as AdminBankController;
use App\Http\Controllers\Admin\ReconciliationSettingController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Import\BankStatementImportController;
use App\Http\Controllers\Import\SalesBookImportController;
use App\Http\Controllers\Import\VoucherImportController;
use App\Http\Controllers\Income\MatchingController;
use App\Http\Controllers\Income\VoucherController as IncomeVoucherController;
use App\Http\Controllers\Income\SalesReportController;
use App\Http\Controllers\Income\StatementController;
use App\Http\Controllers\Income\ReconciliationReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Student\VoucherController as StudentVoucherController;
use App\Http\Controllers\Cashier\TransactionController as CashierTransactionController;
use App\Http\Middleware\CheckRole;
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

    // ========== RUTAS CAJERO ==========
    Route::middleware([CheckRole::class.':cajero'])->group(function () {
        Route::get('/api/cashier/transactions/search', [
            CashierTransactionController::class, 'search'
        ])->name('api.cashier.transactions.search');

        Route::get('/cashier/vouchers/{voucher}/certificate', [
            \App\Http\Controllers\Cashier\VoucherController::class, 'downloadCertificate'
        ])->name('cashier.vouchers.certificate');

    });

    // ========== RUTAS ESTUDIANTE ==========
    Route::middleware(['role:estudiante'])->group(function () {
        Route::post('/estudiante/vouchers', [StudentVoucherController::class, 'store'])
            ->name('student.vouchers.store');

        Route::patch('/estudiante/vouchers/{voucher}/replace', [StudentVoucherController::class, 'replace'])
            ->name('student.vouchers.replace');

        Route::get('/api/student/balance', [\App\Http\Controllers\Student\BalanceController::class, 'show'])
            ->name('api.student.balance');
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
                        'conciliado' => 'Conciliado',
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
    Route::post('/ingresos/import/vouchers', VoucherImportController::class)
        ->name('imports.vouchers');
    Route::post('/ingresos/import/libro-ventas', SalesBookImportController::class)
        ->name('imports.sales_book');

    Route::post('/ingresos/vouchers', [IncomeVoucherController::class, 'store'])
        ->name('ingresos.vouchers.store');
    Route::patch('/ingresos/vouchers/{voucher}', [IncomeVoucherController::class, 'update'])
        ->name('ingresos.vouchers.update');
    Route::post('/ingresos/conciliacion/confirmar', MatchingController::class)
        ->name('ingresos.matching.confirm');
    Route::options('/ingresos/conciliacion/confirmar', function () {
        return response()->noContent();
    });
    Route::get('/ingresos/extractos', [StatementController::class, 'index'])
        ->middleware([CheckRole::class.':encargado_ingresos,jefe_contabilidad,cajero'])
        ->name('ingresos.statements.index');
    Route::get('/ingresos/extractos/export', [StatementController::class, 'export'])
        ->middleware([CheckRole::class.':encargado_ingresos,jefe_contabilidad,cajero'])
        ->name('ingresos.statements.export');
    Route::patch('/ingresos/extractos/{line}', [StatementController::class, 'update'])
        ->middleware([CheckRole::class.':encargado_ingresos,jefe_contabilidad'])
        ->name('ingresos.statements.update');
    Route::get('/ingresos/reporte-diario', [SalesReportController::class, 'index'])
        ->middleware([CheckRole::class.':encargado_ingresos,jefe_contabilidad,cajero'])
        ->name('ingresos.sales-report.index');
    Route::get('/ingresos/reporte-diario/export', [SalesReportController::class, 'export'])
        ->middleware([CheckRole::class.':encargado_ingresos,jefe_contabilidad,cajero'])
        ->name('ingresos.sales-report.export');
    Route::patch('/ingresos/reporte-diario/{entry}', [SalesReportController::class, 'update'])
        ->middleware([CheckRole::class.':encargado_ingresos,jefe_contabilidad'])
        ->name('ingresos.sales-report.update');
    Route::get('/ingresos/reporte-conciliaciones', [ReconciliationReportController::class, 'index'])
        ->middleware([CheckRole::class.':encargado_ingresos,jefe_contabilidad,cajero'])
        ->name('ingresos.reconciliation-report.index');
    Route::get('/ingresos/reporte-conciliaciones/export', [ReconciliationReportController::class, 'export'])
        ->middleware([CheckRole::class.':encargado_ingresos,jefe_contabilidad,cajero'])
        ->name('ingresos.reconciliation-report.export');

    Route::post('/estudiante/vouchers', [StudentVoucherController::class, 'store'])
        ->name('student.vouchers.store');

    Route::get('/api/student/balance', [\App\Http\Controllers\Student\BalanceController::class, 'show'])
        ->name('api.student.balance');

    Route::get('/cashier/vouchers/{voucher}/certificate', [\App\Http\Controllers\Cashier\VoucherController::class, 'downloadCertificate'])
        ->name('cashier.vouchers.certificate');

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
