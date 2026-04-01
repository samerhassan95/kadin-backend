<?php
declare(strict_types=1);

namespace App\Services\AuthService;

use App\Models\User;
use App\Services\CoreService;
use App\Services\UserServices\UserWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Http\Resources\UserResource;

class DirectAuth extends CoreService
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return User::class;
    }

    public function register(array $array): JsonResponse
    {
        try {
            // Set default values for missing fields
            $firstname = data_get($array, 'firstname') ?: 
                        data_get($array, 'email') ?: 
                        data_get($array, 'phone') ?: 
                        'User';
            
            $lastname = data_get($array, 'lastname') ?: '';
            
            /** @var User $user */
            $user = $this->model()->create([
                'firstname'         => $firstname,
                'lastname'          => $lastname,
                'email'             => data_get($array, 'email'),
                'phone'             => data_get($array, 'phone'),
                'password'          => Hash::make(data_get($array, 'password', 'defaultpass123')),
                'email_verified_at' => now(), // Auto verify for direct registration
                'phone_verified_at' => now(), // Auto verify for direct registration
                'active'            => true,
                'ip_address'        => request()->ip(),
            ]);

            if (!$user->hasAnyRole(Role::query()->pluck('name')->toArray())) {
                $user->syncRoles('seller');
            }

            // Create wallet for user
            if (empty($user->wallet)) {
                (new UserWalletService)->create($user);
            }

            // Create email subscription
            $user->emailSubscription()->updateOrCreate([
                'user_id' => $user->id
            ], [
                'active' => true
            ]);

            $token = $user->createToken('api_token')->plainTextToken;

            return $this->successResponse('User successfully registered', [
                'access_token'  => $token,
                'token_type'    => 'Bearer',
                'user'          => UserResource::make($user->load(['roles', 'wallet'])),
            ]);

        } catch (\Exception $e) {
            return $this->onErrorResponse([
                'code'    => 400,
                'message' => $e->getMessage()
            ]);
        }
    }
}