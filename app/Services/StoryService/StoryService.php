<?php
declare(strict_types=1);

namespace App\Services\StoryService;

use App\Helpers\FileHelper;
use App\Helpers\ResponseError;
use App\Models\Settings;
use App\Models\Story;
use App\Services\CoreService;
use Illuminate\Http\UploadedFile;
use Throwable;

class StoryService extends CoreService
{
    protected function getModelClass(): string
    {
        return Story::class;
    }

    public function create(array $data): array
    {
        try {
            $this->model()->create($data);

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => [],
            ];
        } catch (Throwable $e) {
            $this->error($e);
        }

        return [
            'status' => false,
            'code' => ResponseError::ERROR_501,
        ];
    }

    public function update(Story $story, array $data): array
    {
        try {
            $story->update($data);

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => [],
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
        $stories = Story::whereIn('id', is_array($ids) ? $ids : [])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->get();

        foreach ($stories as $story) {
            /** @var Story $story */
            $this->removeFiles(is_array($story->file_urls) ? $story->file_urls : []);
            $story->delete();
        }

        return [
            'status' => true,
            'code' => ResponseError::NO_ERROR,
        ];
    }

    public function uploadFiles(array $data): array
    {
        $fileUrls = [];

        $isAws = Settings::where('key', 'aws')->first();

        $options = [];

        if (data_get($isAws, 'value')) {
            $options = ['disk' => 's3'];
        }

        foreach (data_get($data, 'files') as $file) {

            try {
                /** @var UploadedFile $file */
                $id = auth('sanctum')->id() ?? "0001";

                $ext  = $file->getClientOriginalExtension();

                if (in_array($file->getClientOriginalExtension(), FileHelper::imageExtensions)) {

                    $ext = strtolower(
                        preg_replace("#.+\.([a-z]+)$#i", "$1",
                            str_replace(FileHelper::imageExtensions, '.webp', $file->getClientOriginalName())
                        )
                    );

                }

                $fileName = $id . '-' . now()->unix() . '.' . $ext;

                $url = $file->storeAs('public/stories', $fileName, $options);

                $fileUrls[] = config('app.img_host') . $url;

            } catch (Throwable $e) {
                $message = $e->getMessage();

                if ($message === "Class \"finfo\" not found") {
                    $message = 'You need on php file info extension';
                }

                return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $message];
            }

        }

        if (count($fileUrls) === 0) {
            return [
                'status'    => false,
                'code'      => ResponseError::ERROR_508,
            ];
        }

        return [
            'status'    => true,
            'code'      => ResponseError::NO_ERROR,
            'data'      => $fileUrls,
        ];
    }

    public function removeFiles(array $fileUrls) {

        foreach ($fileUrls as $fileUrl) {
            try {
                $storageUrl = str_replace(request()->getHttpHost() . '/storage', 'app/public', $fileUrl);

                unlink(storage_path($storageUrl));
            } catch (Throwable $e) {
                $this->error($e);
            }
        }

    }

}
