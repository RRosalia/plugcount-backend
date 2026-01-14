<?php

/**
 * LinkRequest
 *
 * Validates the request for linking an integration to a device.
 *
 * @package App\Http\Requests\DeviceIntegration
 */

namespace App\Http\Requests\DeviceIntegration;

use App\Models\Device;
use App\Models\UserIntegration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkRequest extends FormRequest
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
            'device_id' => ['required', 'integer', Rule::exists(Device::class, 'id')],
            'user_integration_id' => ['required', 'integer', Rule::exists(UserIntegration::class, 'id')],
            'metric_type' => ['required', 'string', 'max:50'],
            'display_config' => ['nullable', 'array'],
            'display_config.label' => ['nullable', 'string', 'max:100'],
            'display_config.color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'display_config.refresh_interval' => ['nullable', 'integer', 'min:30', 'max:3600'],
        ];
    }
}
