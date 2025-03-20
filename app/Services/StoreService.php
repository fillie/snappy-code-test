<?php

namespace App\Services;

use App\DTO\DeliverableRequestDTO;
use App\DTO\NearbyStoreRequestDTO;
use Exception;
use App\Models\Store;
use App\DTO\StoreDTO;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreService
{
    protected Store $store;
    protected PostcodeService $postcodeService;

    const DEFAULT_RADIUS = 10;

    /**
     * @param Store $store
     * @param PostcodeService $postcodeService
     */
    public function __construct(Store $store, PostcodeService $postcodeService)
    {
        $this->store = $store;
        $this->postcodeService = $postcodeService;
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

        $stores = $this->performSearch(
            $this->calculateMinMaxBoundaries($lat, $lng, $radius),
            $lat,
            $lng
        );

        return $stores->map(function($store) {
            return new StoreDTO($store->toArray());
        });
    }

    /**
     * Return all stores within delivery range against a postcode.
     *
     * @param DeliverableRequestDTO $deliverableDTO
     * @return EloquentCollection
     * @throws Exception
     */
    public function getDeliverableStores(DeliverableRequestDTO $deliverableDTO): Collection
    {
        $coordinates = $this->postcodeService->getCoordinatesByPostcode($deliverableDTO->postcode);
        if (!$coordinates) {
            throw new NotFoundHttpException('Postcode not found');
        }
        $lat = $coordinates['latitude'];
        $lng = $coordinates['longitude'];

        $stores = $this->performSearch(
            $this->calculateMinMaxBoundaries($lat, $lng, self::DEFAULT_RADIUS),
            $lat,
            $lng
        );

        return $stores->map(function($store) {
            return new StoreDTO($store->toArray());
        });
    }

    /**
     * Calculates the bounds for a given lat and lng.
     *
     * @param float $lat
     * @param float $lng
     * @param float $radius
     * @return float[]
     */
    private function calculateMinMaxBoundaries(float $lat, float $lng, float $radius): array
    {
        $earthRadius = 6371;

        $deltaLat = rad2deg($radius / $earthRadius);
        $deltaLng = rad2deg($radius / ($earthRadius * cos(deg2rad($lat))));
        $minLat = $lat - $deltaLat;
        $maxLat = $lat + $deltaLat;
        $minLng = $lng - $deltaLng;
        $maxLng = $lng + $deltaLng;

        return [
            'minLat' => $minLat,
            'minLng' => $minLng,
            'maxLat' => $maxLat,
            'maxLng' => $maxLng,
        ];
    }

    /**
     * Responsible for actually performing the SQL.
     *
     * TODO: Refactor this to use SQL Geospatial operations, and possibly this whole class to a repository
     *
     * @param array $boundaries
     * @param $lat
     * @param $lng
     * @return EloquentCollection
     */
    protected function performSearch(array $boundaries, $lat, $lng): EloquentCollection
    {
        return $this->store->newQuery()
            ->whereBetween('latitude', [$boundaries['minLat'], $boundaries['maxLat']])
            ->whereBetween('longitude', [$boundaries['minLng'], $boundaries['maxLng']])
            ->selectRaw("*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) as distance", [$lat, $lng, $lat])
            ->havingRaw('distance <= max_delivery_distance')
            ->orderBy('distance','asc')
            ->get();
    }
}
