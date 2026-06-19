<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class LibraryBook extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'school_id', 'title', 'author', 'isbn', 'publisher', 'publish_year',
        'category', 'subject_id', 'copies_total', 'copies_available',
        'shelf_location', 'description',
    ];

    protected function casts(): array
    {
        return [
            'copies_total' => 'integer',
            'copies_available' => 'integer',
            'publish_year' => 'integer',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function borrowings(): HasMany
    {
        return $this->hasMany(BookBorrowing::class, 'book_id');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('copies_available', '>', 0);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->copies_available > 0;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('book-cover')->singleFile();
    }
}
