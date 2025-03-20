<?php

namespace App\DTO;

class DeliverableDTO
{
    public string $postcode;

    public function __construct(array $data)
    {
        $this->postcode = $data['postcode'];
    }
}
