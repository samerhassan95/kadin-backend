<?php
declare(strict_types=1);

namespace App\Services\ReelsService;

use App\Helpers\FileHelper;
use App\Helpers\ResponseError;
use App\Models\Reel;
use App\Models\Settings;
use App\Services\CoreService;
use Illuminate\Http\UploadedFile;
use Throwable;

class ReelsService extends CoreService
{
    protected function getModelClass(): string
    {
        return Reel::class;
    }

    public function create(array $data): array
    {
        try {
            $reel = $this->model()->create($data);

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => $reel,
            ];
        } catch (Throwable $e) {
            $this->error($e);
        }

        return [
            'status' => false,
            'code' => ResponseError::ERROR_501,
        ];
    }

    public function update(Reel $reel, array $data): array
    {
        try {
            $reel->update($data);

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => $reel,
            ];
        } catch (Throwable $e) {
            $this->error($e);
        }

        return [
            'status' => false,
            'code' => ResponseError::ERROR_501,
        ];
    }

    public function delete(?array $ids = [], ?int $shopId = null): array
    {
        $reels = Reel::whereIn('id', is_array($ids) ? $ids : [])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->get();

        foreach ($reels as $reel) {
            /** @var Reel $reel */
            $this->removeVideoFile($reel->video_url);
            $reel->delete();
        }

        return [
            'status' => true,
            'code' => ResponseError::NO_ERROR,
        ];
    }

    public function uploadVideo(UploadedFile $file): array
    {
        try {
            $isAws = Settings::where('key', 'aws')->first();
            $options = [];

            if (data_get($isAws, 'value')) {
                $options = ['disk' => 's3'];
            }

            $id = auth('sanctum')->id() ?? "0001";
            $ext = $file->getClientOriginalExtension();
            $fileName = $id . '-reel-' . now()->unix() . '.' . $ext;

            $url = $file->storeAs('public/reels', $fileName, $options);
            $fullUrl = config('app.img_host') . $url;

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => [
                    'video_url' => $fullUrl,
                    'path' => $url
                ],
            ];
        } catch (Throwable $e) {
            $message = $e->getMessage();

            if ($message === "Class \"finfo\" not found") {
                $message = 'You need php file info extension';
            }

            return [
                'status' => false,
                'code' => ResponseError::ERROR_400,
                'message' => $message
            ];
        }
    }

    public function removeVideoFile(string $videoUrl): void
    {
        try {
            $storageUrl = str_replace(request()->getHttpHost() . '/storage', 'app/public', $videoUrl);
            $filePath = storage_path($storageUrl);
            
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        } catch (Throwable $e) {
            $this->error($e);
        }
    }

    public function toggleActive(Reel $reel): array
    {
        try {
            $reel->update(['active' => !$reel->active]);

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => $reel,
            ];
        } catch (Throwable $e) {
            $this->error($e);
        }

        return [
            'status' => false,
            'code' => ResponseError::ERROR_501,
        ];
    }
}