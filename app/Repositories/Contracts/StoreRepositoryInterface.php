<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;
use App\DTO\StoreDTO;

interface StoreRepositoryInterface
{
    /**
     * @param array $storeData
     * @return StoreDTO
     */
    public function create(array $storeData): StoreDTO;

    /**
     * @param array $boundaries
     * @param float $lat
     * @param float $lng
     * @return Collection
     */
    public function searchWithinBounds(array $boundaries, float $lat, float $lng): Collection;
}
