<?php

namespace App\Http\Requests\Admin\Reel;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'shop_id' => 'sometimes|required|integer|exists:shops,id',
            'video_url' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    // Check if it's an MP4 file (basic URL validation)
                    if (!str_contains(strtolower($value), '.mp4')) {
                        $fail('Only MP4 video files are allowed.');
                    }
                }
            ],
            'description' => 'nullable|string|max:1000',
            'active' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'shop_id.required' => 'Shop is required',
            'shop_id.exists' => 'Selected shop does not exist',
            'video_url.required' => 'Video URL is required',
            'video_url.max' => 'Video URL must not exceed 255 characters',
            'description.max' => 'Description must not exceed 1000 characters',
        ];
    }
}