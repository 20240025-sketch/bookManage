<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryDuty extends Model
{
    use HasFactory;

    protected $fillable = [
        'duty_date',
        'shift_type',
        'visitor_count',
        'borrow_count',
        'reflection',
        'student_id',
        'student_id_2',
        'student_name_1',
        'student_name_2'
    ];

    protected $casts = [
        'duty_date' => 'date',
        'visitor_count' => 'integer',
        'borrow_count' => 'integer',
    ];

    /**
     * 担当者（生徒）とのリレーション
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * 担当者2（生徒）とのリレーション
     */
    public function student2(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id_2');
    }
}
