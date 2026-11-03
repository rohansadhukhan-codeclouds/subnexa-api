<?php

namespace App\Http\Middleware;

use App\Services\AdminJwtService;
use Closure;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminJwt
{
    public function __construct(private readonly AdminJwtService $jwt)
    {
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');

        if (! is_string($header) || ! preg_match('/^Bearer\s+(\S+)$/i', $header, $matches)) {
            return response()->json([
                'message' => 'Admin bearer token required.',
            ], 401);
        }

        try {
            $user = $this->jwt->userFromToken($matches[1]);
        } catch (RuntimeException) {
            return response()->json([
                'message' => 'Invalid or expired admin token.',
            ], 401);
        }

        $request->setUserResolver(fn () => $user);
        auth()->setUser($user);

        return $next($request);
    }
}
