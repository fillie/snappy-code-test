<?php

namespace App\Services;

use App\DTO\NearbyStoreRequestDTO;
use App\Models\Store;
use App\DTO\StoreDTO;
use Illuminate\Support\Collection;

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

    /**
     * Search for nearby stores using a lat and lng.
     *
     * @param NearbyStoreRequestDTO $nearbyDTO
     * @return Collection
     */
    public function getNearbyStores(NearbyStoreRequestDTO $nearbyDTO): Collection
    {
        $lat = $nearbyDTO->latitude;
        $lng = $nearbyDTO->longitude;
        $radius = $nearbyDTO->radius;

        // Calculate bounding box for performance
        $earthRadius = 6371;
        $deltaLat = rad2deg($radius / $earthRadius);
        $deltaLng = rad2deg($radius / ($earthRadius * cos(deg2rad($lat))));
        $minLat = $lat - $deltaLat;
        $maxLat = $lat + $deltaLat;
        $minLng = $lng - $deltaLng;
        $maxLng = $lng + $deltaLng;

        // todo refactor to use sql
        $storeModels = $this->store->newQuery()
            ->whereBetween('latitude', [$minLat, $maxLat])
            ->whereBetween('longitude', [$minLng, $maxLng])
            ->selectRaw("*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) as distance", [$lat, $lng, $lat])
            ->having('distance', '<=', $radius)
            ->orderBy('distance', 'asc')
            ->get();

        return $storeModels->map(function($store) {
            return new StoreDTO($store->toArray());
        });
    }
}
