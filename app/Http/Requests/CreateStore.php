<?php

namespace App\Http\Requests;

use App\Enums\StoreStatus;
use App\Enums\StoreType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateStore extends FormRequest
{
    /**
     * Validation rules for creating a store.
     *
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'status' => [
                'required',
                'string',
                Rule::enum(StoreStatus::class),
            ],
            'type' => [
                'required',
                'string',
                Rule::enum(StoreType::class)
            ],
            'max_delivery_distance' => 'required|numeric|min:0',
        ];
    }
}
