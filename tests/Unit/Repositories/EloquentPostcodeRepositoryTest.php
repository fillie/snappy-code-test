<?php

namespace Tests\Unit\Repositories;

use App\Models\Postcode;
use App\Repositories\Eloquent\EloquentPostcodeRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentPostcodeRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function testFindCoordinatesByPostcodeReturnsCoordinates()
    {
        Postcode::create([
            'postcode' => 'AB123CD',
            'latitude' => 51.5000,
            'longitude' => -0.1200,
        ]);

        $repository = new EloquentPostcodeRepository(new Postcode());

        $result = $repository->findCoordinatesByPostcode('AB123CD');

        $this->assertIsArray($result);
        $this->assertEquals(51.5000, $result['latitude']);
        $this->assertEquals(-0.1200, $result['longitude']);
    }

    /**
     * @return void
     */
    public function testFindCoordinatesByPostcodeReturnsNullWhenNotFound()
    {
        $repository = new EloquentPostcodeRepository(new Postcode());

        $result = $repository->findCoordinatesByPostcode('INVALID');

        $this->assertNull($result);
    }
}
