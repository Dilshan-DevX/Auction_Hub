<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/login
     * Issues a Sanctum token with abilities based on the user's role.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Determine token abilities based on user role
        $abilities = match ($user->role) {
            'admin'  => ['admin:*'],
            'vendor' => ['auction:manage'],
            'bidder' => ['bid:place'],
            default  => [],
        };

        $token = $user->createToken('api-token', $abilities);

        return response()->json([
            'data' => [
                'token'     => $token->plainTextToken,
                'type'      => 'Bearer',
                'abilities' => $abilities,
                'user'      => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ],
            ],
        ], 200);
    }

    /**
     * POST /api/logout
     * Revokes the CURRENT token only (not all user tokens).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Token revoked successfully.',
        ], 200);
    }
}
