<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Prompt;
use App\Services\GeminiService;

class AiController extends Controller
{
    public function index(Prompt $prompt, Request $request, GeminiService $geminiService)
    {
        $request->validate([
            'content' => 'required|string:max:1000',
            'files' => 'nullable|array',
        ]);

        try {
            // Usa useChats = false pois este método não usa os chats do prompt
            $result = $geminiService->generateContent($prompt, $request, false);

            $prompt->increment('count_usage');

            return response()->json($result['parsed']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generateContent(Prompt $prompt, Request $request, GeminiService $geminiService)
    {
        $request->validate([
            'content' => 'required|string|max:10000',
            'files.*' => 'required|file|max:10240',
        ]);

        try {
            $result = $geminiService->generateContent($prompt, $request, true);

            $prompt->chats()->create([
                'role' => 'model',
                'text' => $result['raw'],
                'prompt_id' => $prompt->id,
            ]);

            $prompt->increment('count_usage');

            return response()->json($result['parsed']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
