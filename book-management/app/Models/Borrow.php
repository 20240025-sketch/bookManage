<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Borrow extends Model
{
    protected $fillable = [
        'book_id',
        'student_id',
        'borrowed_date',
        'due_date',
        'returned_date',
    ];

    protected $casts = [
        'borrowed_date' => 'date',
        'due_date' => 'date',
        'returned_date' => 'date',
    ];

    /**
     * Get the book that is borrowed.
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Get the student who borrowed the book.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * 返却期限日を取得（貸出日の2週間後）
     */
    public function getDueDateAttribute()
    {
        return $this->borrowed_date?->copy()->addWeeks(2);
    }
}
