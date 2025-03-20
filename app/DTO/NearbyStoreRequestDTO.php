<?php

namespace App\DTO;

class NearbyStoreRequestDTO
{
    public float $latitude;
    public float $longitude;
    public float $radius;

    /**
     * NearbyStoreRequestDTO, used for request those closest to a store.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->latitude = (float) $data['latitude'];
        $this->longitude = (float) $data['longitude'];
        $this->radius = (float) $data['radius'];
    }
}
