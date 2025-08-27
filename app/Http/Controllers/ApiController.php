<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    /**
     * Test endpoint to verify API is working
     */
    public function test(): JsonResponse
    {
        return response()->json([
            'message' => 'Laravel API is working!',
            'status' => 'success',
            'timestamp' => now()
        ]);
    }

    /**
     * Get all services
     */
    public function getServices(): JsonResponse
    {
        // This will be replaced with actual database queries
        $services = [
            [
                'id' => 1,
                'name' => 'Fadded VIP 🔆 Basic Service',
                'description' => 'Basic service package',
                'price' => 29.99,
                'category' => 'basic'
            ],
            [
                'id' => 2,
                'name' => 'Fadded VIP 🔆 Premium Service',
                'description' => 'Premium service package',
                'price' => 59.99,
                'category' => 'premium'
            ],
            [
                'id' => 3,
                'name' => 'Fadded VIP 🔆 Enterprise Service',
                'description' => 'Enterprise service package',
                'price' => 99.99,
                'category' => 'enterprise'
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $services
        ]);
    }

    /**
     * Create a new service
     */
    public function createService(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string|max:100'
        ]);

        // Here you would typically save to database
        // For now, we'll just return the validated data
        return response()->json([
            'status' => 'success',
            'message' => 'Service created successfully',
            'data' => $validated
        ], 201);
    }

    /**
     * Update a service
     */
    public function updateService(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'category' => 'sometimes|string|max:100'
        ]);

        // Here you would typically update the database
        return response()->json([
            'status' => 'success',
            'message' => 'Service updated successfully',
            'data' => array_merge(['id' => $id], $validated)
        ]);
    }

    /**
     * Delete a service
     */
    public function deleteService($id): JsonResponse
    {
        // Here you would typically delete from database
        return response()->json([
            'status' => 'success',
            'message' => 'Service deleted successfully',
            'id' => $id
        ]);
    }
}
