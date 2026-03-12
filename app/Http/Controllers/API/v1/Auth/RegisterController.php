<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Auth;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService\AuthByEmail;
use App\Services\AuthService\AuthByMobilePhone;
use App\Services\AuthService\DirectAuth;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;


class RegisterController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request): JsonResponse
    {
        // For direct registration without Firebase verification
        if ($request->input('email') && $request->input('password') && $request->input('firstname')) {
            return (new DirectAuth)->register($request->validated());
        }

        // Legacy Firebase-based registration
        if ($request->input('phone')) {
            return (new AuthByMobilePhone)->authentication($request->validated());
        } else if ($request->input('email')) {
            return (new AuthByEmail)->authentication($request->validated());
        }

        return $this->onErrorResponse([
            'code' => ResponseError::ERROR_400
        ]);
    }
}
