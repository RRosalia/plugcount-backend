<?php

/**
 * AuthVerifyRequest
 *
 * Validates the request for verifying a device's signature.
 *
 * @package App\Http\Requests\Device
 */

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class AuthVerifyRequest extends FormRequest
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
            'challenge' => ['required', 'string', 'size:64'],
            'signature' => ['required', 'string'],
            'mac_address' => ['nullable', 'string', 'regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/'],
            'ip_address' => ['nullable', 'ip'],
            'firmware_version' => ['nullable', 'string', 'max:50'],
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
            'challenge.required' => 'Challenge is required',
            'challenge.size' => 'Challenge must be 64 characters (32 bytes hex)',
            'signature.required' => 'Signature is required',
            'mac_address.regex' => 'MAC address must be in format XX:XX:XX:XX:XX:XX',
        ];
    }
}
