<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\TransactionResource;
use App\Repositories\TransactionRepository\TransactionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends SellerBaseController
{

    public function __construct(private TransactionRepository $transactionRepository)
    {
        parent::__construct();
    }

    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $filter = $request->merge(['shop_id' => $this->shop->id])->all();

        $transactions = $this->transactionRepository->paginate($filter);

        return TransactionResource::collection($transactions);
    }

    public function show(int $id): JsonResponse
    {
        $transaction = $this->transactionRepository->show($id, $this->shop->id);

        if (empty($transaction)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            TransactionResource::make($transaction)
        );
    }
}
