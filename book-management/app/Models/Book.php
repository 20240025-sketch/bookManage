<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Book extends Model
{
    use HasFactory;

    /**
     * Get the borrows for the book.
     */
    public function borrows(): HasMany
    {
        return $this->hasMany(Borrow::class);
    }

    /**
     * Get the current borrow (not returned yet).
     */
    public function currentBorrow(): HasOne
    {
        return $this->hasOne(Borrow::class)->whereNull('returned_date');
    }

    /**
     * Get the JAN code for this book (for books registered without ISBN).
     */
    public function janCode(): HasOne
    {
        return $this->hasOne(JanCode::class);
    }

    protected $fillable = [
        'title',
        'volume_number',
        'title_transcription',
        'author',
        'publisher',
        'published_date',
        'isbn',
        'pages',
        'price',
        'ndc',
        'quantity',
        'acceptance_date',
        'acceptance_type',
        'acceptance_source',
        'discard',
        'storage_location',
    ];

    protected $casts = [
        'published_date' => 'date',
        'acceptance_date' => 'date',
        'pages' => 'integer',
        'quantity' => 'integer',
        'price' => 'decimal:2',
    ];

    // 検索スコープ
    public function scopeSearch($query, $search)
    {
        // ISBNの正規化（ハイフンを削除）
        $normalizedSearch = preg_replace('/[-\s]/', '', $search);
        
        return $query->where(function ($q) use ($search, $normalizedSearch) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('author', 'like', "%{$search}%")
              ->orWhere('publisher', 'like', "%{$search}%")
              ->orWhere('isbn', 'like', "%{$search}%")
              ->orWhere('isbn', 'like', "%{$normalizedSearch}%");
        });
    }
}
