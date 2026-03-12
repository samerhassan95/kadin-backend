<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class MockController extends Controller
{
    public function settings(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => [
                'title' => 'Kadin Marketplace',
                'description' => 'Your trusted marketplace',
                'currency' => [
                    'id' => 1,
                    'title' => 'Egyptian Pound',
                    'symbol' => 'EGP',
                    'rate' => 1
                ]
            ]
        ]);
    }

    public function languages(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => [
                [
                    'id' => 1,
                    'title' => 'العربية',
                    'locale' => 'ar',
                    'backward' => 1,
                    'default' => 1,
                    'active' => 1
                ],
                [
                    'id' => 2,
                    'title' => 'English',
                    'locale' => 'en',
                    'backward' => 0,
                    'default' => 0,
                    'active' => 1
                ]
            ]
        ]);
    }

    public function currencies(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => [
                [
                    'id' => 1,
                    'title' => 'Egyptian Pound',
                    'symbol' => 'EGP',
                    'rate' => 1,
                    'default' => 1,
                    'active' => 1
                ]
            ]
        ]);
    }

    public function categories(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => [
                [
                    'id' => 1,
                    'uuid' => 'cat-1',
                    'keywords' => 'electronics',
                    'parent_id' => null,
                    'type' => 'main',
                    'img' => '',
                    'active' => 1,
                    'translation' => [
                        'title' => 'Electronics',
                        'description' => 'Electronic devices and gadgets'
                    ]
                ]
            ]
        ]);
    }
}