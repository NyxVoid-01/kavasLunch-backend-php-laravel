<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOCKOUT_SECONDS = 900; // 15 minutos

    // Cada estudiante es uno solo: se normaliza el nombre (espacios) y el correo (minúsculas) antes de validar
    private function normalizeIdentity(Request $request): void
    {
        $changes = [];
        if ($request->has('full_name') && is_string($request->full_name)) {
            $changes['full_name'] = trim(preg_replace('/\s+/u', ' ', $request->full_name));
        }
        if ($request->has('email') && is_string($request->email)) {
            $changes['email'] = mb_strtolower(trim($request->email));
        }
        $request->merge($changes);
    }

    // No se permiten nombres duplicados (sin distinguir mayúsculas ni espacios de más).
    // El nombre está cifrado: se compara su índice ciego.
    private function uniqueFullName(?string $ignoreId = null): \Closure
    {
        return function ($attribute, $value, $fail) use ($ignoreId) {
            $exists = User::withFullName((string) $value)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists();

            if ($exists) {
                $fail('Ya existe una cuenta con este nombre. Cada estudiante puede tener una sola cuenta.');
            }
        };
    }

    // No se permiten correos duplicados (el correo está cifrado: se compara su índice ciego)
    private function uniqueEmail(?string $ignoreId = null): \Closure
    {
        return function ($attribute, $value, $fail) use ($ignoreId) {
            $exists = User::withEmail((string) $value)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists();

            if ($exists) {
                $fail('Este correo ya está registrado.');
            }
        };
    }

    public function register(Request $request)
    {
        $this->normalizeIdentity($request);

        $validator = Validator::make($request->all(), [
            'full_name' => ['required', 'string', 'max:255', $this->uniqueFullName()],
            'email' => ['required', 'string', 'email', 'max:255', 'regex:/^[a-zA-Z0-9._%+-]+@upch\.pe$/', $this->uniqueEmail()],
            'password' => 'required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            'password_confirmation' => 'required|same:password',
        ], [
            'email.regex' => 'El correo debe ser del dominio @upch.pe',
            'email.unique' => 'Este correo ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.regex' => 'La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un carácter especial',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
        ]);

        $token = JWTAuth::fromUser($user);

        try {
            Mail::to($user->email)->send(new WelcomeMail($user));
        } catch (\Exception $e) {
            Log::error('Error sending welcome email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado exitosamente',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        // Bloqueo temporal: al 5.º intento fallido el acceso queda bloqueado 15 minutos
        $throttleKey = 'login:' . Str::lower($request->email) . '|' . $request->ip();
        $lockKey = $throttleKey . ':lock';

        if ($lockedUntil = Cache::get($lockKey)) {
            $minutes = max(1, (int) ceil(($lockedUntil - time()) / 60));

            return response()->json([
                'success' => false,
                'message' => "Demasiados intentos fallidos. Intenta nuevamente en {$minutes} " . ($minutes === 1 ? 'minuto' : 'minutos') . '.'
            ], 429);
        }

        if (!$token = JWTAuth::attempt($credentials)) {
            $attempts = RateLimiter::hit($throttleKey, self::LOCKOUT_SECONDS);

            if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
                Cache::put($lockKey, time() + self::LOCKOUT_SECONDS, self::LOCKOUT_SECONDS);
                RateLimiter::clear($throttleKey);

                return response()->json([
                    'success' => false,
                    'message' => 'Demasiados intentos fallidos. Tu acceso queda bloqueado por 15 minutos.'
                ], 429);
            }

            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        RateLimiter::clear($throttleKey);

        $user = auth('api')->user();

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ]
        ]);
    }

    /**
     * Solicitar enlace para restablecer la contraseña
     * POST /api/auth/forgot-password
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Misma respuesta exista o no la cuenta, para no revelar qué correos están registrados
        $response = response()->json([
            'success' => true,
            'message' => 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.'
        ]);

        $user = User::findByEmail($request->email);
        if (!$user) {
            return $response;
        }

        $broker = Password::broker();

        // Evita reenviar si ya se pidió un enlace hace menos de 60 segundos
        if ($broker->getRepository()->recentlyCreatedToken($user)) {
            return $response;
        }

        $token = $broker->createToken($user);
        $resetUrl = rtrim(config('app.frontend_url'), '/') . '/reset-password?' . http_build_query([
            'token' => $token,
            'email' => $user->email,
        ]);

        try {
            Mail::to($user->email)->send(
                new PasswordResetMail($user, $resetUrl, (int) config('auth.passwords.users.expire'))
            );
        } catch (\Exception $e) {
            Log::error('Error sending password reset email: ' . $e->getMessage());
        }

        return $response;
    }

    /**
     * Restablecer la contraseña con el token recibido por correo
     * POST /api/auth/reset-password
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            'password_confirmation' => 'required|same:password',
        ], [
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.regex' => 'La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un carácter especial',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // El broker valida el token y su vencimiento, y lo elimina al usarlo (un solo uso)
        $status = Password::reset(
            [
                'email' => Str::lower($request->email),
                'password' => $request->password,
                'password_confirmation' => $request->password_confirmation,
                'token' => $request->token,
            ],
            function (User $user, string $password) {
                $user->password = $password; // el cast 'hashed' del modelo la encripta
                $user->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'success' => false,
                'message' => 'El enlace no es válido o ya venció. Solicita uno nuevo.'
            ], 422);
        }

        // Una contraseña nueva levanta cualquier bloqueo por intentos fallidos
        RateLimiter::clear('login:' . Str::lower($request->email) . '|' . $request->ip());
        Cache::forget('login:' . Str::lower($request->email) . '|' . $request->ip() . ':lock');

        return response()->json([
            'success' => true,
            'message' => 'Tu contraseña fue actualizada. Ya puedes iniciar sesión.'
        ]);
    }

    public function logout()
    {
        auth('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente'
        ]);
    }

    public function refresh()
    {
        $token = auth('api')->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Token renovado exitosamente',
            'data' => [
                'user' => auth('api')->user(),
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ]
        ]);
    }

    public function profile()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => auth('api')->user()
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth('api')->user();

        $this->normalizeIdentity($request);

        $validator = Validator::make($request->all(), [
            'full_name' => ['sometimes', 'required', 'string', 'max:255', $this->uniqueFullName($user->id)],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', 'regex:/^[a-zA-Z0-9._%+-]+@upch\.pe$/', $this->uniqueEmail($user->id)],
        ], [
            'email.regex' => 'El correo debe ser del dominio @upch.pe',
            'email.unique' => 'Este correo ya está registrado.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only(['full_name', 'email']));

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado exitosamente',
            'data' => [
                'user' => $user
            ]
        ]);
    }
}