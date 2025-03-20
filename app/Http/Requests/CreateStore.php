<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateStore extends FormRequest
{
    /**
     * Validation rules for creating a store.
     * // todo replace with enum for type, status?
     *
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'status' => 'required|string|in:open,closed',
            'type' => 'required|string|in:takeaway,shop,restaurant',
            'max_delivery_distance' => 'required|numeric|min:0',
        ];
    }
}
