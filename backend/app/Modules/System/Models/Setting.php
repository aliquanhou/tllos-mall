<?php
namespace App\Modules\System\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Setting extends Model
{
    use SoftDeletes;
    protected $table = 'settings';
    protected $fillable = ['*'];
    protected $guarded = [];
}
