<?php
namespace App\Modules\Decorate\Models;

use Illuminate\Database\Eloquent\Model;

class PageTemplateVersion extends Model
{
    protected $table = 'page_template_versions';

    protected $fillable = [
        'template_id', 'version', 'config', 'published_at'
    ];

    protected $casts = [
        'config' => 'array',
        'version' => 'integer',
        'published_at' => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(PageTemplate::class, 'template_id');
    }
}
