<?php
declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseRequest;

class ExtrasRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'extras'            => 'required|array',
            'extras.*.ids'      => 'nullable|array',
            'extras.*.ids.*'    => 'integer|exists:extra_values,id',
            'extras.*.price'    => 'required|numeric',
            'extras.*.quantity' => 'required|integer',
            'extras.*.sku'      => 'string|max:255',
            'extras.*.images'   => 'array',
            'extras.*.images.*' => 'string',
        ];
    }
}
