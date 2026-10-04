<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminAccess;
use Illuminate\Foundation\Http\FormRequest;

class RouteScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return AdminAccess::canManageMasterData();
    }

    public function rules(): array
    {
        return [
            'departure_time' => ['required', 'date_format:H:i'],
            'arrival_time' => ['required', 'date_format:H:i'],
            'days_of_week' => ['required', 'array', 'min:1'],
            'days_of_week.*' => ['integer', 'between:1,7'],
            'booking_cutoff_minutes_before' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'days_of_week' => array_map('intval', $this->input('days_of_week', [])),
        ]);
    }
}
