<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'title_transcription',
        'author',
        'publisher',
        'published_date',
        'isbn',
        'pages',
        'price',
        'ndc',
        'acceptance_date',
        'acceptance_type',
        'acceptance_source',
        'discard',
    ];

    protected $casts = [
        'published_date' => 'date',
        'acceptance_date' => 'date',
        'pages' => 'integer',
        'price' => 'decimal:2',
    ];

    // 検索スコープ
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('author', 'like', "%{$search}%")
              ->orWhere('publisher', 'like', "%{$search}%");
        });
    }
}
