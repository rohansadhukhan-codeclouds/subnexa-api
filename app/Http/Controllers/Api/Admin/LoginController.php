<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminJwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __construct(private readonly AdminJwtService $jwt)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! $user->is_admin || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match an admin account.',
            ]);
        }

        $token = $this->jwt->issue($user);

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => max(1, (int) config('jwt.ttl', 720)) * 60,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
