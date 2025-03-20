<?php

namespace App\Services;

use App\Models\Postcode;

class PostcodeService
{
    protected Postcode $postcode;

    public function __construct(Postcode $postcode)
    {
        $this->postcode = $postcode;
    }

    /**
     * Get the coordinates for a given postcode.
     *
     * @param string $postcode
     * @return array|null Returns an array with keys 'latitude' and 'longitude' or null if not found.
     */
    public function getCoordinatesByPostcode(string $postcode): ?array
    {
        $record = $this->postcode->where('postcode', $postcode)->first();

        if (!$record) {
            return null;
        }

        return [
            'latitude'  => $record->latitude,
            'longitude' => $record->longitude,
        ];
    }
}
