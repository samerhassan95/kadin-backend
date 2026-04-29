<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\ReelResource;
use App\Models\Reel;
use App\Repositories\ReelsRepository\ReelsRepository;
use App\Services\ReelsService\ReelsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReelsController extends SellerBaseController
{
    public function __construct(
        private ReelsService $service, 
        private ReelsRepository $repository
    ) {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $reels = Reel::where('shop_id', $this->shop->id)
            ->with(['shop', 'shop.translations', 'product', 'product.translations'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('perPage', 15));

        $lang = $request->input('lang', 'en');
        $imgHost = rtrim(config('app.img_host'), '/');

        $items = $reels->map(function ($reel) use ($lang, $imgHost) {
            $videoUrl = $reel->video_url;
            if ($videoUrl && !str_starts_with($videoUrl, 'http')) {
                $videoUrl = $imgHost . '/' . ltrim($videoUrl, '/');
            }

            $reel->video_url = $videoUrl;

            // Fix shop images (stored in storage/ folder)
            if ($reel->shop) {
                if ($reel->shop->background_img && !str_starts_with($reel->shop->background_img, 'http')) {
                    $path = ltrim($reel->shop->background_img, '/');
                    if (!str_starts_with($path, 'storage/')) {
                        $path = 'storage/' . $path;
                    }
                    $reel->shop->background_img = $imgHost . '/' . $path;
                }
                if ($reel->shop->logo_img && !str_starts_with($reel->shop->logo_img, 'http')) {
                    $path = ltrim($reel->shop->logo_img, '/');
                    if (!str_starts_with($path, 'storage/')) {
                        $path = 'storage/' . $path;
                    }
                    $reel->shop->logo_img = $imgHost . '/' . $path;
                }
            }

            $reel->shop_name = $reel->shop->translations->where('locale', $lang)->first()?->title
                ?? $reel->shop->translations->first()?->title
                ?? null;
            $reel->product_name = $reel->product
                ? ($reel->product->translations->where('locale', $lang)->first()?->title
                    ?? $reel->product->translations->first()?->title
                    ?? null)
                : null;

            return $reel;
        });

        return $this->successResponsePaginate(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            [
                'data' => $items,
                'meta' => [
                    'current_page' => $reels->currentPage(),
                    'last_page'    => $reels->lastPage(),
                    'total'        => $reels->total(),
                    'per_page'     => $reels->perPage(),
                ],
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'video_url' => 'required|string',
            'description' => 'nullable|string|max:500',
            'product_id' => 'nullable|exists:products,id',
            'is_active' => 'boolean'
        ]);

        try {
            $reel = Reel::create([
                'shop_id' => $this->shop->id,
                'title' => $request->title,
                'video_url' => $request->video_url,
                'description' => $request->description,
                'product_id' => $request->product_id,
                'is_active' => $request->input('is_active', 1),
                'likes_count' => 0
            ]);

            // Load relationships for response
            $reel->load(['shop', 'shop.translation', 'product', 'product.translation']);

            return $this->successResponse(
                __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
                $reel
            );
        } catch (\Exception $e) {
            return $this->onErrorResponse([
                'code' => ResponseError::ERROR_500,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Reel $reel
     * @return JsonResponse
     */
    public function show(Reel $reel): JsonResponse
    {
        // Check if reel belongs to current shop
        if ($reel->shop_id !== $this->shop->id) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $reel->load(['shop', 'shop.translation', 'product', 'product.translation']);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            $reel
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Reel $reel
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Reel $reel, Request $request): JsonResponse
    {
        // Check if reel belongs to current shop
        if ($reel->shop_id !== $this->shop->id) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'video_url' => 'sometimes|string',
            'description' => 'nullable|string|max:500',
            'product_id' => 'nullable|exists:products,id',
            'is_active' => 'boolean'
        ]);

        try {
            $reel->update($request->only(['title', 'video_url', 'description', 'product_id', 'is_active']));

            // Load relationships for response
            $reel->load(['shop', 'shop.translation', 'product', 'product.translation']);

            return $this->successResponse(
                __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
                $reel
            );
        } catch (\Exception $e) {
            return $this->onErrorResponse([
                'code' => ResponseError::ERROR_500,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Reel $reel
     * @return JsonResponse
     */
    public function destroy(Reel $reel): JsonResponse
    {
        // Check if reel belongs to current shop
        if ($reel->shop_id !== $this->shop->id) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        try {
            $reel->delete();

            return $this->successResponse(
                __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language)
            );
        } catch (\Exception $e) {
            return $this->onErrorResponse([
                'code' => ResponseError::ERROR_500,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Upload video file for reel
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadVideo(Request $request): JsonResponse
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,avi,wmv|max:50000' // 50MB max
        ]);

        try {
            $file = $request->file('video');
            $path = $file->store('reels', 'public');
            $url = asset('storage/' . $path);

            return $this->successResponse(
                __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
                [
                    'video_url' => $url,
                    'path' => $path
                ]
            );
        } catch (\Exception $e) {
            return $this->onErrorResponse([
                'code' => ResponseError::ERROR_500,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Toggle reel active status
     *
     * @param Reel $reel
     * @return JsonResponse
     */
    public function toggleActive(Reel $reel): JsonResponse
    {
        // Check if reel belongs to current shop
        if ($reel->shop_id !== $this->shop->id) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        try {
            $reel->update(['is_active' => !$reel->is_active]);

            // Load relationships for response
            $reel->load(['shop', 'shop.translation', 'product', 'product.translation']);

            return $this->successResponse(
                __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
                $reel
            );
        } catch (\Exception $e) {
            return $this->onErrorResponse([
                'code' => ResponseError::ERROR_500,
                'message' => $e->getMessage()
            ]);
        }
    }
}