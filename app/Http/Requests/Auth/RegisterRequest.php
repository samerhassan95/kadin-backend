<?php
declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     * @return array
     */
    public function rules(): array
	{
		return [
            'phone'                 => [
                'nullable',
                'numeric',
                Rule::unique('users', 'phone')->whereNotNull('phone_verified_at')
            ],
            'password'              => 'nullable|string|min:6',
            'password_confirmation' => 'nullable|string|same:password',
            'email'                 => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->whereNotNull('email_verified_at')
            ],
            'firstname'             => 'nullable|string|min:2|max:100',
            'lastname'              => 'nullable|string|min:2|max:100',
            'referral'              => 'nullable|string|exists:users,my_referral|max:255',
		];
	}
}
