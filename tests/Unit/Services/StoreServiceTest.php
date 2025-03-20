<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCase;
use App\Services\StoreService;
use App\Services\PostcodeService;
use App\Repositories\Contracts\StoreRepositoryInterface;
use App\DTO\StoreDTO;
use App\DTO\NearbyStoreRequestDTO;
use App\DTO\DeliverableRequestDTO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreServiceTest extends TestCase
{
    /**
     * @return void
     * @throws Exception
     */
    public function testCreateStore()
    {
        $storeData = new StoreDTO([
            'name' => 'Test Store',
            'latitude' => 51.5,
            'longitude' => -0.12,
            'status' => 'open',
            'type' => 'shop',
            'max_delivery_distance' => 10,
        ]);

        $storeRepositoryMock = $this->createMock(StoreRepositoryInterface::class);
        $postcodeServiceMock = $this->createMock(PostcodeService::class);

        $storeRepositoryMock->expects($this->once())
            ->method('create')
            ->with($this->isType('array'))
            ->willReturn($storeData);

        $service = new StoreService($storeRepositoryMock, $postcodeServiceMock);
        $result = $service->createStore($storeData);

        $this->assertEquals($storeData->name, $result->name);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testGetNearbyStores()
    {
        $nearbyDTO = new NearbyStoreRequestDTO([
            'latitude' => 51.5,
            'longitude' => -0.12,
            'radius' => 10,
        ]);

        $storeRepositoryMock = $this->createMock(StoreRepositoryInterface::class);
        $postcodeServiceMock = $this->createMock(PostcodeService::class);

        $expectedCollection = collect([new StoreDTO([
            'name' => 'Nearby Store',
            'latitude' => 51.5,
            'longitude' => -0.12,
            'status' => 'open',
            'type' => 'shop',
            'max_delivery_distance' => 10,
        ])]);

        $storeRepositoryMock->expects($this->once())
            ->method('searchWithinBounds')
            ->willReturn($expectedCollection);

        $service = new StoreService($storeRepositoryMock, $postcodeServiceMock);
        $result = $service->getNearbyStores($nearbyDTO);

        $this->assertCount(1, $result);
        $this->assertEquals('Nearby Store', $result->first()->name);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testGetDeliverableStoresThrowsIfPostcodeNotFound()
    {
        $storeRepositoryMock = $this->createMock(StoreRepositoryInterface::class);
        $postcodeServiceMock = $this->createMock(PostcodeService::class);

        $postcodeServiceMock->expects($this->once())
            ->method('getCoordinatesByPostcode')
            ->with('INVALID')
            ->willReturn(null);

        $service = new StoreService($storeRepositoryMock, $postcodeServiceMock);

        $this->expectException(NotFoundHttpException::class);

        $service->getDeliverableStores(new DeliverableRequestDTO(['postcode' => 'INVALID']));
    }
}
