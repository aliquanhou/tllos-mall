<?php
namespace App\Modules\Distribute\Models;
use Illuminate\Database\Eloquent\Model;
class DistributeLevel extends Model {
    protected $table = 'distribute_levels';
    protected $fillable = ['name','level','commission_rate','self_rate','first_rate','second_rate','third_rate','upgrade_orders','upgrade_amount','status','sort'];
    protected $casts = ['status'=>'integer'];
}
