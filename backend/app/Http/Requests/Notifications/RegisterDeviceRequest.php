<?php

namespace App\Http\Requests\Notifications;

use App\Models\Device;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['required', Rule::in([Device::PLATFORM_ANDROID, Device::PLATFORM_IOS])],
        ];
    }
}
