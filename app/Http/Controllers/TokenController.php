<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    public function index(): JsonResponse
    {
        $tokens = auth()->user()->tokens;

        return response()->json($tokens);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $token = $request->user()->createToken($request->name);

        return response()->json($token->plainTextToken);
    }

    public function destroy(Request $request, string $id)
    {
        $request->user()->tokens()->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Token deleted successfully');
    }
}
