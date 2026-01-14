<?php

/**
 * AuthChallengeRequest
 *
 * Validates the request for generating a device authentication challenge.
 *
 * @package App\Http\Requests\Device
 */

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class AuthChallengeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'device_uuid' => ['required', 'string', 'uuid'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'device_uuid.required' => 'Device UUID is required',
            'device_uuid.uuid' => 'Device UUID must be a valid UUID',
        ];
    }
}
