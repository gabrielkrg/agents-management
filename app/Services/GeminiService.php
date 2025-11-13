<?php

namespace App\Services;

use App\Models\Prompt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeminiService
{
    /**
     * Gera conteúdo usando a API do Gemini
     *
     * @param Prompt $prompt
     * @param Request $request
     * @param bool $useChats Se true, usa os chats do prompt; se false, usa apenas o conteúdo da requisição
     * @return array{parsed: array|string, raw: string}
     * @throws \Exception
     */
    public function generateContent(Prompt $prompt, Request $request, bool $useChats = true): array
    {
        $chats = $useChats
            ? $this->prepareChats($prompt, $request)
            : $this->prepareContentFromRequest($request);

        $requestData = $this->buildRequestData($prompt, $chats);

        $response = $this->sendRequest($requestData);
        $responseData = $this->extractResponseData($response);

        return [
            'parsed' => $this->parseResponse($prompt, $responseData),
            'raw' => $responseData
        ];
    }

    /**
     * Prepara os chats a partir do prompt e adiciona arquivos se houver
     *
     * @param Prompt $prompt
     * @param Request $request
     * @return array
     */
    protected function prepareChats(Prompt $prompt, Request $request): array
    {
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

        // Adiciona arquivos ao último chat
        $files = $request->file('files');
        if ($files) {
            $inlineData = $this->processFiles($files);
            $lastIndex = count($chats) - 1;
            $chats[$lastIndex]['parts'][] = $inlineData;
        }

        return $chats;
    }

    /**
     * Prepara o conteúdo diretamente da requisição (sem usar chats do prompt)
     *
     * @param Request $request
     * @return array
     */
    protected function prepareContentFromRequest(Request $request): array
    {
        return [
            [
                'parts' => [
                    [
                        'text' => $request->content
                    ]
                ]
            ]
        ];
    }

    /**
     * Processa os arquivos e converte para formato inline_data
     *
     * @param array $files
     * @return array
     */
    protected function processFiles(array $files): array
    {
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

        return $inlineData;
    }

    /**
     * Constrói os dados da requisição
     *
     * @param Prompt $prompt
     * @param array $chats
     * @return array
     */
    protected function buildRequestData(Prompt $prompt, array $chats): array
    {
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

        // Adiciona configuração de JSON schema se houver
        if ($prompt->json_schema) {
            $requestData['generation_config'] = $this->buildJsonSchemaConfig($prompt);
        }

        return $requestData;
    }

    /**
     * Constrói a configuração de JSON schema
     *
     * @param Prompt $prompt
     * @return array
     */
    protected function buildJsonSchemaConfig(Prompt $prompt): array
    {
        // Se json_schema já é um array, usa diretamente; caso contrário, decodifica
        $schemaProperties = is_array($prompt->json_schema)
            ? $prompt->json_schema
            : json_decode($prompt->json_schema, true);

        return [
            'response_mime_type' => 'application/json',
            'response_schema' => [
                'type' => 'ARRAY',
                'items' => [
                    'type' => 'OBJECT',
                    'properties' => $schemaProperties,
                    'propertyOrdering' => array_keys($schemaProperties)
                ]
            ]
        ];
    }

    /**
     * Envia a requisição para a API do Gemini
     *
     * @param array $requestData
     * @return \Illuminate\Http\Client\Response
     * @throws \Exception
     */
    protected function sendRequest(array $requestData): \Illuminate\Http\Client\Response
    {
        try {
            return Http::withHeaders([
                'x-goog-api-key' => env('GEMINI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent', $requestData);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Extrai os dados da resposta
     *
     * @param \Illuminate\Http\Client\Response $response
     * @return string
     * @throws \Exception
     */
    protected function extractResponseData(\Illuminate\Http\Client\Response $response): string
    {
        $responseData = $response->json();

        if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \Exception('Invalid response from Gemini API');
        }

        return $responseData['candidates'][0]['content']['parts'][0]['text'];
    }

    /**
     * Parseia a resposta baseado no tipo de schema
     *
     * @param Prompt $prompt
     * @param string $responseData
     * @return array|string
     */
    protected function parseResponse(Prompt $prompt, string $responseData): array|string
    {
        if ($prompt->json_schema) {
            return json_decode($responseData, true);
        }

        return $responseData;
    }
}
