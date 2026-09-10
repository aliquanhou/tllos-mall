<?php
namespace App\Modules\Decorate\Models;

use Illuminate\Database\Eloquent\Model;

class PageTemplate extends Model
{
    protected $table = 'page_templates';

    protected $fillable = [
        'title', 'slug', 'config', 'draft_config',
        'version', 'is_published', 'is_default'
    ];

    protected $casts = [
        'config' => 'array',
        'draft_config' => 'array',
        'version' => 'integer',
        'is_published' => 'integer',
        'is_default' => 'integer',
    ];

    public function versions()
    {
        return $this->hasMany(PageTemplateVersion::class, 'template_id');
    }
}
