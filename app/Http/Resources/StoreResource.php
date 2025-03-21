<?php

namespace App\Http\Resources;

use App\DTO\StoreDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
    /**
     * Transform the DTO into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        /** @var StoreDTO $this */
        return [
            'name' => $this->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status->value,
            'type' => $this->type->value,
            'max_delivery_distance' => $this->maxDeliveryDistance,
        ];
    }
}
