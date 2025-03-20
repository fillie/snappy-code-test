<?php

namespace Tests\Feature\Controllers;

use App\DTO\StoreDTO;
use App\Models\User;
use App\Services\StoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Psr\Log\LoggerInterface;
use Exception;

class StoreControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testStoreSuccess()
    {
        $data = [
            'name' => 'Test Store',
            'latitude' => 51.5074,
            'longitude' => 0.1278,
            'status' => 'open',
            'type' => 'shop',
            'max_delivery_distance' => 10.5
        ];

        $storeDTO = new StoreDTO($data);

        $storeServiceMock = $this->createMock(StoreService::class);
        $storeServiceMock->method('createStore')
            ->willReturn($storeDTO);

        App::instance(StoreService::class, $storeServiceMock);
        $loggerMock = $this->createMock(LoggerInterface::class);
        App::instance(LoggerInterface::class, $loggerMock);

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/stores', $data);

        $response->assertStatus(201);
        $response->assertJson((array) $storeDTO);
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testStoreFailure()
    {
        $data = [
            'name' => 'Test Store',
            'latitude' => 51.5074,
            'longitude' => 0.1278,
            'status' => 'open',
            'type' => 'shop',
            'max_delivery_distance' => 10.5
        ];

        $storeServiceMock = $this->createMock(StoreService::class);
        $storeServiceMock->method('createStore')
            ->will($this->throwException(new Exception('Test error')));

        App::instance(StoreService::class, $storeServiceMock);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error creating store: Test error'), $this->arrayHasKey('exception'));
        App::instance(LoggerInterface::class, $loggerMock);

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/stores', $data);
        $response->assertStatus(500);
        $response->assertJson([
            'error' => 'An error occurred while creating the store.'
        ]);
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testNearbySuccess()
    {
        $data = [
            'latitude' => 51.5074,
            'longitude' => 0.1278,
            'radius' => 10
        ];

        $fakeNearbyStoreDTO = new StoreDTO([
            'name' => 'Nearby Store',
            'latitude' => 51.5075,
            'longitude' => 0.1279,
            'status' => 'open',
            'type' => 'shop',
            'max_delivery_distance' => 12
        ]);

        $expectedResult = [ (array) $fakeNearbyStoreDTO ];

        $storeServiceMock = $this->createMock(StoreService::class);
        $storeServiceMock->method('getNearbyStores')
            ->willReturn(collect($expectedResult));

        App::instance(StoreService::class, $storeServiceMock);
        $loggerMock = $this->createMock(LoggerInterface::class);
        App::instance(LoggerInterface::class, $loggerMock);

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Append query parameters to the URL
        $url = '/api/stores/nearby?' . http_build_query($data);
        $response = $this->getJson($url);
        $response->assertStatus(200);
        $response->assertJson($expectedResult);
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testNearbyFailure()
    {
        $data = [
            'latitude' => 51.5074,
            'longitude' => 0.1278,
            'radius' => 10
        ];

        $storeServiceMock = $this->createMock(StoreService::class);
        $storeServiceMock->method('getNearbyStores')
            ->will($this->throwException(new Exception('Test error')));

        App::instance(StoreService::class, $storeServiceMock);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error searching nearby store: Test error'), $this->arrayHasKey('exception'));
        App::instance(LoggerInterface::class, $loggerMock);

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $url = '/api/stores/nearby?' . http_build_query($data);
        $response = $this->getJson($url);
        $response->assertStatus(500);
        $response->assertJson([
            'error' => 'An error occurred while searching nearby store.'
        ]);
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testDeliverableSuccess()
    {
        $data = [
            'postcode' => 'SW1A 1AA'
        ];

        $fakeDeliverableStoreDTO = new StoreDTO([
            'name' => 'Deliverable Store',
            'latitude' => 51.5014,
            'longitude' => -0.1419,
            'status' => 'open',
            'type' => 'restaurant',
            'max_delivery_distance' => 15
        ]);

        $expectedResult = [(array) $fakeDeliverableStoreDTO];

        $storeServiceMock = $this->createMock(StoreService::class);
        $storeServiceMock->method('getDeliverableStores')
            ->willReturn(collect($expectedResult));

        App::instance(StoreService::class, $storeServiceMock);
        $loggerMock = $this->createMock(LoggerInterface::class);
        App::instance(LoggerInterface::class, $loggerMock);

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $url = '/api/stores/deliverable?' . http_build_query($data);
        $response = $this->getJson($url);
        $response->assertStatus(200);
        $response->assertJson($expectedResult);
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testDeliverableFailure()
    {
        $data = [
            'postcode' => 'SW1A1AA'
        ];

        $storeServiceMock = $this->createMock(StoreService::class);
        $storeServiceMock->method('getDeliverableStores')
            ->will($this->throwException(new Exception('Test error')));

        App::instance(StoreService::class, $storeServiceMock);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error searching stores for postcode: Test error'), $this->arrayHasKey('exception'));
        App::instance(LoggerInterface::class, $loggerMock);

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $url = '/api/stores/deliverable?' . http_build_query($data);
        $response = $this->getJson($url);
        $response->assertStatus(500);
        $response->assertJson([
            'error' => 'An error occurred while searching stores for given postcode.'
        ]);
    }
}
