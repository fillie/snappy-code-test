<?php

namespace Tests\Unit\DTO;

use App\DTO\StoreDTO;
use App\Enums\StoreStatus;
use App\Enums\StoreType;
use PHPUnit\Framework\TestCase;

class StoreDTOTest extends TestCase
{
    public function testStoreDTOInitialisation()
    {
        $dto = new StoreDTO(
            name: 'Test Store',
            latitude: 51.5074,
            longitude: 0.1278,
            status: StoreStatus::OPEN,
            type: StoreType::SHOP,
            maxDeliveryDistance: 10.5
        );

        $this->assertEquals('Test Store', $dto->name);
        $this->assertEquals(51.5074, $dto->latitude);
        $this->assertEquals(0.1278, $dto->longitude);
        $this->assertEquals(StoreStatus::OPEN, $dto->status);
        $this->assertEquals(StoreType::SHOP, $dto->type);
        $this->assertEquals(10.5, $dto->maxDeliveryDistance);
    }
}
