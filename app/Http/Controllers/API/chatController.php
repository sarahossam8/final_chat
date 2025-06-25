<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\chat;

class chatController extends Controller
{
    public function index()
    {
        $texts = chat::all();
        return response()->json(['texts' => $texts], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
        ]);      

        $mlResponse = $this->sendToMLModel($request->text);

        $text = chat::create([
            'text' => $request->text,
            'users_id' => $request->user()->id, 
            'output_text' => json_encode($mlResponse), 
        ]);

        if (!$text) {
            return response()->json(['message' => 'Failed to create text'], 500);
        }

        return response()->json([
            'message' => 'Text created successfully',
            'data' => [
                'id' => $text->id,
                'text' => $text->text,
                'ml_response' => $mlResponse,
                'user_id' => $text->users_id
            ]
        ], 201);
    }

    public function show(string $id)
    {
        $texts = chat::where('users_id', $id)->get();
        
        if ($texts->isEmpty()) {
            return response()->json(['message' => 'No texts found for this user'], 404);
        }

        return response()->json([
            'message' => 'Texts retrieved successfully',
            'data' => $texts->map(function($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->text,
                    'ml_response' => json_decode($item->output_text, true),
                    'created_at' => $item->created_at
                ];
            })
        ], 200);
    }

    public function update(Request $request, string $user_id, string $text_id)
    {
        $request->validate([
            'text' => 'sometimes|string'
        ]);

        $text = chat::where('id', $text_id)
                    ->where('users_id', $user_id)
                    ->first();

        if (!$text) {
            return response()->json(['message' => 'Text not found'], 404);
        }

        $updateData = ['is_edited' => true];
        
        if ($request->has('text')) {
            $updateData['text'] = $request->text;
        }
        

        $text->update($updateData);

        return response()->json([
            'message' => 'Text updated successfully',
            'data' => $text
        ], 200);
    }

    public function destroy(string $user_id, string $text_id)
    {
        $text = chat::where('id', $text_id)
                    ->where('users_id', $user_id)
                    ->first();

        if (!$text) {
            return response()->json(['message' => 'Text not found'], 404);
        }

        $text->delete();

        return response()->json([
            'message' => 'Text deleted successfully',
            'id' => $text_id
        ], 200);
    }

   protected function sendToMLModel($text)
{
    try {
        $response = Http::withoutVerifying() 
            ->timeout(30)
            ->post('https://web-production-f73d.up.railway.app/chat', [
                'message' => $text,
            ]);

        Log::debug('ML API Raw Response:', [
            'status' => $response->status(),
            'body' => $response->body(),
            'json' => $response->json()
        ]);

        if ($response->successful()) {
            $responseData = $response->json();
            
            // تعديل الشرط ليتناسب مع الهيكل الفعلي
            if (!empty($responseData)) {
                return $responseData; 
            }
            
            throw new \Exception('Empty ML response');
        }

        throw new \Exception('ML API request failed with status: '.$response->status());
        
    } catch (\Exception $e) {
        Log::error('ML Model Error:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'error' => true,
            'message' => 'Sorry, an error occurred while processing your request.',
            'details' => $e->getMessage()
        ];
    }
}
}
