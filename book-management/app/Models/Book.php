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
        'title_transcription',
        'author',
        'publisher',
        'published_date',
        'isbn',
        'pages',
        'price',
        'ndc',
        'reading_status',
    ];

    protected $casts = [
        'reading_status' => ReadingStatus::class,
        'published_date' => 'date',
        'pages' => 'integer',
        'price' => 'decimal:2',
    ];

    protected $attributes = [
        'reading_status' => 'unread', // デフォルトは「未読」
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

    // 読書状況フィルタスコープ
    public function scopeReadingStatus($query, $status)
    {
        if ($status) {
            return $query->where('reading_status', $status);
        }
        return $query;
    }
}
