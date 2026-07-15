<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PersonalAccessTokenController extends Controller
{
    /**
     * List all access tokens.
     */
    public function index(Request $request)
    {
        return response()->json($request->user()->tokens);
    }

    /**
     * Create a new access token (API Key).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $token = $request->user()->createToken($request->name);

        return response()->json([
            'plain_text_token' => $token->plainTextToken,
            'token' => $token->accessToken,
        ], Response::HTTP_CREATED);
    }

    /**
     * Revoke a specific token.
     */
    public function destroy(Request $request, $id)
    {
        $request->user()->tokens()->where('id', $id)->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
