<?php

namespace Tests\Unit\Http\Resources;

use App\DTO\StoreDTO;
use App\Enums\StoreStatus;
use App\Enums\StoreType;
use App\Http\Resources\StoreResource;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class StoreResourceTest extends TestCase
{
    /**
     * @return void
     */
    public function testStoreResourceTransformsDtoCorrectly()
    {
        $dto = new StoreDTO([
            'name' => 'Test Store',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'status' => StoreStatus::OPEN->value,
            'type' => StoreType::SHOP->value,
            'max_delivery_distance' => 15.5,
        ]);

        $resource = new StoreResource($dto);

        $expected = [
            'name' => 'Test Store',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'status' => 'open',
            'type' => 'shop',
            'max_delivery_distance' => 15.5,
        ];

        $actual = $resource->toArray(new Request());

        $this->assertEquals($expected, $actual);
    }
}
