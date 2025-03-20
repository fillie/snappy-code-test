<?php

namespace App\Http\Controllers;

use App\DTO\NearbyStoreRequestDTO;
use App\Http\Requests\CreateStore;
use App\Http\Requests\NearbyStoreRequest;
use App\Services\StoreService;
use App\DTO\StoreDTO;
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
            $store = $this->storeService->createStore(new StoreDTO($request->all()));
        } catch (Exception $e) {
            $this->logger->error('Error creating store: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'An error occurred while creating the store.'
            ], 500);
        }

        return response()->json($store, 201);
    }

    /**
     * Returns all stores around a certain point and distance, using lat and lng.
     *
     * @param NearbyStoreRequest $request
     * @return JsonResponse
     */
    public function nearby(NearbyStoreRequest $request): JsonResponse
    {
        try {
            $result = $this->storeService->getNearbyStores(new NearbyStoreRequestDTO($request->all()));
        } catch (Exception $e) {
            $this->logger->error('Error searching nearby store: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'An error occurred while searching nearby store.'
            ], 500);
        }

        return response()->json($result, 200);
    }
}
