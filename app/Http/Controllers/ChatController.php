<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\Prompt;
use App\Models\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'prompt_id' => 'required|string|exists:prompts,uuid',
            'role' => 'required|in:user,model',
            'text' => 'required|string',
            'new_files.*' => 'nullable|file|max:10240|mimes:txt,pdf,doc,docx,jpg,jpeg,png',
            'existing_files' => 'nullable|array',
            'existing_files.*' => 'nullable|string|exists:files,id',
        ]);

        dd($validated);

        $prompt = Prompt::where('uuid', $validated['prompt_id'])
            ->where('user_id', auth()->id())
            ->firstOrFail();

        Chat::create($validated);

        // Sincronizar arquivos
        $this->syncFiles($prompt, $request);

        return redirect()->back()->with('success', 'Chat created successfully');
    }

    private function syncFiles(Prompt $prompt, Request $request)
    {
        // Obter IDs dos arquivos existentes que devem ser mantidos
        $existingFileIds = $request->input('existing_files', []);

        // Obter arquivos atualmente associados ao prompt
        $currentFiles = $prompt->files()->pluck('id')->toArray();

        // Remover arquivos que não estão mais na lista
        $filesToDelete = array_diff($currentFiles, $existingFileIds);

        if (!empty($filesToDelete)) {
            $filesToDeleteData = File::whereIn('id', $filesToDelete)->get();

            foreach ($filesToDeleteData as $file) {
                // Remover arquivo físico do storage
                if (Storage::exists($file->path)) {
                    Storage::delete($file->path);
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
                    'path' => $file->store('chat_files'),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }
    }

    public function delete(Prompt $prompt): JsonResponse
    {
        $prompt->chats()->delete();

        return response()->json([
            'message' => 'Chats deleted successfully',
        ]);
    }
}
