<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\TransactionResource;
use App\Repositories\TransactionRepository\TransactionRepository;
use App\Services\TransactionService\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends AdminBaseController
{

    public function __construct(private TransactionRepository $repository, private TransactionService $service)
    {
        parent::__construct();
    }

    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $transactions = $this->repository->paginate($request->all());

        return TransactionResource::collection($transactions);
    }

    public function show(int $id): JsonResponse
    {
        $transaction = $this->repository->show($id);

        if (empty($transaction)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            TransactionResource::make($transaction)
        );
    }

    /**
     * @return JsonResponse
     */
    public function dropAll(): JsonResponse
    {
        $this->service->dropAll();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
