<?php
namespace App\Modules\Decorate\Models;
use Illuminate\Database\Eloquent\Model;
class DecorateTabbar extends Model {
    protected $table = 'decorate_tabbars';
    protected $fillable = ['name','icon','active_icon','link_type','link_value','sort','status'];
    protected $casts = ['status'=>'integer'];
}
