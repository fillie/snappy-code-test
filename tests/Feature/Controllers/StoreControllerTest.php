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
        $data= [
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
        $this->actingAs($user,'sanctum');

        $response = $this->postJson('/api/stores' ,$data);
        $response->assertStatus(500);
        $response->assertJson([
            'error' => 'An error occurred while creating the store.'
        ]);
    }
}
