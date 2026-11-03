<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'segment' => 'admin',
            'status' => 'ok',
            'brand' => config('app.name', 'Subnexa'),
            'user' => [
                'name' => $request->user()?->name,
                'email' => $request->user()?->email,
            ],
        ]);
    }
}
