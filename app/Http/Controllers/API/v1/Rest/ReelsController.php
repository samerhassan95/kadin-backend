<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Models\Reel;
use App\Repositories\ReelsRepository\ReelsRepository;
use App\Services\LikeService\LikeService;
use Illuminate\Http\JsonResponse;

class ReelsController extends RestBaseController
{
    public function __construct(
        private ReelsRepository $repository,
        private LikeService $likeService
    ) {
        parent::__construct();
    }

    /**
     * Get paginated reels
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function paginate(FilterParamsRequest $request): JsonResponse
    {
        try {
            $userId = auth('sanctum')->id();
            $shopId = $request->input('shop_id');
            $productId = $request->input('product_id');
            $lang = $request->input('lang', 'en');
            
            $reels = \App\Models\Reel::with([
                'shop', 
                'shop.translation' => function($q) use ($lang) {
                    $q->where('locale', $lang);
                },
                'product',
                'product.translation' => function($q) use ($lang) {
                    $q->where('locale', $lang);
                }
            ])
                ->when($shopId, fn($q, $shopId) => $q->where('shop_id', $shopId))
                ->when($productId, fn($q, $productId) => $q->where('product_id', $productId))
                ->active()
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('perPage', 15));
            
            $reelsData = [];
            foreach ($reels as $reel) {
                $videoUrl = $reel->video_url;
                if ($videoUrl && !str_starts_with($videoUrl, 'http')) {
                    $videoUrl = config('app.img_host') . (str_starts_with($videoUrl, '/') ? '' : '/') . $videoUrl;
                }

                $reelData = [
                    'id' => $reel->id,
                    'video_url' => $videoUrl,
                    'description' => $reel->description,
                    'is_liked' => $reel->isLikedByUser($userId),
                    'likes_count' => $reel->likes_count,
                    'created_at' => $reel->created_at?->format('Y-m-d\TH:i:s\Z'),
                    'updated_at' => $reel->updated_at?->format('Y-m-d\TH:i:s\Z'),
                    'shop' => [
                        'id' => $reel->shop->id,
                        'uuid' => $reel->shop->uuid,
                        'user_id' => $reel->shop->user_id,
                        'tax' => (float) $reel->shop->tax,
                        'delivery_range' => (int) $reel->shop->delivery_range,
                        'percentage' => (float) $reel->shop->percentage,
                        'phone' => $reel->shop->phone,
                        'show_type' => (bool) $reel->shop->show_type,
                        'open' => (bool) $reel->shop->open,
                        'visibility' => (bool) $reel->shop->visibility,
                        'open_time' => $reel->shop->open_time,
                        'close_time' => $reel->shop->close_time,
                        'background_img' => $reel->shop->background_img,
                        'logo_img' => $reel->shop->logo_img,
                        'min_amount' => (float) $reel->shop->min_amount,
                        'status' => $reel->shop->status,
                        'status_note' => $reel->shop->status_note,
                        'rating_avg' => (float) $reel->shop->r_avg,
                        'created_at' => $reel->shop->created_at?->format('Y-m-d\TH:i:s\Z'),
                        'updated_at' => $reel->shop->updated_at?->format('Y-m-d\TH:i:s\Z'),
                        'translation' => $reel->shop->translation ? [
                            'id' => $reel->shop->translation->id,
                            'locale' => $reel->shop->translation->locale,
                            'title' => $reel->shop->translation->title ?? 'Shop Name',
                            'description' => $reel->shop->translation->description ?? ''
                        ] : null
                    ]
                ];

                // Add product data if exists
                if ($reel->product) {
                    $reelData['product'] = [
                        'id' => $reel->product->id,
                        'uuid' => $reel->product->uuid,
                        'shop_id' => $reel->product->shop_id,
                        'category_id' => $reel->product->category_id,
                        'price' => (float) $reel->product->price,
                        'img' => $reel->product->img,
                        'stock' => (int) $reel->product->stock?->quantity,
                        'active' => (bool) $reel->product->active,
                        'translation' => $reel->product->translation ? [
                            'id' => $reel->product->translation->id,
                            'locale' => $reel->product->translation->locale,
                            'title' => $reel->product->translation->title ?? 'Product Name',
                            'description' => $reel->product->translation->description ?? ''
                        ] : null
                    ];
                } else {
                    $reelData['product'] = null;
                }

                $reelsData[] = $reelData;
            }

            // Return format matching the documentation exactly
            return response()->json([
                'data' => $reelsData,
                'meta' => [
                    'current_page' => $reels->currentPage(),
                    'last_page' => $reels->lastPage(),
                    'total' => $reels->total()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ], 500);
        }
    }

    /**
     * Toggle like for a reel
     *
     * @param int $id
     * @return JsonResponse
     */
    public function like(int $id): JsonResponse
    {
        try {
            $userId = auth('sanctum')->id();
            
            if (!$userId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $reel = \App\Models\Reel::find($id);
            
            if (!$reel) {
                return response()->json([
                    'status' => false,
                    'message' => 'Reel not found'
                ], 404);
            }

            // Check if user already liked this reel
            $existingLike = $reel->likes()->where('user_id', $userId)->first();

            if ($existingLike) {
                // Unlike - remove the like
                $existingLike->delete();
                $reel->decrement('likes_count');
            } else {
                // Like - add the like
                $reel->likes()->create([
                    'user_id' => $userId
                ]);
                $reel->increment('likes_count');
            }

            // Return format matching the Flutter documentation exactly
            return response()->json([
                'status' => true,
                'message' => 'Successfully updated'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}