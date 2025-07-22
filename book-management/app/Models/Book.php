<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ReadingStatus;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
        'publisher',
        'publication_year',
        'isbn',
        'category',
        'reading_status',
    ];

    protected $casts = [
        'reading_status' => ReadingStatus::class,
        'publication_year' => 'integer',
    ];

    // 検索スコープ
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('author', 'like', "%{$search}%");
        });
    }

    // カテゴリフィルタスコープ
    public function scopeCategory($query, $category)
    {
        if ($category) {
            return $query->where('category', $category);
        }
        return $query;
    }

    // 読書状況フィルタスコープ
    public function scopeReadingStatus($query, $status)
    {
        if ($status) {
            return $query->where('reading_status', $status);
        }
        return $query;
    }
}
