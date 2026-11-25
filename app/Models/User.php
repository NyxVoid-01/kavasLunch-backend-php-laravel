<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Datos personales cifrados en la base (RNF-004):
 *  - full_name y email se guardan cifrados con AES-256 (cast "encrypted", clave APP_KEY).
 *  - email_hash y full_name_hash son índices ciegos (HMAC-SHA256) para buscar y evitar
 *    duplicados sin descifrar. Si cambia APP_KEY, los datos dejan de poder leerse.
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasUuids;

    protected $fillable = [
        'full_name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_hash',
        'full_name_hash',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'email' => 'encrypted',
            'full_name' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        // Los índices ciegos siempre acompañan al valor cifrado
        static::saving(function (User $user) {
            $user->email_hash = self::emailIndex((string) $user->email);
            $user->full_name_hash = self::nameIndex((string) $user->full_name);
        });
    }

    // Correo normalizado (minúsculas, sin espacios) → HMAC
    public static function emailIndex(string $email): string
    {
        return self::blindIndex(mb_strtolower(trim($email)));
    }

    // Nombre normalizado (minúsculas, espacios simples) → HMAC: "  ANA   Pérez " = "ana pérez"
    public static function nameIndex(string $name): string
    {
        return self::blindIndex(mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name))));
    }

    private static function blindIndex(string $value): string
    {
        $key = hash('sha256', 'kavas-lunch:blind-index:' . config('app.key'), true);

        return hash_hmac('sha256', $value, $key);
    }

    public function scopeWithEmail(Builder $query, string $email): Builder
    {
        return $query->where('email_hash', self::emailIndex($email));
    }

    public function scopeWithFullName(Builder $query, string $name): Builder
    {
        return $query->where('full_name_hash', self::nameIndex($name));
    }

    public static function findByEmail(string $email): ?self
    {
        return self::withEmail($email)->first();
    }

    // La tabla de tokens de recuperación guarda el índice ciego, nunca el correo en claro
    public function getEmailForPasswordReset()
    {
        return $this->email_hash;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
