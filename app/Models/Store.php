<?php

namespace App\Models;

use App\Enums\StoreStatus;
use App\Enums\StoreType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'status',
        'type',
        'max_delivery_distance',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'status' => StoreStatus::class,
        'type' => StoreType::class,
        'max_delivery_distance' => 'float',
    ];
}
