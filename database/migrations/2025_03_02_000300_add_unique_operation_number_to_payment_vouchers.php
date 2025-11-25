<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        // Los índices se gestionan ahora directamente en la migración principal de vouchers.
    }

    public function down(): void
    {
        // No hay cambios que revertir.
    }
};
