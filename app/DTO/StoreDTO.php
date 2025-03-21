<?php

namespace App\DTO;

use App\Enums\StoreStatus;
use App\Enums\StoreType;

class StoreDTO
{
    public string $name;
    public float $latitude;
    public float $longitude;
    public StoreStatus $status;
    public StoreType $type;
    public float $maxDeliveryDistance;

    /**
     * StoreDTO constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->latitude = (float) $data['latitude'];
        $this->longitude = (float) $data['longitude'];
        $this->status = StoreStatus::from($data['status']);
        $this->type = StoreType::from($data['type']);
        $this->maxDeliveryDistance = (float) $data['max_delivery_distance'];
    }
}
