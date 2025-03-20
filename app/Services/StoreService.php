<?php

namespace App\Services;

use App\Models\Store;
use App\DTO\StoreDTO;

class StoreService
{
    protected Store $store;

    /**
     * Inject the Store model.
     *
     * @param Store $store
     */
    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    /**
     * Create a new store using the data from StoreDTO.
     *
     * @param StoreDTO $storeData
     * @return StoreDTO
     */
    public function createStore(StoreDTO $storeData): StoreDTO
    {
        $store = $this->store->create([
            'name' => $storeData->name,
            'latitude' => $storeData->latitude,
            'longitude' => $storeData->longitude,
            'status' => $storeData->status,
            'type' => $storeData->type,
            'max_delivery_distance' => $storeData->maxDeliveryDistance,
        ]);

        return new StoreDTO((array) $store);
    }
}
