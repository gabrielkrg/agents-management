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
            ],
            'generation_config' => [
                'response_mime_type' => 'application/json',
                'response_schema' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'name' => ['type' => 'STRING'],
                            'description' => ['type' => 'STRING']
                        ],
                        'propertyOrdering' => ['name', 'description']
                    ]
                ]
            ]
        ]);

        $responseData = $response['candidates'][0]['content']['parts'][0]['text'];
        $parsedData = json_decode($responseData, true);

        return response()->json($parsedData);
    }
}
