<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'student_id',
        'book_request_id',
        'book_id',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * この通知が関連するリクエスト
     */
    public function bookRequest(): BelongsTo
    {
        return $this->belongsTo(BookRequest::class);
    }

    /**
     * この通知が関連する書籍
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * この通知を受け取る生徒
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * 未読の通知のみを取得
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * 既読にする
     */
    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }
}
