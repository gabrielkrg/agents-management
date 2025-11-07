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
            'files' => 'nullable|array',
        ]);

        $prompt->increment('count_usage');

        $requestData = [
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
        ];

        if ($prompt->json_schema) {
            $json_schema_config = [
                'response_mime_type' => 'application/json',
                'response_schema' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => json_decode($prompt->json_schema, true),
                        'propertyOrdering' => array_keys(json_decode($prompt->json_schema, true))
                    ]
                ]
            ];

            $requestData['generation_config'] = $json_schema_config;
        }

        $response = Http::withHeaders([
            'x-goog-api-key' => env('GEMINI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent', $requestData);

        $responseData = $response->json();

        $responseData = $responseData['candidates'][0]['content']['parts'][0]['text'];

        if ($prompt->json_schema) {
            $parsedData = json_decode($responseData, true);
        } else {
            $parsedData = $responseData;
        }

        return response()->json($parsedData);
    }

    public function generateContent(Prompt $prompt, Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:10000',
            'files.*' => 'required|file|max:10240',
        ]);

        // load chats from prompt
        $chats = [];
        foreach ($prompt->chats as $chat) {
            $chats[] = [
                'role' => $chat->role,
                'parts' => [
                    [
                        'text' => $chat->text
                    ]
                ]
            ];
        }

        // add files to request to the last chat
        $files = $request->file('files');
        if ($files) {
            $inlineData = [];

            foreach ($files as $file) {
                $fileContent = $file->get();
                $base64File = base64_encode($fileContent);

                $inlineData[] = [
                    'inline_data' => [
                        'mime_type' => $file->getMimeType(),
                        'data' => $base64File
                    ]
                ];
            }

            $lastIndex = count($chats) - 1;

            $chats[$lastIndex]['parts'][] = $inlineData;
        }

        $requestData = [
            'system_instruction' => [
                'parts' => [
                    [
                        'text' => $prompt->description
                    ]
                ]
            ],
            'contents' => $chats
        ];


        // add json schema to request
        if ($prompt->json_schema) {
            $json_schema_config = [
                'response_mime_type' => 'application/json',
                'response_schema' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => json_decode($prompt->json_schema, true),
                        'propertyOrdering' => array_keys(json_decode($prompt->json_schema, true))
                    ]
                ]
            ];

            $requestData['generation_config'] = $json_schema_config;
        }

        try {
            $response = Http::withHeaders([
                'x-goog-api-key' => env('GEMINI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent', $requestData);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }

        $responseData = $response->json()['candidates'][0]['content']['parts'][0]['text'];

        if ($prompt->json_schema) {
            $parsedData = json_decode($responseData, true);
        } else {
            $parsedData = $responseData;
        }

        $prompt->chats()->create([
            'role' => 'model',
            'text' => $responseData,
            'prompt_id' => $prompt->id,
        ]);

        $prompt->increment('count_usage');

        return response()->json($parsedData);
    }
}
