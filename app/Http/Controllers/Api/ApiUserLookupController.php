<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiUserLookupController extends Controller
{
    public function lookup(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first(['id', 'name', 'email']);

        if (!$user) {
            return response()->json(['found' => false], 404);
        }

        return response()->json([
            'found' => true,
            'user' => $user,
        ]);
    }
}
