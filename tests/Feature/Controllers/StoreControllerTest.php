<?php

namespace Tests\Feature\Controllers;

use App\DTO\StoreDTO;
use App\Enums\StoreStatus;
use App\Enums\StoreType;
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

    public function testStoreSuccess()
    {
        $data = [
            'name' => 'Test Store',
            'latitude' => 51.5074,
            'longitude' => 0.1278,
            'status' => StoreStatus::OPEN->value,
            'type' => StoreType::SHOP->value,
            'max_delivery_distance' => 10.5
        ];
        
        $storeDTO = new StoreDTO(
            $data['name'],
            $data['latitude'],
            $data['longitude'],
            StoreStatus::from($data['status']),
            StoreType::from($data['type']),
            $data['max_delivery_distance']
        );

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
        $response->assertJson([
            'name' => $storeDTO->name,
            'latitude' => $storeDTO->latitude,
            'longitude' => $storeDTO->longitude,
            'status' => $storeDTO->status->value,
            'type' => $storeDTO->type->value,
            'max_delivery_distance' => $storeDTO->maxDeliveryDistance,
        ]);
    }

    public function testStoreFailure()
    {
        $data = [
            'name' => 'Test Store',
            'latitude' => 51.5074,
            'longitude' => 0.1278,
            'status' => StoreStatus::OPEN->value,
            'type' => StoreType::SHOP->value,
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

    public function testNearbySuccess()
    {
        $data = [
            'latitude' => 51.5074,
            'longitude' => 0.1278,
            'radius' => 10
        ];

        // Create a fake StoreDTO instance using explicit parameters:
        $fakeNearbyStoreDTO = new StoreDTO(
            'Nearby Store',
            51.5074,
            0.1278,
            StoreStatus::OPEN,
            StoreType::SHOP,
            10.5
        );

        $storeServiceMock = $this->createMock(StoreService::class);
        $storeServiceMock->method('getNearbyStores')
            ->willReturn(collect([$fakeNearbyStoreDTO]));

        App::instance(StoreService::class, $storeServiceMock);
        $loggerMock = $this->createMock(LoggerInterface::class);
        App::instance(LoggerInterface::class, $loggerMock);

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $url = '/api/stores/nearby?' . http_build_query($data);
        $response = $this->getJson($url);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Nearby Store',
            'latitude' => 51.5074,
            'longitude' => 0.1278,
            'status' => StoreStatus::OPEN->value,
            'type' => StoreType::SHOP->value,
            'max_delivery_distance' => 10.5
        ]);
    }

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

    public function testDeliverableSuccess()
    {
        $data = [
            'postcode' => 'SW1A 1AA'
        ];

        $fakeDeliverableStoreDTO = new StoreDTO(
            'Deliverable Store',
            51.5074,
            0.1278,
            StoreStatus::OPEN,
            StoreType::SHOP,
            10.5
        );

        $storeServiceMock = $this->createMock(StoreService::class);
        $storeServiceMock->method('getDeliverableStores')
            ->willReturn(collect([$fakeDeliverableStoreDTO]));

        App::instance(StoreService::class, $storeServiceMock);
        $loggerMock = $this->createMock(LoggerInterface::class);
        App::instance(LoggerInterface::class, $loggerMock);

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $url = '/api/stores/deliverable?' . http_build_query($data);
        $response = $this->getJson($url);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Deliverable Store',
            'latitude' => 51.5074,
            'longitude' => 0.1278,
            'status' => StoreStatus::OPEN->value,
            'type' => StoreType::SHOP->value,
            'max_delivery_distance' => 10.5
        ]);
    }

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
