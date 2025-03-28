<?php

namespace App\DTO;

readonly class NearbyStoreRequestDTO
{
    /**
     * @param float $latitude
     * @param float $longitude
     * @param float $radius
     */
    public function __construct(
        public float $latitude,
        public float $longitude,
        public float $radius
    ) {}
}
