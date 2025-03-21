<?php

namespace App\Services;

use App\DTO\DeliverableRequestDTO;
use App\DTO\NearbyStoreRequestDTO;
use Exception;
use App\Repositories\Contracts\StoreRepositoryInterface;
use App\DTO\StoreDTO;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreService
{
    /**
     * @var StoreRepositoryInterface
     */

    protected StoreRepositoryInterface $storeRepository;
    /**
     * @var PostcodeService
     */
    protected PostcodeService $postcodeService;

    public const DEFAULT_RADIUS = 10;
    public const EARTH_RADIUS = 6371;

    /**
     * @param StoreRepositoryInterface $storeRepository
     * @param PostcodeService $postcodeService
     */
    public function __construct(StoreRepositoryInterface $storeRepository, PostcodeService $postcodeService)
    {
        $this->storeRepository = $storeRepository;
        $this->postcodeService = $postcodeService;
    }

    /**
     * @param StoreDTO $storeData
     * @return StoreDTO
     */
    public function createStore(StoreDTO $storeData): StoreDTO
    {
        return $this->storeRepository->create([
            'name' => $storeData->name,
            'latitude' => $storeData->latitude,
            'longitude' => $storeData->longitude,
            'status' => $storeData->status,
            'type' => $storeData->type,
            'max_delivery_distance' => $storeData->maxDeliveryDistance,
        ]);
    }

    /**
     * @param NearbyStoreRequestDTO $nearbyDTO
     * @return Collection
     */
    public function getNearbyStores(NearbyStoreRequestDTO $nearbyDTO): Collection
    {
        $boundaries = $this->calculateMinMaxBoundaries($nearbyDTO->latitude, $nearbyDTO->longitude, $nearbyDTO->radius);

        return $this->storeRepository->searchWithinBounds(
            $boundaries,
            $nearbyDTO->latitude,
            $nearbyDTO->longitude
        );
    }

    /**
     * @param DeliverableRequestDTO $deliverableDTO
     * @return Collection
     */
    public function getDeliverableStores(DeliverableRequestDTO $deliverableDTO): Collection
    {
        $coordinates = $this->postcodeService->getCoordinatesByPostcode($deliverableDTO->postcode);

        if (!$coordinates) {
            throw new NotFoundHttpException('Postcode not found');
        }

        $boundaries = $this->calculateMinMaxBoundaries($coordinates['latitude'], $coordinates['longitude'], self::DEFAULT_RADIUS);

        return $this->storeRepository->searchWithinBounds(
            $boundaries,
            $coordinates['latitude'],
            $coordinates['longitude']
        );
    }

    /**
     * @param float $lat
     * @param float $lng
     * @param float $radius
     * @return float[]
     */
    private function calculateMinMaxBoundaries(float $lat, float $lng, float $radius): array
    {
        $deltaLat = rad2deg($radius / self::EARTH_RADIUS);
        $deltaLng = rad2deg($radius / (self::EARTH_RADIUS * cos(deg2rad($lat))));

        return [
            'minLat' => $lat - $deltaLat,
            'maxLat' => $lat + $deltaLat,
            'minLng' => $lng - $deltaLng,
            'maxLng' => $lng + $deltaLng,
        ];
    }
}
