<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_menus', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->uuid('appetizer_id')->nullable();
            $table->uuid('main_dish_id')->nullable();
            $table->uuid('drink_id')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('appetizer_id')->references('id')->on('dishes')->onDelete('set null');
            $table->foreign('main_dish_id')->references('id')->on('dishes')->onDelete('set null');
            $table->foreign('drink_id')->references('id')->on('dishes')->onDelete('set null');
            
            $table->unique('date');
            $table->index('date');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_menus');
    }
};