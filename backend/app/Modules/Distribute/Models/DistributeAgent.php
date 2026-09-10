<?php
namespace App\Modules\Distribute\Models;
use Illuminate\Database\Eloquent\Model;
class DistributeAgent extends Model {
    protected $table = 'distribute_agents';
    protected $fillable = ['user_id','level_id','parent_id','real_name','mobile','status','total_income','available_income','total_orders','total_members','apply_at','audit_at'];
    protected $casts = ['status'=>'integer'];
}
