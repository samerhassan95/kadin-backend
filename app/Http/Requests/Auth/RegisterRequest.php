<?php
declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class RegisterRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     * @return array
     */
    public function rules(): array
	{
		return [
            'phone'                 => 'nullable|numeric',
            'password'              => 'nullable|string|min:6',
            'password_confirmation' => 'nullable|string|same:password',
            'email'                 => 'nullable|email',
            'firstname'             => 'nullable|string|min:1|max:100',
            'lastname'              => 'nullable|string|min:1|max:100',
            'referral'              => 'nullable|string|exists:users,my_referral|max:255',
		];
	}
}
