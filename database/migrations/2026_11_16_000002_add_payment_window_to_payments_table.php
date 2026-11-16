<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF-014: la solicitud de pago (Yape/Plin o tarjeta) se acepta solo dentro de los
 * 3 minutos siguientes a su generación; paid_at registra cuándo se confirmó el pago.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('transaction_code');
            $table->timestamp('paid_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['expires_at', 'paid_at']);
        });
    }
};
