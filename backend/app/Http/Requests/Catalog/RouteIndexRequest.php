<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class RouteIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'origin_station_id' => ['nullable', 'required_with:destination_station_id', 'integer', 'exists:stations,id'],
            'destination_station_id' => ['nullable', 'required_with:origin_station_id', 'integer', 'exists:stations,id'],
        ];
    }
}
