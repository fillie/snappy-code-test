<?php

namespace App\Repositories\Contracts;

interface PostcodeRepositoryInterface
{
    /**
     * @param string $postcode
     * @return array|null
     */
    public function findCoordinatesByPostcode(string $postcode): ?array;
}
