<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    use HasFactory;
    
    protected $table = 'classes';

    protected $fillable = [
        'name',
        'nendo',
        'grade',
        'kumi',
        'class_number',
    ];

    protected $casts = [
        'nendo' => 'integer',
        'grade' => 'integer',
    ];

    /**
     * このクラスに所属する生徒たちを取得
     * class_numberフィールドでこのクラスのclass_numberを参照している生徒を取得
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_number', 'class_number');
    }

    /**
     * クラス名を自動生成するアクセサ
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->grade}年{$this->kumi}組";
    }

    /**
     * 年度とクラス名を含む完全名
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->nendo}年度 {$this->getFullNameAttribute()}";
    }

    /**
     * スコープ: 特定の年度のクラスを取得
     */
    public function scopeByNendo($query, int $nendo)
    {
        return $query->where('nendo', $nendo);
    }

    /**
     * スコープ: 特定の学年のクラスを取得
     */
    public function scopeByGrade($query, int $grade)
    {
        return $query->where('grade', $grade);
    }
}
