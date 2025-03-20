<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Deliverable extends FormRequest
{
    /**
     * TODO I would usually add proper validation on a postcode.
     *
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'postcode' => 'required|string',
        ];
    }
}
