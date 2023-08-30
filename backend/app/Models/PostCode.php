<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'postcode',
        'latitude',
        'longitude',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function getLatitudeAttribute($value): float
    {
        return (float) $value;
    }

    public function getLongitudeAttribute($value): float
    {
        return (float) $value;
    }

    public function setLatitudeAttribute($value): void
    {
        $this->attributes['latitude'] = (float) $value;
    }

    public function setLongitudeAttribute($value): void
    {
        $this->attributes['longitude'] = (float) $value;
    }

    public function getDistanceFrom(PostCode $postCode): float
    {
        $theta = $this->longitude - $postCode->longitude;
        $distance = sin(deg2rad($this->latitude)) * sin(deg2rad($postCode->latitude)) + cos(deg2rad($this->latitude)) * cos(deg2rad($postCode->latitude)) * cos(deg2rad($theta));
        $distance = acos($distance);
        $distance = rad2deg($distance);
        $distance = $distance * 60 * 1.1515;

        return (float) $distance;
    }

    // Get postcodes near a location lat/long
    public function scopeCloseTo($query, $latitude, $longitude)
    {
        $query->select('post_codes.*')
            ->selectRaw('( 3959 * acos( cos( radians(?) ) *
                cos( radians( latitude ) )
                * cos( radians( longitude ) - radians(?)
                ) + sin( radians(?) ) *
                sin( radians( latitude ) ) )
                ) AS distance', [$latitude, $longitude, $latitude])
            ->havingRaw("distance < ?", [10])
            ->orderByRaw("distance");
    }
    
}
