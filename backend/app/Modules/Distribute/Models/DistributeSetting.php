<?php
namespace App\Modules\Distribute\Models;
use Illuminate\Database\Eloquent\Model;
class DistributeSetting extends Model {
    protected $table = 'distribute_settings';
    protected $fillable = ['key','value'];
}
