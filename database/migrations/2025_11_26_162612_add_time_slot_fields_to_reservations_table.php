<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Time slot asignado para recoger el pedido
            $table->dateTime('pickup_time_slot')->nullable()->after('reservation_date');
            
            // Ruta del código QR generado
            $table->string('qr_code_path')->nullable()->after('code');
            
            // Timestamp de expiración (15 min después del time slot)
            $table->dateTime('expired_at')->nullable()->after('status');
            
            // Agregar estado 'expired' al enum
            $table->dropColumn('status');
        });
        
        // Recrear columna status con nuevo valor
        Schema::table('reservations', function (Blueprint $table) {
            $table->enum('status', ['pending', 'delivered', 'cancelled', 'expired'])
                ->default('pending')
                ->after('menu_id');
        });
        
        // Agregar índices para optimizar consultas
        Schema::table('reservations', function (Blueprint $table) {
            $table->index('pickup_time_slot');
            $table->index('expired_at');
            $table->index(['reservation_date', 'pickup_time_slot']);
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['reservations_pickup_time_slot_index']);
            $table->dropIndex(['reservations_expired_at_index']);
            $table->dropIndex(['reservations_reservation_date_pickup_time_slot_index']);
            
            $table->dropColumn(['pickup_time_slot', 'qr_code_path', 'expired_at']);
            
            // Restaurar enum original
            $table->dropColumn('status');
        });
        
        Schema::table('reservations', function (Blueprint $table) {
            $table->enum('status', ['pending', 'delivered', 'cancelled'])
                ->default('pending')
                ->after('menu_id');
        });
    }
};
