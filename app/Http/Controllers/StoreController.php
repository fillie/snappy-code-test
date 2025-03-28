<?php

namespace App\Http\Controllers;

use App\DTO\DeliverableRequestDTO;
use App\DTO\NearbyStoreRequestDTO;
use App\Enums\StoreStatus;
use App\Enums\StoreType;
use App\Http\Requests\CreateStore;
use App\Http\Requests\Deliverable;
use App\Http\Requests\NearbyStore;
use App\Http\Resources\StoreResource;
use App\Services\StoreService;
use App\DTO\StoreDTO;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\JsonResponse;
use Exception;
use Psr\Log\LoggerInterface;

class StoreController extends Controller
{
    protected StoreService $storeService;
    protected LoggerInterface $logger;

    /**
     * Inject the StoreService and LoggerInterface via dependency injection.
     *
     * @param StoreService $storeService
     * @param LoggerInterface $logger
     */
    public function __construct(StoreService $storeService, LoggerInterface $logger)
    {
        $this->storeService = $storeService;
        $this->logger = $logger;
    }

    /**
     * Creates a new store.
     *
     * @param CreateStore $request
     * @return JsonResponse
     */
    public function store(CreateStore $request): JsonResponse
    {
        try {
            $store = $this->storeService->createStore(new StoreDTO(
                name: $request->input('name'),
                latitude: (float) $request->input('latitude'),
                longitude: (float) $request->input('longitude'),
                status: StoreStatus::from($request->input('status')),
                type: StoreType::from($request->input('type')),
                maxDeliveryDistance: (float) $request->input('max_delivery_distance'),
            ));
        } catch (Exception $e) {
            $this->logger->error('Error creating store: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'An error occurred while creating the store.'
            ], 500);
        }

        return response()->json(new StoreResource($store), 201);
    }

    /**
     * Returns all stores around a certain point and distance, using lat and lng.
     *
     * @param NearbyStore $request
     * @return JsonResponse
     */
    public function nearby(NearbyStore $request): JsonResponse
    {
        try {
            $stores = $this->storeService->getNearbyStores(new NearbyStoreRequestDTO(
                latitude: $request->input('latitude'),
                longitude: $request->input('longitude'),
                radius: $request->input('radius'),
            ));
        } catch (Exception $e) {
            $this->logger->error('Error searching nearby store: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'An error occurred while searching nearby store.'
            ], 500);
        }

        return response()->json(StoreResource::collection($stores));
    }

    /**
     * Returns all stores around a certain postcode.
     *
     * @param Deliverable $request
     * @return JsonResponse
     */
    public function deliverable(Deliverable $request): JsonResponse
    {
        try {
            $stores = $this->storeService->getDeliverableStores(new DeliverableRequestDTO(
                postcode: $request->input('postcode'),
            ));
        } catch (Exception $e) {
            $this->logger->error('Error searching stores for postcode: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'An error occurred while searching stores for given postcode.'
            ], 500);
        }

        return response()->json(StoreResource::collection($stores));
    }
}
