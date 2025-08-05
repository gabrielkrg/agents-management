<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Prompt;
use Illuminate\Http\JsonResponse;
use App\Models\File;
use Illuminate\Support\Facades\Storage;

class PromptController extends Controller
{
    public function index(): JsonResponse
    {
        $prompts = auth()->user()->prompts;

        return response()->json($prompts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'json_schema' => 'nullable|json',
        ]);

        Prompt::create(array_merge($validated, [
            'user_id' => auth()->user()->id,
        ]));

        return redirect()->back()->with('success', 'Prompt created successfully');
    }

    public function update(Request $request, Prompt $prompt)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'json_schema' => 'nullable|json',
        ]);

        $prompt->update($validated);

        return redirect()->back()->with('success', 'Prompt updated successfully');
    }

    public function destroy(Prompt $prompt)
    {
        $prompt->delete();

        return redirect()->back()->with('success', 'Prompt deleted successfully');
    }

    public function getPrompt(Prompt $prompt)
    {
        if ($prompt->user_id !== auth()->user()->id) {
            return response()->json([
                'message' => 'Prompt not found',
            ], 404);
        }

        return response()->json([
            'message' => 'success',
            'name' => $prompt->name,
            'description' => $prompt->description,
            'json_schema' => $prompt->json_schema,
            'files' => $prompt->files()->get(),
        ]);
    }

    public function getChats(Prompt $prompt): JsonResponse
    {
        if ($prompt->user_id !== auth()->user()->id) {
            return response()->json([
                'message' => 'Prompt not found',
            ], 404);
        }

        $chats = $prompt->chats()->orderBy('created_at', 'asc')->get();

        return response()->json([
            'message' => 'success',
            'chats' => $chats,
        ]);
    }

    public function attachFiles(Request $request, Prompt $prompt)
    {
        $request->validate([
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:pdf,doc,docx,txt,csv,xls,xlsx,ppt,pptx|max:2048',
            'existing_files' => 'nullable|array',
            'existing_files.*' => 'string',
        ]);

        // Obter arquivos existentes do prompt
        $existingFiles = $prompt->files()->pluck('id')->toArray();

        // Obter IDs dos arquivos que devem permanecer (enviados pelo frontend)
        $filesToKeep = $request->input('existing_files', []);

        // Encontrar arquivos que devem ser removidos
        $filesToRemove = array_diff($existingFiles, $filesToKeep);

        // Remover arquivos deletados do storage e do banco
        foreach ($filesToRemove as $fileId) {
            $file = $prompt->files()->find($fileId);
            if ($file) {
                // Remover arquivo do storage
                if (Storage::disk('public')->exists($file->path)) {
                    Storage::disk('public')->delete($file->path);
                }
                // Remover registro do banco
                $file->delete();
            }
        }

        // Adicionar novos arquivos
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $prompt->files()->create([
                    'name' => $file->getClientOriginalName(),
                    'path' => $file->store('agents', 'public'), // Especificar o disk 'public'
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Files updated successfully',
            'files' => $prompt->files()->get()
        ]);
    }
}
