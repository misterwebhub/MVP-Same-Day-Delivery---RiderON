<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'label' => $this->label,
            'name' => $this->name,
            'phone' => $this->phone,
            'station_id' => $this->station_id,
            'landmark' => $this->landmark,
        ];
    }
}
