<?php

namespace App\Http\Requests\DeviceToken;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceTokenRequest extends FormRequest
{
    /**
     * Determine whether the authenticated user can make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:2048'],
            'platform' => ['required', 'string', 'max:50'],
            'browser' => ['nullable', 'string', 'max:100'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}