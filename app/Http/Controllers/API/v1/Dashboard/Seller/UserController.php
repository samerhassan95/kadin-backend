<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\UserCreateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository\UserRepository;
use App\Services\AuthService\UserVerifyService;
use App\Services\UserServices\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends SellerBaseController
{

    public function __construct(
        private UserRepository $repository,
        private UserService $service
    )
    {
        parent::__construct();
    }

    public function paginate(FilterParamsRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $users = $this->repository->usersPaginate($request->merge(['role' => 'user', 'active' => true])->all());

        return UserResource::collection($users);
    }

    public function show(string $uuid): JsonResponse
    {
        $user = $this->repository->userByUUID($uuid);

        if (empty($user)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            UserResource::make($user)
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param UserCreateRequest $request
     * @return JsonResponse
     */
    public function store(UserCreateRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['role'] = 'user';

        if (!empty(data_get($validated, 'email'))) {
            $validated['email_verified_at'] = now();
        }

        if (!empty(data_get($validated, 'phone'))) {
            $validated['phone_verified_at'] = now();
        }

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        (new UserVerifyService)->verifyEmail(data_get($result, 'data'));

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            UserResource::make(data_get($result, 'data'))
        );
    }

    public function shopUsersPaginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $users = $this->repository->shopUsersPaginate($request->merge(['shop_id' => $this->shop->id])->all());

        return UserResource::collection($users);
    }

    public function shopUserShow(string $uuid): JsonResponse
    {
        /** @var User $user */
        $user = $this->repository->userByUUID($uuid);

        if ($user && $user->invite?->shop_id == $this->shop->id) {
            return $this->successResponse(
                __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
                UserResource::make($user)
            );
        }

        return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
    }

    public function getDeliveryman(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $filter = $request
            ->merge([
                'not_shop_id' => $this->shop->id,
                'active'      => true
            ])
            ->all();

        $users = $this->repository->shopUsersPaginate($filter);

        return UserResource::collection($users);
    }

    public function setUserActive($uuid): JsonResponse
    {
        /** @var User $user */
        $user = $this->repository->userByUUID($uuid);

        if ($user && $user->invite?->shop_id == $this->shop->id) {

            $this->service->setActive($user);

            return $this->successResponse(
                __('errors.' . ResponseError::NO_ERROR, locale: $this->language), 
                UserResource::make($user)
            );
        }

        return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
    }
}
