<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Prompt;

class AiController extends Controller
{
    public function index(Prompt $prompt, Request $request)
    {
        $request->validate([
            'content' => 'required|string:max:1000',
        ]);

        if ($request->debug) {
            return response()->json([
                'message' => 'debug',
                'prompt' => $prompt->description,
                'data' => $request->content,
            ]);
        }

        $response = Http::withHeaders([
            'x-goog-api-key' => env('GEMINI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent', [
            'system_instruction' => [
                'parts' => [
                    [
                        'text' => $prompt->description
                    ]
                ]
            ],
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $request->content
                        ]
                    ]
                ]
            ]
        ]);

        if ($request->debug) {
            return response()->json([
                'message' => 'debug',
                'prompt' => $prompt->description,
                'data' => $request->content,
                'response' => $response->json(),
            ]);
        }

        $responseData = $response->json();
        $aiResponse = $responseData['candidates'][0]['content']['parts'][0]['text'];

        // return response()->json($aiResponse);

        $jsonString = preg_replace('/```json\s*|\s*```/', '', $aiResponse);
        $parsedData = json_decode($jsonString, true);

        return response()->json($parsedData);
    }
}
