<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Http\Controllers\Api\BookController;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "NDCフィルターPDF出力テストを開始します...\n";

// Create controller instance
$controller = new BookController();

// Test different NDC filters
$testCases = [
    ['filter' => null, 'name' => '全書籍'],
    ['filter' => '0', 'name' => 'NDC分類0（総記）'],
    ['filter' => '4', 'name' => 'NDC分類4（自然科学）'],
    ['filter' => '40', 'name' => 'NDC分類40（自然科学詳細）'],
];

foreach ($testCases as $case) {
    echo "\n=== {$case['name']}テスト ===\n";
    
    try {
        // Create request
        $request = new Request();
        if ($case['filter']) {
            $request->merge(['ndc_category' => $case['filter']]);
        }
        
        // Generate PDF
        $response = $controller->exportPdf($request);
        
        if ($response->getStatusCode() === 200) {
            $content = $response->getContent();
            $filename = 'test_ndc_' . ($case['filter'] ?: 'all') . '_' . date('His') . '.pdf';
            file_put_contents(__DIR__ . '/' . $filename, $content);
            
            echo "✅ PDF生成成功: {$filename}\n";
            echo "   ファイルサイズ: " . number_format(strlen($content)) . " bytes\n";
            
            // Check if it's a valid PDF
            if (strpos($content, '%PDF') === 0) {
                echo "   ✅ 有効なPDFファイル\n";
            } else {
                echo "   ⚠️  PDFヘッダーが見つかりません\n";
            }
        } else {
            echo "❌ エラー: HTTP " . $response->getStatusCode() . "\n";
            echo "   レスポンス: " . $response->getContent() . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ 例外エラー: " . $e->getMessage() . "\n";
        echo "   詳細: " . substr($e->getTraceAsString(), 0, 200) . "...\n";
    }
    
    sleep(1); // システム負荷軽減のため少し待機
}

echo "\n=== テスト完了 ===\n";
