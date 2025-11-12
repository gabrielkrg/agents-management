<?php

namespace App\Jobs;

use App\Models\Prompt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ProcessAiRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Prompt $prompt,
        public string $content,
        public ?array $jsonSchema = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): array|string
    {
        $requestData = [
            'system_instruction' => [
                'parts' => [
                    [
                        'text' => $this->prompt->description
                    ]
                ]
            ],
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $this->content
                        ]
                    ]
                ]
            ]
        ];

        if ($this->jsonSchema) {
            $json_schema_config = [
                'response_mime_type' => 'application/json',
                'response_schema' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => $this->jsonSchema,
                        'propertyOrdering' => array_keys($this->jsonSchema)
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

        if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \Exception('Invalid response from Gemini API');
        }

        $responseData = $responseData['candidates'][0]['content']['parts'][0]['text'];

        if ($this->jsonSchema) {
            $parsedData = json_decode($responseData, true);
        } else {
            $parsedData = $responseData;
        }

        return $parsedData;
    }
}
