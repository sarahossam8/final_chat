<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\API\note1; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotesController extends Controller
{
    public function index()
    {
        $texts = note1::all();
        return response()->json(['texts' => $texts], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
            'title' => 'required|string'
        ]);      

        $mlResponse = $this->sendToMLModel($request->text);

        $text = note1::create([
            'text' => $request->text,
            'users_id' => $request->user()->id, 
            'output_text' => json_encode($mlResponse), 
            'title' => $request->title,
        ]);

        if (!$text) {
            return response()->json(['message' => 'Failed to create text'], 500);
        }

        return response()->json([
            'message' => 'Text created successfully',
            'data' => [
                'id' => $text->id,
                'title' => $text->title,
                'text' => $text->text,
                'ml_response' => $mlResponse,
                'user_id' => $text->users_id
            ]
        ], 201);
    }

    public function show(string $id)
    {
        $texts = note1::where('users_id', $id)->get();
        
        if ($texts->isEmpty()) {
            return response()->json(['message' => 'No texts found for this user'], 404);
        }

        return response()->json([
            'message' => 'Texts retrieved successfully',
            'data' => $texts->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
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
            'text' => 'sometimes|string',
            'title' => 'sometimes|string'
        ]);

        $text = note1::where('id', $text_id)
                    ->where('users_id', $user_id)
                    ->first();

        if (!$text) {
            return response()->json(['message' => 'Text not found'], 404);
        }

        $updateData = ['is_edited' => true];
        
        if ($request->has('text')) {
            $updateData['text'] = $request->text;
        }
        
        if ($request->has('title')) {
            $updateData['title'] = $request->title;
        }

        $text->update($updateData);

        return response()->json([
            'message' => 'Text updated successfully',
            'data' => $text
        ], 200);
    }

    public function destroy(string $user_id, string $text_id)
    {
        $text = note1::where('id', $text_id)
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
            ->post('https://web-production-edcec.up.railway.app/predict', [
                'text' => $text,
            ]);

        Log::debug('ML API Response:', $response->json());

        if ($response->successful()) {
            $responseData = $response->json();
            
            // عدّل الشرط ليتناسب مع رد الـ ML الحالي
            if (isset($responseData['predicted_class'])) {
                return [
                    'predicted_mood' => $responseData['predicted_class'], // يمكنك تعديل الحقل هنا إذا كنت تريد اسمًا مختلفًا
                    'confidence' => $responseData['confidence']
                ];
            }
            
            throw new \Exception('Invalid ML response structure: ' . json_encode($responseData));
        }

        throw new \Exception('ML API request failed with status: ' . $response->status());
        
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