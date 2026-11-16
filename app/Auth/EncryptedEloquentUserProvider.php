<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;

/**
 * El correo se guarda cifrado (RNF-004), así que no se puede buscar por su valor.
 * Este proveedor traduce la búsqueda por correo (login y recuperación de contraseña)
 * a su índice ciego (HMAC), que sí es comparable.
 */
class EncryptedEloquentUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials)
    {
        if (array_key_exists('email', $credentials)) {
            $credentials['email_hash'] = User::emailIndex((string) $credentials['email']);
            unset($credentials['email']);
        }

        return parent::retrieveByCredentials($credentials);
    }
}
