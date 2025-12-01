<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Get registration options
     */
    public function registerOptions()
    {
        return response()->json([
            'berhasil' => true,
            'data' => [
                'kelas' => ['X', 'XI', 'XII'],
                'jurusan' => ['MPLB', 'RPL', 'PM', 'TKJ', 'AKL']
            ],
            'pesan' => 'Opsi registrasi berhasil diambil'
        ]);
    }

    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:users|regex:/^[a-zA-Z0-9_]+$/',
            'name' => 'required|string|max:255',
            'telepon' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:guru,siswa',
            'kelas' => 'required_if:role,siswa|nullable|in:X,XI,XII',
            'jurusan' => 'required_if:role,siswa|nullable|in:MPLB,RPL,PM,TKJ,AKL'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'berhasil' => false,
                'pesan' => $validator->errors()->first()
            ], 400);
        }

        $user = User::create([
            'username' => $request->username,
            'name' => $request->name,
            'telepon' => $request->telepon,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'kelas' => $request->kelas,
            'jurusan' => $request->jurusan,
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'berhasil' => true,
            'data' => [
                'token' => $token,
                'pengguna' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'telepon' => $user->telepon,
                    'role' => $user->role,
                    'kelas' => $user->kelas,
                    'jurusan' => $user->jurusan,
                    'dibuat_pada' => $user->created_at->toISOString(),
                    'diperbarui_pada' => $user->updated_at->toISOString()
                ]
            ],
            'pesan' => 'Registrasi berhasil'
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'berhasil' => false,
                'pesan' => $validator->errors()->first()
            ], 400);
        }

        $credentials = [
            'username' => $request->username,
            'password' => $request->password
        ];

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Username atau password salah'
            ], 401);
        }

        $user = JWTAuth::user();

        return response()->json([
            'berhasil' => true,
            'data' => [
                'token' => $token,
                'pengguna' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'telepon' => $user->telepon,
                    'role' => $user->role,
                    'kelas' => $user->kelas,
                    'jurusan' => $user->jurusan,
                    'dibuat_pada' => $user->created_at->toISOString(),
                    'diperbarui_pada' => $user->updated_at->toISOString()
                ]
            ],
            'pesan' => 'Login berhasil'
        ]);
    }

    /**
     * Logout user
     */
    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'berhasil' => true,
            'pesan' => 'Logout berhasil'
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me()
    {
        $user = JWTAuth::user();

        return response()->json([
            'berhasil' => true,
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'telepon' => $user->telepon,
                'role' => $user->role,
                'kelas' => $user->kelas,
                'jurusan' => $user->jurusan,
                'dibuat_pada' => $user->created_at->toISOString(),
                'diperbarui_pada' => $user->updated_at->toISOString()
            ]
        ]);
    }
}
