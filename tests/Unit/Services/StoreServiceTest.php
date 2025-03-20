<?php

namespace Tests\Unit\Services;

use App\Models\Store;
use App\Services\StoreService;
use App\DTO\StoreDTO;
use PHPUnit\Framework\TestCase;

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

        $storeService = new StoreService($storeMock);
        $result = $storeService->createStore($dto);

        $this->assertEquals($dto, $result);
    }
}
