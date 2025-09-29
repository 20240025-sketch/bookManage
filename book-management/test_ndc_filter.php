<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Book;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "NDC分類フィルターテストを開始します...\n\n";

// 全書籍の統計
$totalBooks = Book::count();
echo "総書籍数: {$totalBooks}冊\n";

// NDC分類別の統計を表示
$ndcStats = Book::selectRaw('SUBSTR(ndc, 1, 1) as main_class, COUNT(*) as count')
    ->whereNotNull('ndc')
    ->where('ndc', '!=', '')
    ->groupBy('main_class')
    ->orderBy('main_class')
    ->get();

echo "\n=== NDC大分類別統計 ===\n";
$ndcMap = [
    '0' => '総記', '1' => '哲学', '2' => '歴史', '3' => '社会科学', '4' => '自然科学',
    '5' => '技術・工学', '6' => '産業', '7' => '芸術・美術', '8' => '言語', '9' => '文学'
];

foreach ($ndcStats as $stat) {
    $className = $ndcMap[$stat->main_class] ?? '不明';
    echo "{$stat->main_class}**: {$className} - {$stat->count}冊\n";
}

// 個別のNDC分類をテスト
echo "\n=== フィルターテスト ===\n";
$testFilters = ['4', '40', '400', '410'];

foreach ($testFilters as $filter) {
    $query = Book::query();
    
    if (strlen($filter) === 1) {
        $query->where('ndc', 'like', $filter . '%');
    } elseif (strlen($filter) === 2) {
        $query->where('ndc', 'like', $filter . '%');
    } elseif (strlen($filter) === 3) {
        if (str_ends_with($filter, '00')) {
            $baseCategory = substr($filter, 0, 1);
            $query->where('ndc', 'like', $baseCategory . '%');
        } else {
            $query->where('ndc', 'like', $filter . '%');
        }
    }
    
    $count = $query->count();
    $books = $query->take(3)->get();
    
    echo "\nフィルター '{$filter}': {$count}冊\n";
    foreach ($books as $book) {
        echo "  - NDC: {$book->ndc} | タイトル: " . mb_substr($book->title, 0, 30) . "...\n";
    }
}

echo "\nテスト完了!\n";
