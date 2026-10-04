<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return AdminAccess::canManageMasterData();
    }

    public function rules(): array
    {
        $routeId = $this->route('route')?->id;

        return [
            'origin_station_id' => [
                'required',
                'integer',
                'exists:stations,id',
                Rule::unique('routes', 'origin_station_id')
                    ->where('destination_station_id', $this->input('destination_station_id'))
                    ->whereNull('deleted_at')
                    ->ignore($routeId),
            ],
            'destination_station_id' => ['required', 'integer', 'exists:stations,id', Rule::notIn([(int) $this->input('origin_station_id')])],
            'distance_km' => ['required', 'numeric', 'min:0'],
            'estimated_duration_minutes' => ['required', 'integer', 'min:1'],
            'cutoff_time' => ['required', 'date_format:H:i'],
            'max_parcels_per_schedule' => ['required', 'integer', 'min:1'],
            'waiting_time_minutes' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'destination_station_id.not_in' => 'Destination station must be different from the origin station.',
            'origin_station_id.unique' => 'A route between these two stations already exists.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
