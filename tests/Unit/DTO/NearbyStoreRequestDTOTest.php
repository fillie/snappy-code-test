<?php

namespace Tests\Unit\DTO;

use App\DTO\NearbyStoreRequestDTO;
use PHPUnit\Framework\TestCase;

class NearbyStoreRequestDTOTest extends TestCase
{
    public function testNearbyStoreRequestDTOInitialisation()
    {
        $data = [
            'latitude' => '51.5074',
            'longitude' => 0.1278,
            'radius' => 5,
        ];

        $dto = new NearbyStoreRequestDTO(
            (float) $data['latitude'],
            (float) $data['longitude'],
            (float) $data['radius']
        );

        $this->assertEquals(51.5074, $dto->latitude);
        $this->assertEquals(0.1278, $dto->longitude);
        $this->assertEquals(5, $dto->radius);
    }
}
