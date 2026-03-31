<?php
declare(strict_types=1);

namespace App\Repositories\ReelsRepository;

use App\Models\Language;
use App\Models\Reel;
use App\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ReelsRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Reel::class;
    }

    /**
     * Get paginated reels with shop information
     *
     * @param array $data
     * @return LengthAwarePaginator
     */
    public function paginate(array $data = []): LengthAwarePaginator
    {
        $locale = Language::languagesList()->where('default', 1)->first()?->locale;
        $userId = auth('sanctum')->id();

        /** @var Reel $reels */
        $reels = $this->model();

        return $reels
            ->with([
                'shop' => function ($q) {
                    $q->select([
                        'id', 'uuid', 'user_id', 'tax', 'delivery_range', 'percentage',
                        'phone', 'show_type', 'open', 'visibility', 'open_time', 'close_time',
                        'background_img', 'logo_img', 'min_amount', 'status', 'status_note',
                        'rating_avg', 'created_at', 'updated_at'
                    ]);
                },
                'shop.translation' => function ($q) use ($locale) {
                    $q->select('id', 'shop_id', 'locale', 'title', 'description')
                        ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                            $q->where('locale', $this->language)->orWhere('locale', $locale);
                        }));
                }
            ])
            ->select([
                'id',
                'shop_id',
                'video_url',
                'description',
                'likes_count',
                'created_at',
                'updated_at'
            ])
            ->when(data_get($data, 'shop_id'), fn(Builder $q, $shopId) => $q->where('shop_id', $shopId))
            ->active()
            ->orderBy(data_get($data, 'column', 'created_at'), data_get($data, 'sort', 'desc'))
            ->paginate(data_get($data, 'perPage', 15));
    }

    /**
     * Get reels list with like status for authenticated user
     *
     * @param array $data
     * @return array
     */
    public function list(array $data = []): array
    {
        $locale = Language::languagesList()->where('default', 1)->first()?->locale;
        $userId = auth('sanctum')->id();

        $reels = $this->paginate($data);
        
        $reelsData = [];
        
        foreach ($reels as $reel) {
            $reelsData[] = [
                'id' => $reel->id,
                'video_url' => $reel->video_url,
                'description' => $reel->description,
                'is_liked' => $reel->isLikedByUser($userId),
                'likes_count' => $reel->likes_count,
                'created_at' => $reel->created_at?->format('Y-m-d\TH:i:s\Z'),
                'updated_at' => $reel->updated_at?->format('Y-m-d\TH:i:s\Z'),
                'shop' => [
                    'id' => $reel->shop->id,
                    'uuid' => $reel->shop->uuid,
                    'user_id' => $reel->shop->user_id,
                    'tax' => $reel->shop->tax,
                    'delivery_range' => $reel->shop->delivery_range,
                    'percentage' => $reel->shop->percentage,
                    'phone' => $reel->shop->phone,
                    'show_type' => $reel->shop->show_type,
                    'open' => $reel->shop->open,
                    'visibility' => $reel->shop->visibility,
                    'open_time' => $reel->shop->open_time,
                    'close_time' => $reel->shop->close_time,
                    'background_img' => $reel->shop->background_img,
                    'logo_img' => $reel->shop->logo_img,
                    'min_amount' => $reel->shop->min_amount,
                    'status' => $reel->shop->status,
                    'status_note' => $reel->shop->status_note,
                    'rating_avg' => $reel->shop->rating_avg,
                    'created_at' => $reel->shop->created_at?->format('Y-m-d\TH:i:s\Z'),
                    'updated_at' => $reel->shop->updated_at?->format('Y-m-d\TH:i:s\Z'),
                    'translation' => $reel->shop->translation ? [
                        'id' => $reel->shop->translation->id,
                        'locale' => $reel->shop->translation->locale,
                        'title' => $reel->shop->translation->title,
                        'description' => $reel->shop->translation->description,
                    ] : null
                ]
            ];
        }

        return [
            'data' => $reelsData,
            'meta' => [
                'current_page' => $reels->currentPage(),
                'last_page' => $reels->lastPage(),
                'total' => $reels->total()
            ]
        ];
    }
}