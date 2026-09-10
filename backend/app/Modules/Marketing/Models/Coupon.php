<?php
namespace App\Modules\Marketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes;
    protected $table = 'coupons';
    protected $fillable = ['name', 'type', 'value', 'min_amount', 'total_count', 'used_count', 'start_at', 'end_at', 'status', 'sort', 'description'];
    protected $casts = ['status' => 'integer'];
}
