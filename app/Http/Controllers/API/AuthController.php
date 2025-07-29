<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $credentials = $request->only('email', 'password');

            if (!Auth::guard('web')->attempt($credentials)) {
                return response()->json([
                    'message' => 'Email atau password salah',
                    'data' => null,
                ], 401);
            }

            $user = Auth::guard('web')->user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login berhasil',
                'data' => [
                    'token' => $token,
                    'user' => $user,
                ],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan server',
                'error' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function register(Request $request)
    {
        try {
            $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|unique:users,email',
                'password' => 'required|min:6|confirmed',
            ]);

            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => bcrypt($request->password),
                'role'     => 'user',
                'email_verified_at' => now(), 
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Registrasi berhasil',
                'data'    => [
                    'token' => $token,
                    'user'  => $user,
                ]
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat registrasi',
                'error'   => $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Logout berhasil',
                'data' => null,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat logout',
                'error' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function me(Request $request)
    {
        return response()->json([
            'message' => 'Berhasil mengambil data user',
            'data' => $request->user(),
        ]);
    }
}
