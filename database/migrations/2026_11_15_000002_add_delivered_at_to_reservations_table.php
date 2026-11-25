<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Hora en que se entregó el pedido (RF-013). El modelo y la API ya la usaban,
            // pero ninguna migración la creaba, así que entregar un pedido fallaba.
            $table->timestamp('delivered_at')->nullable()->after('expired_at');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('delivered_at');
        });
    }
};
