<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class Student extends Authenticatable
{
    protected $fillable = [
        'student_number',
        'name',
        'name_transcription',
        'email',
        'password',
        'class_number', // クラス番号フィールド（classesテーブルのIDを参照）
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * このクラスとのリレーション
     * class_numberでclassesテーブルのclass_numberフィールドを参照
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_number', 'class_number');
    }

    /**
     * Get the borrows for the student.
     */
    public function borrows(): HasMany
    {
        return $this->hasMany(Borrow::class);
    }

    /**
     * Get the book requests made by the student.
     */
    public function bookRequests(): HasMany
    {
        return $this->hasMany(BookRequest::class);
    }

    /**
     * 学年を取得するアクセサ
     * class_numberからclassesテーブルを参照
     */
    public function getGradeAttribute()
    {
        return $this->schoolClass?->grade;
    }

    /**
     * 組を取得するアクセサ
     * class_numberからclassesテーブルを参照
     */
    public function getClassAttribute()
    {
        return $this->schoolClass?->kumi;
    }

    /**
     * クラス名を取得するアクセサ
     */
    public function getClassNameAttribute(): ?string
    {
        return $this->schoolClass?->name;
    }

    /**
     * 年度を取得するアクセサ
     */
    public function getNendoAttribute(): ?int
    {
        return $this->schoolClass?->nendo;
    }

    /**
     * スコープ: 特定の学年の生徒を取得
     */
    public function scopeByGrade($query, int $grade)
    {
        // class_numberからclassesテーブルを参照して学年で絞り込み
        return $query->whereHas('schoolClass', function ($q) use ($grade) {
            $q->where('grade', $grade);
        });
    }

    /**
     * スコープ: 特定のクラス（組）の生徒を取得
     */
    public function scopeByClass($query, string $kumi)
    {
        // class_numberからclassesテーブルを参照して組で絞り込み
        return $query->whereHas('schoolClass', function ($q) use ($kumi) {
            $q->where('kumi', $kumi);
        });
    }

    /**
     * スコープ: 特定のクラス番号の生徒を取得
     */
    public function scopeByClassNumber($query, int $classNumber)
    {
        return $query->where('class_number', $classNumber);
    }

    /**
     * 総貸出数を取得するアクセサー
     */
    public function getTotalBorrowsCountAttribute(): int
    {
        return $this->borrows()->count();
    }

    /**
     * アチーブメント情報を取得するアクセサー
     */
    public function getAchievementAttribute(): ?array
    {
        // withCountで計算された値を使用するか、手動で計算
        $totalBorrows = $this->attributes['total_borrows_count'] ?? $this->borrows()->count();
        
        // 50の倍数でアチーブメントを計算
        $achievementLevel = intval($totalBorrows / 50);
        
        if ($achievementLevel === 0) {
            return null; // アチーブメントなし
        }
        
        return $this->getAchievementData($achievementLevel, $totalBorrows);
    }

    /**
     * アチーブメントレベルに応じたデータを取得
     */
    private function getAchievementData(int $level, int $totalBorrows): array
    {
        $achievements = [
            1 => [
                'title' => '読書家',
                'description' => '50冊達成！',
                'icon' => '📚',
                'color' => 'bg-blue-100 text-blue-800 border-blue-200',
                'milestone' => 50
            ],
            2 => [
                'title' => '本の虫',
                'description' => '100冊達成！',
                'icon' => '🐛',
                'color' => 'bg-green-100 text-green-800 border-green-200',
                'milestone' => 100
            ],
            3 => [
                'title' => '図書館マスター',
                'description' => '150冊達成！',
                'icon' => '🏆',
                'color' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                'milestone' => 150
            ],
            4 => [
                'title' => 'スーパーリーダー',
                'description' => '200冊達成！',
                'icon' => '⭐',
                'color' => 'bg-purple-100 text-purple-800 border-purple-200',
                'milestone' => 200
            ],
            5 => [
                'title' => '読書王',
                'description' => '250冊達成！',
                'icon' => '👑',
                'color' => 'bg-red-100 text-red-800 border-red-200',
                'milestone' => 250
            ],
        ];

        // レベル6以上の場合
        if ($level > 5) {
            return [
                'title' => '読書皇帝',
                'description' => $totalBorrows . '冊達成！',
                'icon' => '🌟',
                'color' => 'bg-gradient-to-r from-purple-100 to-pink-100 text-purple-800 border-purple-200',
                'milestone' => $totalBorrows,
                'level' => $level
            ];
        }

        return $achievements[$level] ?? null;
    }

    /**
     * 最高レベルのアチーブメントを取得
     */
    public function getHighestAchievementAttribute(): ?array
    {
        return $this->achievement;
    }

    /**
     * NDC分類別の貸出履歴を取得
     */
    public function getNdcBorrowsAttribute(): array
    {
        return $this->borrows()
            ->join('books', 'borrows.book_id', '=', 'books.id')
            ->whereNotNull('books.ndc')
            ->select('books.ndc')
            ->get()
            ->groupBy('ndc')
            ->map(function ($borrows) {
                return $borrows->count();
            })
            ->toArray();
    }

    /**
     * NDCアチーブメントを取得
     */
    public function getNdcAchievementsAttribute(): array
    {
        $ndcBorrows = $this->ndc_borrows;
        $achievements = [];
        
        $ndcCategories = [
            '000' => ['title' => '情報博士', 'icon' => '💾', 'description' => '総記・情報学の本を読破！', 'color' => 'bg-gray-100 text-gray-800 border-gray-200'],
            '100' => ['title' => '哲学者', 'icon' => '🤔', 'description' => '哲学・心理学の本を読破！', 'color' => 'bg-indigo-100 text-indigo-800 border-indigo-200'],
            '200' => ['title' => '歴史家', 'icon' => '📜', 'description' => '歴史・地理の本を読破！', 'color' => 'bg-amber-100 text-amber-800 border-amber-200'],
            '300' => ['title' => '社会学者', 'icon' => '👥', 'description' => '社会科学の本を読破！', 'color' => 'bg-orange-100 text-orange-800 border-orange-200'],
            '400' => ['title' => '言語学者', 'icon' => '🗣️', 'description' => '語学の本を読破！', 'color' => 'bg-cyan-100 text-cyan-800 border-cyan-200'],
            '500' => ['title' => '科学者', 'icon' => '🔬', 'description' => '自然科学・数学の本を読破！', 'color' => 'bg-teal-100 text-teal-800 border-teal-200'],
            '600' => ['title' => '技術者', 'icon' => '⚙️', 'description' => '技術・工学の本を読破！', 'color' => 'bg-slate-100 text-slate-800 border-slate-200'],
            '700' => ['title' => 'アーティスト', 'icon' => '🎨', 'description' => '芸術・美術の本を読破！', 'color' => 'bg-pink-100 text-pink-800 border-pink-200'],
            '800' => ['title' => '言語マスター', 'icon' => '📝', 'description' => '語学の本を読破！', 'color' => 'bg-violet-100 text-violet-800 border-violet-200'],
            '900' => ['title' => '文豪', 'icon' => '📖', 'description' => '文学の本を読破！', 'color' => 'bg-rose-100 text-rose-800 border-rose-200']
        ];
        
        foreach ($ndcBorrows as $ndc => $count) {
            if ($count >= 3 && isset($ndcCategories[$ndc])) { // 3冊以上でアチーブメント
                $achievement = $ndcCategories[$ndc];
                $achievement['ndc'] = $ndc;
                $achievement['count'] = $count;
                $achievements[] = $achievement;
            }
        }
        
        return $achievements;
    }
}
