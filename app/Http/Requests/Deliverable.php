<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Deliverable extends FormRequest
{
    public function rules(): array
    {
        return [
            'postcode' => 'required|string|postal_code,UK',
        ];
    }
}
