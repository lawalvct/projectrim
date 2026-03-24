<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeNav($query)
    {
        return $query->whereIn('position', ['nav', 'both']);
    }

    public function scopeFooter($query)
    {
        return $query->whereIn('position', ['footer', 'both']);
    }
}
