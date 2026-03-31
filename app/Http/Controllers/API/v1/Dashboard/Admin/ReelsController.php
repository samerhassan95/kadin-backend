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
        $reels = Reel::with(['shop', 'shop.translation'])
            ->when($request->shop_id, fn($q, $shopId) => $q->where('shop_id', $shopId))
            ->when(isset($request->active), fn($q) => $q->where('active', $request->active))
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('perPage', 15));

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            [
                'data' => $reels->items(),
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
            $reel = Reel::create($request->validated());
            $reel->load(['shop', 'shop.translation']);

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
        $reel->load(['shop', 'shop.translation']);

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
            $reel->update($request->validated());
            $reel->load(['shop', 'shop.translation']);

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