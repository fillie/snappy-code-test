<?php

namespace Tests\Unit\Repositories;

use App\Models\Store;
use App\Repositories\Eloquent\EloquentStoreRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentStoreRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testCreateStore()
    {
        $repository = new EloquentStoreRepository(new Store());

        $storeData = [
            'name' => 'Test Store',
            'latitude' => 51.5,
            'longitude' => -0.12,
            'status' => 'open',
            'type' => 'shop',
            'max_delivery_distance' => 10,
        ];

        $result = $repository->create($storeData);

        $this->assertEquals('Test Store', $result->name);
        $this->assertDatabaseHas('stores', ['name' => 'Test Store']);
    }

    /**
     * @return void
     */
    public function testSearchWithinBounds()
    {
        Store::factory()->create([
            'name' => 'In Bounds Store',
            'latitude' => 51.501,
            'longitude' => -0.121,
            'max_delivery_distance' => 5,
        ]);

        Store::factory()->create([
            'name' => 'Out of Bounds Store',
            'latitude' => 53.000,
            'longitude' => -2.000,
            'max_delivery_distance' => 5,
        ]);

        $repository = new EloquentStoreRepository(new Store());

        $boundaries = [
            'minLat' => 51.4,
            'maxLat' => 51.6,
            'minLng' => -0.2,
            'maxLng' => -0.1,
        ];

        $results = $repository->searchWithinBounds($boundaries, 51.5, -0.12);

        $this->assertCount(1, $results);
        $this->assertEquals('In Bounds Store', $results->first()->name);
    }
}
