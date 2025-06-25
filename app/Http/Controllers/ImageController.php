<?php
namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImageController extends Controller
{
    public function store(Request $request) 
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
    
        $path = $request->file('image')->store('images', 'public');
        
        $image = Image::create([
            'path' => $path,
            'user_id' => Auth::id(),
        ]);
    
        // إرسال الصورة لنموذج ML
        $mlResponse = $this->sendToMLModel($request->file('image'));
    
        return response()->json([
            'message' => 'Image uploaded successfully',
            'image' => $image,
            'ml_analysis' => $mlResponse,
        ], 201);
    }

    public function show(Image $image)
    {
        return response()->file(storage_path('app/public/' . $image->path));
    }

    public function update(Request $request, Image $image)
    {
        $this->authorize('update', $image);

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif',
        ]);

        
        Storage::disk('public')->delete($image->path);
        $path = $request->file('image')->store('images', 'public');    
        $image->update(['path' => $path]);

        return response()->json([
            'message' => 'Image updated successfully',
            'image' => $image,
        ]);
    }

    public function destroy(Image $image)
    {
        $this->authorize('delete', $image);

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return response()->json(['message' => 'Image deleted successfully']);
    }

    protected function sendToMLModel($imageFile)
    {
        $endpoint = 'https://deepface-api-main-production-5d54.up.railway.app/analyze/';
        
        try {
            
            $response = Http::withoutVerifying()
                ->timeout(30)
                ->attach(
                    'file', 
                    file_get_contents($imageFile->getRealPath()),
                    $imageFile->getClientOriginalName()
                )
                ->post($endpoint);
    
            
            if ($response->status() == 404) {
                $response = Http::withoutVerifying()
                    ->timeout(30)
                    ->post($endpoint, [
                        'image' => base64_encode(file_get_contents($imageFile->getRealPath())),
                        'filename' => $imageFile->getClientOriginalName()
                    ]);
            }
    
            Log::debug('ML API Full Response:', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body()
            ]);
    
            if ($response->successful()) {
                return $response->json();
            }
    
            throw new \Exception('ML API request failed with status: '.$response->status());
            
        } catch (\Exception $e) {
            Log::error('ML Model Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'error' => true,
                'message' => 'ML processing failed',
                'details' => $e->getMessage()
            ];
        }
    }
}