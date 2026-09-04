<?php
namespace App\Modules\Announcement\Models;
use Illuminate\Database\Eloquent\Model;
class Announcement extends Model {
    protected $table = 'announcements';
    protected $fillable = ['title','content','type','status','sort','start_at','end_at'];
    protected $casts = ['status'=>'integer'];
}
