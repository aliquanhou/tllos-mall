<?php
namespace App\Modules\Application\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Notice extends Model
{
    use SoftDeletes;
    protected $table = 'notices';
    protected $fillable = ['*'];
    protected $guarded = [];
}
