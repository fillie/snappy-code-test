<?php

namespace App\Repositories\Eloquent;

use App\Models\Postcode;
use App\Repositories\Contracts\PostcodeRepositoryInterface;

class EloquentPostcodeRepository implements PostcodeRepositoryInterface
{
    /**
     * @var Postcode
     */
    protected Postcode $model;

    /**
     * @param Postcode $model
     */
    public function __construct(Postcode $model)
    {
        $this->model = $model;
    }

    /**
     * @param array $chunk
     * @return void
     */
    public function insert(array $chunk): void
    {
        $this->model->insert($chunk);
    }

    /**
     * @param string $postcode
     * @return array|null
     */
    public function findCoordinatesByPostcode(string $postcode): ?array
    {
        $record = $this->model->where('postcode', $postcode)->first();

        if (!$record) {
            return null;
        }

        return [
            'latitude'  => $record->latitude,
            'longitude' => $record->longitude,
        ];
    }
}
