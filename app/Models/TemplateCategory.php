<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateCategory extends Model
{
    /** @use HasFactory<\Database\Factories\TemplateCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<Template, $this> */
    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
