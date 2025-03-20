<?php

namespace Tests\Unit\Services;

use App\DTO\NearbyStoreRequestDTO;
use App\Models\Store;
use App\Services\PostcodeService;
use App\Services\StoreService;
use App\DTO\StoreDTO;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class StoreServiceTest extends TestCase
{
    public function testCreateStore()
    {
        $data = [
            'name' => 'Test Store',
            'latitude' => 51.5074,
            'longitude' => 0.1278,
            'status' => 'open',
            'type' => 'shop',
            'max_delivery_distance' => 10.5,
        ];
        $dto = new StoreDTO($data);

        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->addMethods(['create'])
            ->getMock();

        $postcodeServiceMock = $this->getMockBuilder(PostcodeService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $expectedStore = (object) $data;
        $storeMock->expects($this->once())
            ->method('create')
            ->with([
                'name' => $dto->name,
                'latitude' => $dto->latitude,
                'longitude' => $dto->longitude,
                'status' => $dto->status,
                'type' => $dto->type,
                'max_delivery_distance' => $dto->maxDeliveryDistance,
            ])
            ->willReturn($expectedStore);

        $storeService = new StoreService($storeMock, $postcodeServiceMock);
        $result = $storeService->createStore($dto);
        $this->assertEquals($dto, $result);
    }

    public function testGetNearbyStores()
    {
        $data = [
            'latitude' => 51.5074,
            'longitude' => 0.1278,
            'radius' => 10,
        ];
        $nearbyDTO = new NearbyStoreRequestDTO($data);
        $fakeStoreData = [
            'id' => 1,
            'name' => 'Nearby Test Store',
            'latitude' => 51.5075,
            'longitude' => 0.1280,
            'status' => 'open',
            'type' => 'shop',
            'max_delivery_distance' => 15,
            'distance' => 5.0,
        ];

        // Create a fake store model with a toArray method.
        $fakeStoreModel = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['toArray'])
            ->getMock();
        $fakeStoreModel->expects($this->once())
            ->method('toArray')
            ->willReturn($fakeStoreData);

        // Build a query builder mock with the required methods.
        $queryBuilderMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['whereBetween', 'selectRaw', 'havingRaw', 'orderBy', 'get'])
            ->getMock();

        $calls = [];
        $queryBuilderMock->method('whereBetween')
            ->willReturnCallback(function ($field, $range) use (&$calls, $queryBuilderMock) {
                $calls[] = [$field, $range];
                return $queryBuilderMock;
            });
        $queryBuilderMock->expects($this->once())
            ->method('selectRaw')
            ->with($this->anything(), $this->anything())
            ->willReturnSelf();
        $queryBuilderMock->expects($this->once())
            ->method('havingRaw')
            ->with('distance <= max_delivery_distance')
            ->willReturnSelf();
        $queryBuilderMock->expects($this->once())
            ->method('orderBy')
            ->with('distance', 'asc')
            ->willReturnSelf();
        $queryBuilderMock->expects($this->once())
            ->method('get')
            ->willReturn(new EloquentCollection([$fakeStoreModel]));

        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['newQuery'])
            ->getMock();
        $storeMock->expects($this->once())
            ->method('newQuery')
            ->willReturn($queryBuilderMock);

        $postcodeServiceMock = $this->getMockBuilder(PostcodeService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeService = new StoreService($storeMock, $postcodeServiceMock);
        $result = $storeService->getNearbyStores($nearbyDTO);

        // Verify that whereBetween was called for both 'latitude' and 'longitude'
        $this->assertCount(2, $calls);
        $fields = array_column($calls, 0);
        $this->assertContains('latitude', $fields);
        $this->assertContains('longitude', $fields);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $storeDTO = $result->first();
        $this->assertInstanceOf(StoreDTO::class, $storeDTO);
        $this->assertEquals($fakeStoreData['name'], $storeDTO->name);
    }
}
