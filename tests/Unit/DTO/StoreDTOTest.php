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
        $data = [
            'name' => 'Test Store',
            'latitude' => '51.5074',
            'longitude' => 0.1278,
            'status' => 'open',
            'type' => 'shop',
            'max_delivery_distance' => '10.5',
        ];

        $dto = new StoreDTO($data);

        $this->assertEquals('Test Store', $dto->name);
        $this->assertEquals(51.5074, $dto->latitude);
        $this->assertEquals(0.1278, $dto->longitude);
        $this->assertEquals(StoreStatus::OPEN, $dto->status);
        $this->assertEquals(StoreType::SHOP, $dto->type);
        $this->assertEquals(10.5, $dto->maxDeliveryDistance);
    }
}
