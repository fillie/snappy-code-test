<?php

namespace App\Services;

use App\Repositories\Contracts\PostcodeRepositoryInterface;

class PostcodeService
{
    /**
     * @var PostcodeRepositoryInterface
     */
    protected PostcodeRepositoryInterface $repository;

    /**
     * @param PostcodeRepositoryInterface $repository
     */
    public function __construct(PostcodeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param string $postcode
     * @return array|null
     */
    public function getCoordinatesByPostcode(string $postcode): ?array
    {
        return $this->repository->findCoordinatesByPostcode($postcode);
    }
}
