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
     * Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Revoke old tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'balance' => $user->balance,
                'jokers_remaining' => $user->jokers_remaining,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Register
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'mobile' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'mobile' => $request->mobile,
            'balance' => 0,
            'jokers_remaining' => 3,
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'balance' => $user->balance,
                'jokers_remaining' => $user->jokers_remaining,
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('bets');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'is_admin' => $user->is_admin,
            'balance' => $user->balance,
            'jokers_remaining' => $user->jokers_remaining,
            'jokers_used' => $user->jokers_used ?? [],
            'wants_email_reminder' => $user->wants_email_reminder,
            'wants_sms_reminder' => $user->wants_sms_reminder,
            'bet_count' => $user->bets->count(),
        ]);
    }

    /**
     * Update user settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'mobile' => 'nullable|string',
            'wants_email_reminder' => 'sometimes|boolean',
            'wants_sms_reminder' => 'sometimes|boolean',
        ]);

        $user = $request->user();
        $user->update($request->only([
            'name',
            'mobile',
            'wants_email_reminder',
            'wants_sms_reminder',
        ]));

        return response()->json([
            'message' => 'Settings updated',
            'user' => $user,
        ]);
    }
}
