<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates device registration requests from ESP32 devices.
 */
class RegisterDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string', 'uuid'],
            'mac_address' => ['nullable', 'string'],
            'ip_address' => ['nullable', 'ip'],
            'firmware_version' => ['nullable', 'string'],
        ];
    }
}
