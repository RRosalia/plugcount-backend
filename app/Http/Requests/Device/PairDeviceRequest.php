<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates device pairing requests from authenticated users.
 */
class PairDeviceRequest extends FormRequest
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
            'pairing_code' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pairing_code.size' => 'The pairing code must be exactly 6 digits.',
            'pairing_code.regex' => 'The pairing code must contain only numbers.',
        ];
    }
}
