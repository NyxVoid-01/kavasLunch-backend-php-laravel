<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Eliminar la restricción única existente
            $table->dropUnique(['user_id', 'reservation_date']);
        });

        // Crear un índice parcial que solo aplique a reservas activas (pending, delivered)
        // Esto permite tener múltiples reservas canceladas/expiradas para la misma fecha
        DB::statement("CREATE UNIQUE INDEX reservations_user_date_active_unique ON reservations (user_id, reservation_date) WHERE status IN ('pending', 'delivered')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar el índice parcial
        DB::statement("DROP INDEX IF EXISTS reservations_user_date_active_unique");

        Schema::table('reservations', function (Blueprint $table) {
            // Restaurar la restricción única original (esto fallará si hay duplicados)
            $table->unique(['user_id', 'reservation_date']);
        });
    }
};
