<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\Prompt;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'prompt_id' => 'required|string|exists:prompts,uuid',
            'role' => 'required|in:user,model',
            'text' => 'required|string',
        ]);

        Prompt::where('uuid', $validated['prompt_id'])
            ->where('user_id', auth()->id())
            ->firstOrFail();

        Chat::create($validated);

        return redirect()->back()->with('success', 'Chat created successfully');
    }

    public function delete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt_id' => 'required|string|exists:prompts,uuid',
        ]);

        // Verificar se o prompt pertence ao usuário autenticado
        $prompt = Prompt::where('uuid', $validated['prompt_id'])
            ->where('user_id', auth()->id())
            ->firstOrFail();

        Chat::where('prompt_id', $validated['prompt_id'])->delete();

        return response()->json([
            'message' => 'Chats deleted successfully',
        ]);
    }
}
