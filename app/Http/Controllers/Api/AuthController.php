<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
{
    $credentials = $request->validated();

    $user = User::where('email', $credentials['email'])->first();

    if (! $user || ! Hash::check($credentials['password'], $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['Email atau password tidak valid.'],
        ]);
    }

    if ($user->status !== 'active') {
        throw ValidationException::withMessages([
            'email' => ['Akun belum aktif atau sedang diblokir.'],
        ]);
    }

    $tokenName = $credentials['device_name'] ?? 'simpb-api-token';

    $token = $user->createToken($tokenName, $this->abilitiesFor($user));

    $user->forceFill([
        'last_login_at' => now(),
    ])->save();

    return response()->json([
        'message' => 'Login berhasil.',
        'token_type' => 'Bearer',
        'access_token' => $token->plainTextToken,
        'user' => $this->formatUser($user->fresh()),
    ]);
}

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->formatUser($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    private function abilitiesFor(User $user): array
    {
        if ($user->hasRole('admin')) {
            return ['*'];
        }

        if ($user->hasRole('pustakawan')) {
            return [
                'catalog:read',
                'library:manage',
                'circulation:manage',
                'member:read',
            ];
        }

        if ($user->hasRole('kurator')) {
            return [
                'catalog:read',
                'museum:manage',
            ];
        }

        if ($user->hasRole('member')) {
            return [
                'catalog:read',
                'member:read',
            ];
        }

        return [
            'catalog:read',
        ];
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'user_type' => $user->user_type,
            'unit' => $user->unit,
            'status' => $user->status,
            'member_number' => $user->member_number,
            'member_category' => $user->member_category,
            'membership_status' => $user->membership_status,
            'member_active_until' => $user->member_active_until,
            'roles' => $user->roles->pluck('name')->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
        ];
    }
}