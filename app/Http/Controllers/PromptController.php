<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Prompt;
use Illuminate\Http\JsonResponse;

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
        ]);
    }
}
