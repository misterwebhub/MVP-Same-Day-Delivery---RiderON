<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StationResource;
use App\Http\Resources\CityResource;
use App\Models\City;
use Illuminate\Http\JsonResponse;

class CityController extends Controller
{
    public function index(): JsonResponse
    {
        $cities = City::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return $this->success(CityResource::collection($cities));
    }

    public function stations(City $city): JsonResponse
    {
        $stations = $city->stations()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return $this->success(StationResource::collection($stations));
    }
}
