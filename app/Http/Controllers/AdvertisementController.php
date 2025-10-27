<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AdvertisementController extends Controller
{
    /**
     * Get active advertisements for frontend
     */
    public function getActive(): JsonResponse
    {
        $advertisements = Advertisement::active()
            ->ordered()
            ->get()
            ->map(function ($ad) {
                return [
                    'id' => $ad->id,
                    'title' => $ad->title,
                    'description' => $ad->description,
                    'button_text' => $ad->button_text,
                    'button_url' => $ad->button_url,
                    'background_type' => $ad->background_type,
                    'background_color' => $ad->background_color,
                    'background_image' => $ad->background_image,
                    'text_color' => $ad->text_color,
                    'is_active' => $ad->is_active,
                    'is_featured' => $ad->is_featured,
                    'sort_order' => $ad->sort_order
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $advertisements
        ]);
    }

    /**
     * Get all advertisements for admin
     */
    public function index(): JsonResponse
    {
        $advertisements = Advertisement::ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $advertisements
        ]);
    }

    /**
     * Store a new advertisement
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'button_text' => 'required|string|max:50',
            'button_url' => 'required|url',
            'background_type' => 'required|in:color,image',
            'background_color' => 'required_if:background_type,color|string',
            'background_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'text_color' => 'required|string',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();

        // Handle image upload
        if ($request->hasFile('background_image')) {
            $imagePath = $request->file('background_image')->store('advertisements', 'public');
            $data['background_image'] = Storage::url($imagePath);
        }

        $advertisement = Advertisement::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Advertisement created successfully',
            'data' => $advertisement
        ], 201);
    }

    /**
     * Update an advertisement
     */
    public function update(Request $request, Advertisement $advertisement): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:500',
            'button_text' => 'sometimes|required|string|max:50',
            'button_url' => 'sometimes|required|string',
            'background_type' => 'sometimes|required|in:color,image',
            'background_color' => 'nullable|string',
            'background_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'text_color' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            \Log::error('Advertisement update validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();

        // Handle image upload
        if ($request->hasFile('background_image')) {
            // Delete old image if exists
            if ($advertisement->background_image) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $advertisement->background_image));
            }
            
            $imagePath = $request->file('background_image')->store('advertisements', 'public');
            $data['background_image'] = Storage::url($imagePath);
        }

        $advertisement->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Advertisement updated successfully',
            'data' => $advertisement
        ]);
    }

    /**
     * Delete an advertisement
     */
    public function destroy(Advertisement $advertisement): JsonResponse
    {
        // Delete associated image if exists
        if ($advertisement->background_image) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $advertisement->background_image));
        }

        $advertisement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Advertisement deleted successfully'
        ]);
    }

    /**
     * Toggle advertisement status
     */
    public function toggleStatus(Advertisement $advertisement): JsonResponse
    {
        $advertisement->update(['is_active' => !$advertisement->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Advertisement status updated successfully',
            'data' => $advertisement
        ]);
    }
}