<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_paid' => 'boolean',
            'is_featured' => 'boolean',
            'views_count' => 'integer',
            'downloads_count' => 'integer',
            'likes_count' => 'integer',
            'date_available' => 'date',
            'published_at' => 'datetime',
        ];
    }

    // --- Scopes ---

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // --- Relationships ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProductFile::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tag');
    }

    public function authors(): HasMany
    {
        return $this->hasMany(ProductAuthor::class);
    }

    public function primaryAuthor(): HasMany
    {
        return $this->hasMany(ProductAuthor::class)->where('is_primary', true);
    }

    public function coAuthors(): HasMany
    {
        return $this->hasMany(ProductAuthor::class)->where('is_primary', false);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function revenues(): HasMany
    {
        return $this->hasMany(Revenue::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    // --- Computed ---

    public function getAverageRatingAttribute(): float
    {
        return $this->reviews()->avg('rating') ?? 0;
    }
}
