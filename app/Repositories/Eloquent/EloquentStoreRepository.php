<?php

namespace App\Repositories\Eloquent;

use App\Models\Store;
use App\Repositories\Contracts\StoreRepositoryInterface;
use Illuminate\Support\Collection;
use App\DTO\StoreDTO;

class EloquentStoreRepository implements StoreRepositoryInterface
{
    /**
     * @var Store
     */
    protected Store $model;

    /**
     * @param Store $model
     */
    public function __construct(Store $model)
    {
        $this->model = $model;
    }

    /**
     * @param array $storeData
     * @return StoreDTO
     */
    public function create(array $storeData): StoreDTO
    {
        $store = $this->model->create($storeData);

        return new StoreDTO($store->toArray());
    }

    /**
     * @param array $boundaries
     * @param float $lat
     * @param float $lng
     * @return Collection
     */
    public function searchWithinBounds(array $boundaries, float $lat, float $lng): Collection
    {
        $results = $this->model->newQuery()
            ->whereBetween('latitude', [$boundaries['minLat'], $boundaries['maxLat']])
            ->whereBetween('longitude', [$boundaries['minLng'], $boundaries['maxLng']])
            ->whereRaw(
                "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= max_delivery_distance",
                [$lat, $lng, $lat]
            )
            ->selectRaw(
                "*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) as distance",
                [$lat, $lng, $lat]
            )
            ->orderBy('distance', 'asc')
            ->get();

        return $results->map(fn($store) => new StoreDTO($store->toArray()));
    }
}
