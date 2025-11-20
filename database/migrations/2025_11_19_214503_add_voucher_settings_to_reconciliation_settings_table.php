<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reconciliation_settings', function (Blueprint $table) {
            $table->json('voucher_statuses')->nullable()->after('credit_max_amount');
            $table->text('voucher_rules')->nullable()->after('voucher_statuses');
            $table->text('voucher_template_help')->nullable()->after('voucher_rules');
        });

        DB::table('reconciliation_settings')->update([
            'voucher_statuses' => json_encode(['recibido', 'validado', 'rechazado', 'demasía']),
            'voucher_rules' => 'Define cuándo aprobar o rechazar automáticamente un voucher y qué justificación mostrar.',
            'voucher_template_help' => 'Especifica el formato esperado: columnas obligatorias, orden preferido y recomendaciones para digitalizar el comprobante.',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reconciliation_settings', function (Blueprint $table) {
            $table->dropColumn(['voucher_statuses', 'voucher_rules', 'voucher_template_help']);
        });
    }
};
