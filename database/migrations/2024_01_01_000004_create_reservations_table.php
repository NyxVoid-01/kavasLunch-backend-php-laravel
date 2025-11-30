<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('menu_id');
            $table->date('reservation_date');
            $table->enum('status', ['pending', 'delivered', 'cancelled'])->default('pending');
            $table->string('code')->unique();
            $table->enum('payment_method', ['yape', 'plin', 'credit_card', 'cash'])->default('cash');
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('menu_id')->references('id')->on('daily_menus')->onDelete('cascade');
            
            $table->unique(['user_id', 'reservation_date']);
            $table->index('user_id');
            $table->index('menu_id');
            $table->index('status');
            $table->index('reservation_date');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};