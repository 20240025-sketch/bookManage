<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $books = [
            [
                'title' => 'Laravel実践入門',
                'author' => '山田太郎',
                'publisher' => '技術書出版',
                'publication_year' => 2023,
                'isbn' => '978-4-123456-78-9',
                'category' => '技術書',
                'reading_status' => 'read',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Vue.js 3 完全ガイド',
                'author' => '佐藤花子',
                'publisher' => 'フロントエンド出版',
                'publication_year' => 2022,
                'isbn' => '978-4-987654-32-1',
                'category' => '技術書',
                'reading_status' => 'reading',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => '1984年',
                'author' => 'ジョージ・オーウェル',
                'publisher' => '新潮社',
                'publication_year' => 1949,
                'isbn' => '978-4-101092-41-7',
                'category' => '小説',
                'reading_status' => 'read',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // テーブルが存在しない場合は作成
        DB::statement('CREATE TABLE IF NOT EXISTS books (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(255) NOT NULL,
            author VARCHAR(255) NOT NULL,
            publisher VARCHAR(255),
            publication_year INTEGER,
            isbn VARCHAR(20),
            category VARCHAR(100),
            reading_status VARCHAR(20) DEFAULT "unread",
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        foreach ($books as $book) {
            DB::table('books')->insert($book);
        }
    }
}
