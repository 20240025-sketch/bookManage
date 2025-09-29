<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SchoolClass;

class SchoolClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentYear = date('Y');
        
        // 各学年のクラスを作成
        $grades = [1, 2, 3, 4, 5, 6];
        $classes = ['A', 'B', 'C'];
        
        foreach ($grades as $grade) {
            foreach ($classes as $kumi) {
                SchoolClass::create([
                    'name' => "{$grade}年{$kumi}組",
                    'nendo' => $currentYear,
                    'grade' => $grade,
                    'kumi' => $kumi,
                ]);
            }
        }
        
        // 前年度のクラスも作成（履歴データとして）
        $previousYear = $currentYear - 1;
        foreach ($grades as $grade) {
            foreach ($classes as $kumi) {
                SchoolClass::create([
                    'name' => "{$grade}年{$kumi}組",
                    'nendo' => $previousYear,
                    'grade' => $grade,
                    'kumi' => $kumi,
                ]);
            }
        }
    }
}
