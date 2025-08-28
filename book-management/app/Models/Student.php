<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'student_number',
        'name',
        'name_transcription',
        'email',
        'grade',
        'class',
    ];

    /**
     * Get the borrows for the student.
     */
    public function borrows(): HasMany
    {
        return $this->hasMany(Borrow::class);
    }
}
