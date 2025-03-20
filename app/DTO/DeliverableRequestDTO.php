<?php

namespace App\DTO;

class DeliverableRequestDTO
{
    public string $postcode;

    public function __construct(array $data)
    {
        $this->postcode = $data['postcode'];
    }
}
