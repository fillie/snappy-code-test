<?php

namespace App\DTO;

use App\Enums\StoreStatus;
use App\Enums\StoreType;

readonly class StoreDTO
{
    /**
     * @param string $name
     * @param float $latitude
     * @param float $longitude
     * @param StoreStatus $status
     * @param StoreType $type
     * @param float $maxDeliveryDistance
     */
    public function __construct(
        public string $name,
        public float $latitude,
        public float $longitude,
        public StoreStatus $status,
        public StoreType $type,
        public float $maxDeliveryDistance
    ) {}
}
