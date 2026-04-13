<?php

namespace Database\Seeders;

use App\Models\Borrow;
use Illuminate\Database\Seeder;

class BorrowSeeder extends Seeder
{
    public function run(): void
    {
        // サンプル借出記録: Tanaka Taro が最初の本を借りている
        Borrow::create([
            'book_id' => 1,
            'student_id' => 1,
            'borrowed_date' => '2026-04-01 10:00:00',
            'return_due_date' => '2026-04-15 10:00:00',
            'returned_date' => null, // 貸出中
        ]);

        // その他のサンプルデータ
        Borrow::create([
            'book_id' => 2,
            'student_id' => 2,
            'borrowed_date' => '2026-03-20 09:30:00',
            'return_due_date' => '2026-04-03 09:30:00',
            'returned_date' => '2026-04-02 14:00:00',
        ]);

        Borrow::create([
            'book_id' => 3,
            'student_id' => 3,
            'borrowed_date' => '2026-04-05 11:00:00',
            'return_due_date' => '2026-04-19 11:00:00',
            'returned_date' => null,
        ]);

        Borrow::create([
            'book_id' => 4,
            'student_id' => 4,
            'borrowed_date' => '2026-03-15 08:00:00',
            'return_due_date' => '2026-03-29 08:00:00',
            'returned_date' => '2026-03-28 16:30:00',
        ]);

        Borrow::create([
            'book_id' => 5,
            'student_id' => 5,
            'borrowed_date' => '2026-04-06 10:30:00',
            'return_due_date' => '2026-04-20 10:30:00',
            'returned_date' => null,
        ]);
    }
}
