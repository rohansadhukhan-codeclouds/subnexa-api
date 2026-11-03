<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LogoutController extends Controller
{
    /**
     * Stateless JWT logout — client must discard the bearer token.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'Signed out. Discard the bearer token on the client.',
        ]);
    }
}
