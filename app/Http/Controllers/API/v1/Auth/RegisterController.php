<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Auth;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
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
        $email = $request->input('email');
        $phone = $request->input('phone');
        
        // Check if user already exists
        $existingUser = null;
        if ($email) {
            $existingUser = User::where('email', $email)->first();
        } elseif ($phone) {
            $existingUser = User::where('phone', $phone)->first();
        }
        
        if ($existingUser) {
            return $this->onErrorResponse([
                'code' => ResponseError::ERROR_400,
                'message' => 'Account already exists with this ' . ($email ? 'email' : 'phone number')
            ]);
        }
        
        // Create new user without SMS verification
        $data = $request->validated();
        if (!$request->input('password')) {
            $data['password'] = 'defaultpass123'; // Default password for SMS-less registration
        }
        
        return (new DirectAuth)->register($data);
    }
}
