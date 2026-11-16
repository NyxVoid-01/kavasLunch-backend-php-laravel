<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RNF-004: cifra el nombre y el correo de los usuarios existentes y agrega sus índices ciegos.
 * Idempotente: si un valor ya está cifrado, no lo vuelve a cifrar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_unique');
            $table->dropIndex('users_email_index');
        });

        Schema::table('users', function (Blueprint $table) {
            // El texto cifrado es más largo que 255 caracteres
            $table->text('email')->change();
            $table->text('full_name')->change();
            $table->string('email_hash', 64)->nullable();
            $table->string('full_name_hash', 64)->nullable();
        });

        DB::table('users')->orderBy('id')->each(function ($row) {
            $email = mb_strtolower(trim($this->plain($row->email)));
            $name = trim(preg_replace('/\s+/u', ' ', $this->plain($row->full_name)));

            DB::table('users')->where('id', $row->id)->update([
                'email' => Crypt::encryptString($email),
                'full_name' => Crypt::encryptString($name),
                'email_hash' => User::emailIndex($email),
                'full_name_hash' => User::nameIndex($name),
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email_hash', 64)->nullable(false)->change();
            $table->string('full_name_hash', 64)->nullable(false)->change();
            $table->unique('email_hash');
            // Sin unique: ya existían nombres repetidos; la regla se valida al registrar y editar
            $table->index('full_name_hash');
        });

        // Los tokens de recuperación pendientes tenían el correo en claro
        DB::table('password_reset_tokens')->delete();
    }

    public function down(): void
    {
        DB::table('users')->orderBy('id')->each(function ($row) {
            DB::table('users')->where('id', $row->id)->update([
                'email' => $this->plain($row->email),
                'full_name' => $this->plain($row->full_name),
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email_hash']);
            $table->dropIndex(['full_name_hash']);
            $table->dropColumn(['email_hash', 'full_name_hash']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->change();
            $table->string('full_name')->change();
            $table->unique('email');
            $table->index('email');
        });

        DB::table('password_reset_tokens')->delete();
    }

    // Devuelve el valor en claro, esté o no cifrado
    private function plain(string $value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }
};
