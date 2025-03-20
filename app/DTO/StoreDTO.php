<?php

namespace App\DTO;

class StoreDTO
{
    public string $name;
    public float $latitude;
    public float $longitude;
    public string $status;
    // todo make enum?
    public string $type;
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
        $this->status = $data['status'];
        $this->type = $data['type'];
        $this->maxDeliveryDistance = (float) $data['max_delivery_distance'];
    }
}
