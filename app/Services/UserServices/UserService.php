<?php
declare(strict_types=1);

namespace App\Services\UserServices;

use App\Helpers\ResponseError;
use App\Http\Resources\UserResource;
use App\Models\Notification;
use App\Models\User;
use App\Services\CoreService;
use DB;
use Exception;

class UserService extends CoreService
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return User::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            /** @var User $user */

            $password = bcrypt(data_get($data, 'password', 'password'));

            unset($data['password']);

            if (!empty(data_get($data, 'firebase_token'))) {
                $data['firebase_token'] = [data_get($data, 'firebase_token')];
            }

            if (data_get($data, 'phone')) {
                $data['phone'] = preg_replace('/\D/', '', (string)data_get($data, 'phone'));
            }

            $user = $this->model()->create($data + [
                'password'   => $password,
                'ip_address' => request()->ip()
            ]);

            if (data_get($data, 'images.0')) {
                $user->update(['img' => data_get($data, 'images.0')]);
                $user->uploads(data_get($data, 'images'));
            }

            $user->syncRoles(data_get($data, 'role', 'user'));

            if($user->hasRole(['moderator', 'deliveryman', 'master']) && is_array(data_get($data, 'shop_id'))) {

                foreach (data_get($data, 'shop_id') as $shopId) {
                    $user->invitations()->create([
                        'shop_id' => $shopId,
                        'role'    => $data['role']
                    ]);
                }

            }

            $this->notificationSync($user);

            $user->emailSubscription()->updateOrCreate([
                'user_id' => $user->id
            ], [
                'active' => true
            ]);

            $user = (new UserWalletService)->create($user);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $user->loadMissing(['invitations', 'roles'])
            ];
        } catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    public function notificationSync(User $user): void
    {

        $id = Notification::where('type', Notification::PUSH)
            ->select(['id', 'type'])
            ->first()
            ?->id;

        if ($id) {

            $user->notifications()->sync([$id]);

            return;
        }

        $user->notifications()->delete();

    }

    /**
     * @param string $uuid
     * @param array $data
     * @return array
     */
    public function update(string $uuid, array $data): array
    {
        /** @var User $user */
        $user = $this->model()->firstWhere('uuid', $uuid);

        if (!$user) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

        try {

            if (!empty(data_get($data, 'password'))) {

                $password = bcrypt(data_get($data, 'password', 'password'));

                $data['password'] = $password;

            }

            if (data_get($data, 'firebase_token')) {
                $token = is_array($user->firebase_token) ? $user->firebase_token : [];
                $data['firebase_token'] = array_push($token, data_get($data, 'firebase_token'));
            }

            if (data_get($data, 'phone')) {
                $data['phone'] = preg_replace('/\D/', '', (string)data_get($data, 'phone') ?? $user->phone);
            }

            $user->update($data);

            if (data_get($data, 'subscribe') !== null) {

                $user->emailSubscription()->updateOrCreate([
                    'user_id' => $user->id
                ], [
                    'active' => !!data_get($data, 'subscribe')
                ]);

            }

            if (!empty(data_get($data, 'notifications'))) {
                $user->notifications()->sync(data_get($data, 'notifications'));
            }

            if (data_get($data, 'images.0')) {

                $user->galleries()->delete();
                $user->update(['img' => data_get($data, 'images.0')]);
                $user->uploads(data_get($data, 'images'));

            }

            if (isset($data['role'])) {

                $user->syncRoles($data['role']);

                if (
                    in_array($data['role'], ['moderator', 'deliveryman'])
                    && is_array(data_get($data, 'shop_id'))
                ) {

                    $user->invitations()->delete();

                    foreach (data_get($data, 'shop_id') as $shopId) {
                        $user->invitations()->create([
                            'shop_id' => $shopId,
                            'role'    => $data['role']
                        ]);
                    }

                }

            }

            $user = $user->loadMissing(['emailSubscription', 'notifications', 'invitations', 'roles', 'wallet']);

            return [
                'status'    => true,
                'code'      => ResponseError::NO_ERROR,
                'data'      => $user
            ];
        } catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param $uuid
     * @param $password
     * @return array
     */
    public function updatePassword($uuid, $password): array
    {
        $user = $this->model()->firstWhere('uuid', $uuid);

        if (!$user) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

        try {
            $user->update(['password' => bcrypt($password)]);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $user];
        } catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param $uuid
     * @return array
     */
    public function loginAsUser($uuid): array
    {
        $user = $this->model()->firstWhere('uuid', $uuid);

        if (!$user) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

        try {
            /** @var User $user */
            return [
                'status' => true,
                'code'   => ResponseError::ERROR_400,
                'data'   => [
                    'access_token'  => $user->createToken('api_token')->plainTextToken,
                    'token_type'    => 'Bearer',
                    'user'          => UserResource::make($user->loadMissing(['wallet'])),
                ],
            ];
        } catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param array $data
     * @return array
     */
    public function updateNotifications(array $data): array
    {
        try {
            /** @var User $user */
            $user = auth('sanctum')->user();

            DB::table('notification_user')->where('user_id', $user->id)->delete();

            $user->notifications()->attach(data_get($data, 'notifications'));

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $user->loadMissing('notifications')
            ];
        } catch (Exception $e) {
            $this->error($e);
            return [
                'status'    => false,
                'code'      => ResponseError::ERROR_400,
                'message'   => "cant update notifications"
            ];
        }
    }

    /**
     * @param int $currencyId
     * @return array
     */
    public function updateCurrency(int $currencyId): array
    {
        try {
            /** @var User $user */
            $user = auth('sanctum')->user();

            $user->update(['currency_id' => $currencyId]);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $user];
        } catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param string $lang
     * @return array
     */
    public function updateLang(string $lang): array
    {
        try {
            /** @var User $user */
            $user = auth('sanctum')->user();

            $user->update(['lang' => $lang]);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $user];
        } catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param array|null $ids
     * @return array
     */
    public function delete(?array $ids = []): array
    {
        foreach (User::find($ids) as $user) {

            DB::table('wallet_histories')->where('created_by', $user->id)->delete();
            $user->wallet?->histories()?->delete();
            $user->wallet()?->delete();
            $user->transactions()?->delete();
            $user->delete();

        }

        return [
            'status' => true,
            'code'   => ResponseError::NO_ERROR
        ];
    }

    /**
     * @param string|null $firebaseToken
     * @return array|bool[]
     */
    public function firebaseTokenUpdate(?string $firebaseToken): array
    {
        if (empty($firebaseToken)) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_502,
                'message' => 'token is empty'
            ];
        }

        /** @var User $user */
        $user     = auth('sanctum')->user();

        $tokens   = is_array($user->firebase_token) ? $user->firebase_token : [$user->firebase_token];
        $tokens[] = $firebaseToken;

        $user->update(['firebase_token' => array_values(array_unique($tokens))]);

        return ['status' => true];
    }

    /**
     * @param User $user
     * @return void
     */
    public function setActive(User $user): void
    {
        $user->update(['active' => !$user->active]);
    }

}
