<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Book;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "日本語フォントテストを開始します...\n";
    
    // Get a book with Japanese text
    $books = Book::take(1)->get();
    if ($books->isEmpty()) {
        echo "テスト用の書籍データがありません\n";
        exit;
    }
    
    $book = $books->first();
    echo "テスト書籍: " . $book->title . "\n";
    
    // Create TCPDF instance
    $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    
    $pdf->SetCreator('蔵書管理システム');
    $pdf->SetAuthor('蔵書管理システム');
    $pdf->SetTitle('日本語フォントテスト');
    
    // Try to load Japanese font
    $fontPath = __DIR__ . '/public/seieiIPAexMincho.ttf';
    echo "フォントパス: $fontPath\n";
    echo "ファイル存在確認: " . (file_exists($fontPath) ? 'OK' : 'NG') . "\n";
    
    if (file_exists($fontPath)) {
        echo "日本語フォントを読み込み中...\n";
        $fontname = \TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 96);
        if ($fontname) {
            echo "フォント読み込み成功: $fontname\n";
            $pdf->SetFont($fontname, '', 12);
        } else {
            echo "フォント読み込み失敗、フォールバック使用\n";
            $pdf->SetFont('cid0jp', '', 12);
        }
    } else {
        echo "フォントファイルが見つかりません\n";
        $pdf->SetFont('cid0jp', '', 12);
    }
    
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();
    
    // Test Japanese text
    $testTexts = [
        '日本語テスト',
        '書籍名: ' . $book->title,
        '著者: ' . ($book->author ?: '不明'),
        '出版社: ' . ($book->publisher ?: '不明'),
        'ひらがな: あいうえお',
        'カタカナ: アイウエオ',
        '漢字: 図書館管理'
    ];
    
    $y = 20;
    foreach ($testTexts as $text) {
        $pdf->SetXY(10, $y);
        $pdf->Cell(0, 8, $text, 0, 1, 'L');
        $y += 10;
    }
    
    // Save PDF
    $filename = 'font_test_' . date('Ymd_His') . '.pdf';
    $pdfContent = $pdf->Output($filename, 'S');
    
    file_put_contents(__DIR__ . '/' . $filename, $pdfContent);
    echo "テストPDF作成完了: $filename\n";
    echo "ファイルサイズ: " . strlen($pdfContent) . " bytes\n";
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    echo "詳細: " . $e->getTraceAsString() . "\n";
}
