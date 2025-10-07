<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JanCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'jan_code',
        'sequence_number',
        'is_used',
        'book_id'
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'sequence_number' => 'integer'
    ];

    /**
     * このJANコードに関連付けられた書籍
     */
    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}