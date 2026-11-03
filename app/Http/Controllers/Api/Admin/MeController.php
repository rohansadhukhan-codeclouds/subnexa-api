<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'name' => $user?->name,
            'email' => $user?->email,
            'is_admin' => (bool) $user?->is_admin,
        ]);
    }
}
