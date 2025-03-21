<?php

namespace App\Repositories\Contracts;

interface PostcodeRepositoryInterface
{
    /**
     * @param array $chunk
     * @return void
     */
    public function insert(array $chunk): void;
    /**
     * @param string $postcode
     * @return array|null
     */
    public function findCoordinatesByPostcode(string $postcode): ?array;
}
