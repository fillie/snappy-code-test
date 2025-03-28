<?php

namespace Tests\Unit\DTO;

use App\DTO\DeliverableRequestDTO;
use PHPUnit\Framework\TestCase;

class DeliverableRequestDTOTest extends TestCase
{
    public function testDeliverableRequestDTOInitialisation()
    {
        $data = [
            'postcode' => 'NE16 4PQ'
        ];

        $dto = new DeliverableRequestDTO($data['postcode']);

        $this->assertEquals('NE16 4PQ', $dto->postcode);
    }
}
