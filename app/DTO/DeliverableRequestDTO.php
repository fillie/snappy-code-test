<?php

namespace App\DTO;

readonly class DeliverableRequestDTO
{
    /**
     * @param string $postcode
     */
    public function __construct(
        public string $postcode
    ) {}
}
