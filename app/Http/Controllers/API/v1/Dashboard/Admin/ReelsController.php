<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Admin\Reel\StoreRequest;
use App\Http\Requests\Admin\Reel\UpdateRequest;
use App\Models\Reel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReelsController extends AdminBaseController
{
    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function index(FilterParamsRequest $request): JsonResponse
    {
        $reels = Reel::with(['shop', 'shop.translation', 'product', 'product.translation'])
            ->when($request->shop_id, fn($q, $shopId) => $q->where('shop_id', $shopId))
            ->when($request->product_id, fn($q, $productId) => $q->where('product_id', $productId))
            ->when(isset($request->active), fn($q) => $q->where('active', $request->active))
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('perPage', 15));

        $imgHost = rtrim(config('app.img_host'), '/');
        if (str_ends_with($imgHost, 'storage')) {
            $imgHost = rtrim(substr($imgHost, 0, -7), '/');
        }

        $items = $reels->map(function ($reel) use ($imgHost) {
            $videoUrl = $reel->video_url;
            if ($videoUrl && !str_starts_with($videoUrl, 'http')) {
                $path = ltrim($videoUrl, '/');
                if (!str_starts_with($path, 'storage/')) {
                    $path = 'storage/' . $path;
                }
                $videoUrl = $imgHost . '/' . $path;
            }
            $reel->video_url = $videoUrl;
            return $reel;
        });

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            [
                'data' => $items,
                'meta' => [
                    'current_page' => $reels->currentPage(),
                    'last_page' => $reels->lastPage(),
                    'total' => $reels->total(),
                    'per_page' => $reels->perPage()
                ]
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // If a file is uploaded directly, handle the upload first
            if ($request->hasFile('video')) {
                $file = $request->file('video');
                $path = $file->store('reels', 'public');
                $data['video_url'] = $path; // Store the relative path in DB
            }

            $reel = Reel::create($data);
            $reel->load(['shop', 'shop.translation', 'product', 'product.translation']);

            // Format URL for the response
            $imgHost = rtrim(config('app.img_host'), '/');
            if (str_ends_with($imgHost, 'storage')) {
                $imgHost = rtrim(substr($imgHost, 0, -7), '/');
            }
            if ($reel->video_url && !str_starts_with($reel->video_url, 'http')) {
                $path = ltrim($reel->video_url, '/');
                if (!str_starts_with($path, 'storage/')) {
                    $path = 'storage/' . $path;
                }
                $reel->video_url = $imgHost . '/' . $path;
            }

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
        $reel->load(['shop', 'shop.translation', 'product', 'product.translation']);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            $reel
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateRequest $request
     * @param Reel $reel
     * @return JsonResponse
     */
    public function update(UpdateRequest $request, Reel $reel): JsonResponse
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('video')) {
                $file = $request->file('video');
                $path = $file->store('reels', 'public');
                $data['video_url'] = $path;
            }

            $reel->update($data);
            $reel->load(['shop', 'shop.translation', 'product', 'product.translation']);

            // Format URL for the response
            $imgHost = rtrim(config('app.img_host'), '/');
            if (str_ends_with($imgHost, 'storage')) {
                $imgHost = rtrim(substr($imgHost, 0, -7), '/');
            }
            if ($reel->video_url && !str_starts_with($reel->video_url, 'http')) {
                $path = ltrim($reel->video_url, '/');
                if (!str_starts_with($path, 'storage/')) {
                    $path = 'storage/' . $path;
                }
                $reel->video_url = $imgHost . '/' . $path;
            }

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
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        
        if (empty($ids)) {
            return $this->onErrorResponse([
                'code' => ResponseError::ERROR_400,
                'message' => 'No IDs provided'
            ]);
        }

        try {
            Reel::whereIn('id', $ids)->delete();

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
     * Drop all reels
     *
     * @return JsonResponse
     */
    public function dropAll(): JsonResponse
    {
        try {
            Reel::truncate();

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
        try {
            $reel->update(['active' => !$reel->active]);

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