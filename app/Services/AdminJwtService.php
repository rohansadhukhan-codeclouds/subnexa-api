<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

class AdminJwtService
{
    public function issue(User $user): string
    {
        if (! $user->is_admin) {
            throw new InvalidArgumentException('Only admin users may receive admin JWTs.');
        }

        $now = now()->getTimestamp();
        $ttlMinutes = max(1, (int) config('jwt.ttl', 720));

        $payload = [
            'iss' => (string) config('jwt.issuer'),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + ($ttlMinutes * 60),
            'sub' => (string) $user->getKey(),
            'email' => $user->email,
            'name' => $user->name,
            'is_admin' => true,
            'typ' => 'admin_access',
        ];

        return JWT::encode($payload, $this->secret(), $this->algo());
    }

    /**
     * @return array<string, mixed>
     */
    public function decode(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret(), $this->algo()));
        } catch (UnexpectedValueException $exception) {
            throw new RuntimeException('Invalid or expired admin token.', 0, $exception);
        }

        $payload = (array) $decoded;

        if (($payload['typ'] ?? null) !== 'admin_access' || ($payload['is_admin'] ?? false) !== true) {
            throw new RuntimeException('Token is not an admin access token.');
        }

        return $payload;
    }

    public function userFromToken(string $token): User
    {
        $payload = $this->decode($token);
        $userId = $payload['sub'] ?? null;

        if (! is_string($userId) && ! is_int($userId)) {
            throw new RuntimeException('Admin token is missing a subject.');
        }

        $user = User::query()->find($userId);

        if (! $user || ! $user->is_admin) {
            throw new RuntimeException('Admin account for this token was not found.');
        }

        return $user;
    }

    private function secret(): string
    {
        $secret = (string) config('jwt.secret');

        if ($secret === '') {
            throw new RuntimeException('JWT secret is not configured.');
        }

        // Laravel APP_KEY is often "base64:...." — use raw key material when present.
        if (str_starts_with($secret, 'base64:')) {
            $decoded = base64_decode(substr($secret, 7), true);

            if ($decoded === false || $decoded === '') {
                throw new RuntimeException('JWT secret base64 value is invalid.');
            }

            return $decoded;
        }

        return $secret;
    }

    private function algo(): string
    {
        return (string) config('jwt.algo', 'HS256');
    }
}
