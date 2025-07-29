<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        return response()->json([
            'message' => 'List user',
            'data' => User::all()
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|unique:users',
                'password' => 'required|min:6',
                'role'     => 'required|in:user,admin',
                'jabatan'  => 'required|string|max:100',
            ]);

            $nip = $this->generateUniqueNip();

            $user = User::create([
                'name'              => $request->name,
                'email'             => $request->email,
                'password'          => Hash::make($request->password),
                'role'              => $request->role,
                'jabatan'           => $request->jabatan,
                'nip'               => $nip,
                'email_verified_at' => now(),
            ]);

            return response()->json([
                'message' => 'User berhasil dibuat',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }


    public function show($id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'User tidak ditemukan'], 404);

        return response()->json(['message' => 'Detail user', 'data' => $user]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'User tidak ditemukan'], 404);

        $user->update([
            'name'     => $request->name ?? $user->name,
            'email'    => $request->email ?? $user->email,
            'role'     => $request->role ?? $user->role,
            'jabatan'  => $request->jabatan ?? $user->jabatan,
        ]);

        if ($request->password) {
            $user->password = Hash::make($request->password);
            $user->save();
        }

        return response()->json(['message' => 'User berhasil diperbarui', 'data' => $user]);
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'User tidak ditemukan'], 404);

        $user->delete();

        return response()->json(['message' => 'User berhasil dihapus']);
    }

    private function generateUniqueNip()
    {
        do {
            $nip = mt_rand(10000000, 99999999); // 8 digit
        } while (User::where('nip', $nip)->exists());

        return (string) $nip;
    }
}
