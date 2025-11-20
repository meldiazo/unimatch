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
        Schema::create('reconciliation_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('difference_alert_threshold', 10, 2)->default(1.00);
            $table->decimal('shortage_alert_threshold', 10, 2)->default(5.00);
            $table->decimal('credit_max_amount', 10, 2)->default(500.00);
            $table->timestamps();
        });

        DB::table('reconciliation_settings')->insert([
            'difference_alert_threshold' => 1.00,
            'shortage_alert_threshold' => 5.00,
            'credit_max_amount' => 500.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconciliation_settings');
    }
};
