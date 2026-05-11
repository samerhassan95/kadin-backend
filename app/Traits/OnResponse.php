<?php
declare(strict_types=1);

namespace App\Traits;

use App\Helpers\ResponseError;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait OnResponse
{
    /**
     * @param array $result = ['code' => 200]
     * @return JsonResponse
     */
    public function onErrorResponse(array $result = []): JsonResponse
    {
        $code = data_get($result, 'code', ResponseError::ERROR_101);

        $httpDefault = $code === ResponseError::ERROR_404 ? Response::HTTP_NOT_FOUND : Response::HTTP_BAD_REQUEST;

        $http = data_get($result, 'http', $httpDefault);

        $data = is_array(data_get($result, 'data')) ? data_get($result, 'data') : [];

        $locale = property_exists($this, 'language') ? $this->language : 'en';

        $message = $code === ResponseError::ERROR_101 ?
            __('errors.' . ResponseError::ERROR_101, $data, locale: $locale) :
            __('errors.' . $code, $data, locale: $locale);

        return $this->errorResponse(
            (string)$code,
            (string)data_get($result, 'message', $message),
            (int)$http
        );
    }

    /**
     * @param \Illuminate\Database\QueryException $exception
     * @return JsonResponse
     */
    public function handleQueryException(\Illuminate\Database\QueryException $exception): JsonResponse
    {
        if ($exception->errorInfo[1] == 1062) {
            $message = $exception->getMessage();
            if (str_contains($message, 'users_phone_unique')) {
                return $this->onErrorResponse([
                    'code' => ResponseError::ERROR_400,
                    'message' => 'هذا الرقم مسجل بالفعل، يرجى استخدام رقم آخر أو تسجيل الدخول.'
                ]);
            }
            if (str_contains($message, 'users_email_unique')) {
                return $this->onErrorResponse([
                    'code' => ResponseError::ERROR_400,
                    'message' => 'هذا البريد الإلكتروني مسجل بالفعل، يرجى استخدام بريد آخر.'
                ]);
            }
            return $this->onErrorResponse(['code' => ResponseError::ERROR_106]);
        }

        return $this->onErrorResponse([
            'code' => ResponseError::ERROR_400,
            'message' => 'حدث خطأ في قاعدة البيانات.'
        ]);
    }
}
