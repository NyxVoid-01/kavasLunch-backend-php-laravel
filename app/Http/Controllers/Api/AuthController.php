<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOCKOUT_SECONDS = 900; // 15 minutos


    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users|regex:/^[a-zA-Z0-9._%+-]+@upch\.pe$/',
            'password' => 'required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            'password_confirmation' => 'required|same:password',
        ], [
            'email.regex' => 'El correo debe ser del dominio @upch.pe',
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

        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id . '|regex:/^[a-zA-Z0-9._%+-]+@upch\.pe$/',
        ], [
            'email.regex' => 'El correo debe ser del dominio @upch.pe',
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