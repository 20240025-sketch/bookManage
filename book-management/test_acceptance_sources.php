<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Book;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "受け入れ元機能テストを開始します...\n";

// 現在の受け入れ元データを確認
$existingSources = Book::whereNotNull('acceptance_source')
    ->where('acceptance_source', '!=', '')
    ->distinct()
    ->orderBy('acceptance_source')
    ->pluck('acceptance_source')
    ->toArray();

echo "\n=== 現在のデータベース内受け入れ元 ===\n";
foreach ($existingSources as $source) {
    echo "- {$source}\n";
}

// テスト用の新しい受け入れ元を追加
$book = Book::first();
if ($book) {
    $testSources = [
        '地元書店',
        '学校図書館協会',
        'オンライン書店X'
    ];
    
    echo "\n=== テスト用受け入れ元を追加 ===\n";
    foreach ($testSources as $i => $testSource) {
        // 既存の書籍を複製してテスト用受け入れ元を設定
        $testBook = $book->replicate();
        $testBook->title = "テスト書籍 " . ($i + 1) . " - " . $testSource;
        $testBook->acceptance_source = $testSource;
        $testBook->isbn = '9999999999' . str_pad($i + 1, 3, '0', STR_PAD_LEFT); // ユニークなISBN
        $testBook->save();
        
        echo "- 追加: {$testSource} (Book ID: {$testBook->id})\n";
    }
    
    echo "\n✅ テスト用データを追加しました\n";
}

// 更新後の受け入れ元データを確認
$updatedSources = Book::whereNotNull('acceptance_source')
    ->where('acceptance_source', '!=', '')
    ->distinct()
    ->orderBy('acceptance_source')
    ->pluck('acceptance_source')
    ->toArray();

echo "\n=== 更新後のデータベース内受け入れ元 ===\n";
foreach ($updatedSources as $source) {
    echo "- {$source}\n";
}

echo "\n" . count($updatedSources) . "種類の受け入れ元があります\n";
echo "\nAPI経由での候補取得をテストしてください: /api/books/acceptance-sources\n";
echo "Webテストページ: http://localhost:8000/test-acceptance-source.html\n";

echo "\nテスト完了!\n";
