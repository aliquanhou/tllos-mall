<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    protected $fillable = [
        'user_id', 'name', 'mobile',
        'province_id', 'city_id', 'district_id',
        'province_name', 'city_name', 'district_name',
        'detail', 'postal_code', 'is_default',
    ];

    protected $casts = [
        'is_default' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
