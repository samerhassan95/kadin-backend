<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Models\Gallery;
use App\Models\Settings;
use Illuminate\Http\UploadedFile;
use Throwable;

class FileHelper
{
    public const imageExtensions = [
        'png',
        'jpg',
        'jpeg',
        'webp',
        'svg',
        'jfif',
        'avif',
        'gif',
    ];

    private static $awsSettings = null;

    /**
     * Upload file function
     * @param UploadedFile $file
     * @param string $path
     * @return array
     */
    public static function uploadFile(UploadedFile $file, string $path): array
    {
        try {
            if (self::$awsSettings === null) {
                self::$awsSettings = Settings::where('key', 'aws')->first();
            }

            $options = [];

            if (data_get(self::$awsSettings, 'value')) {
                $options = ['disk' => 's3'];
            }

            $id = auth('sanctum')->id() ?? "0001";

            $originalExt = strtolower($file->getClientOriginalExtension());
            $ext = $originalExt;
            $dir = $ext;

            if (in_array($ext, self::imageExtensions)) {
                $dir = 'images';
                // Only rename the extension to webp if it's an image, 
                // but keep the original logic if it's required by the system
                $ext = 'webp'; 
            }

            $time = time() . mt_rand(1000, 9999);
            $fileName = "$id-$time.$ext";

            $url = $file->storeAs("public/$dir/$path", $fileName, $options);

            // Return just the relative path without the full URL
            $relativePath = str_replace('public/', '', $url);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $relativePath
            ];
        } catch (Throwable $e) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_400,
                'message' => $e->getMessage() === "Class \"finfo\" not found" ? 'You need on php file info extension' : $e->getMessage()
            ];
        }
    }

    /**
     * Delete file function
     * @param $path
     * @return mixed
     */
    public static function deleteFile($path): mixed
    {
        return Gallery::where('path', $path)->delete();
    }

}
