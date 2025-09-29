<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Book;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "複数行テーブルテストを開始します...\n";
    
    // Get books with long titles
    $books = Book::take(3)->get();
    echo "テスト書籍数: " . count($books) . "\n";
    
    // Create TCPDF instance
    $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    
    $pdf->SetCreator('蔵書管理システム');
    $pdf->SetTitle('複数行テストPDF');
    
    // Load Japanese font
    $fontPath = __DIR__ . '/public/seieiIPAexMincho.ttf';
    $fontname = \TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 96);
    if ($fontname) {
        $pdf->SetFont($fontname, '', 9);
        echo "日本語フォント読み込み成功\n";
    } else {
        $pdf->SetFont('cid0jp', '', 9);
        echo "フォールバックフォント使用\n";
    }
    
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(10, 10, 10);
    $pdf->AddPage();
    
    // Title
    $pdf->SetFont($fontname ?: 'cid0jp', 'B', 16);
    $pdf->Cell(0, 10, '複数行テーブルテスト', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Table headers
    $pdf->SetFont($fontname ?: 'cid0jp', 'B', 8);
    $pdf->SetFillColor(240, 240, 240);
    $headers = ['タイトル' => 80, '著者' => 50, '出版社' => 40];
    
    foreach ($headers as $header => $width) {
        $pdf->Cell($width, 8, $header, 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    // Data rows with multiline support
    $pdf->SetFont($fontname ?: 'cid0jp', '', 7);
    
    foreach ($books as $book) {
        echo "処理中: " . mb_substr($book->title, 0, 20) . "...\n";
        
        $rowData = [
            $book->title ?: '',
            $book->author ?: '',
            $book->publisher ?: ''
        ];
        
        $colWidths = array_values($headers);
        $longTextColumns = [0, 1, 2]; // All columns
        $maxLines = 1;
        
        // Calculate required lines
        foreach ($longTextColumns as $colIndex) {
            $text = $rowData[$colIndex];
            if (!empty($text)) {
                $width = $colWidths[$colIndex];
                $chars = mb_str_split($text, 1, 'UTF-8');
                $currentLine = '';
                $lines = [];
                
                foreach ($chars as $char) {
                    $testLine = $currentLine . $char;
                    $testWidth = $pdf->GetStringWidth($testLine);
                    
                    if ($testWidth > ($width - 4) && !empty($currentLine)) {
                        $lines[] = $currentLine;
                        $currentLine = $char;
                    } else {
                        $currentLine = $testLine;
                    }
                }
                
                if (!empty($currentLine)) {
                    $lines[] = $currentLine;
                }
                
                $maxLines = max($maxLines, count($lines));
            }
        }
        
        $maxLines = min($maxLines, 3);
        $cellHeight = $maxLines * 6;
        
        $startY = $pdf->GetY();
        $startX = $pdf->GetX();
        $currentX = $startX;
        
        // Draw cell borders
        foreach ($colWidths as $width) {
            $pdf->Rect($currentX, $startY, $width, $cellHeight);
            $currentX += $width;
        }
        
        // Draw text
        $currentX = $startX;
        foreach ($rowData as $colIndex => $data) {
            $width = $colWidths[$colIndex];
            
            if (!empty($data)) {
                $chars = mb_str_split($data, 1, 'UTF-8');
                $currentLine = '';
                $lines = [];
                
                foreach ($chars as $char) {
                    $testLine = $currentLine . $char;
                    $testWidth = $pdf->GetStringWidth($testLine);
                    
                    if ($testWidth > ($width - 4) && !empty($currentLine)) {
                        $lines[] = $currentLine;
                        $currentLine = $char;
                    } else {
                        $currentLine = $testLine;
                    }
                }
                
                if (!empty($currentLine)) {
                    $lines[] = $currentLine;
                }
                
                $lines = array_slice($lines, 0, 3);
                
                foreach ($lines as $lineIndex => $line) {
                    $lineY = $startY + 2 + ($lineIndex * 6);
                    $pdf->SetXY($currentX + 2, $lineY);
                    $pdf->Cell($width - 4, 5, $line, 0, 0, 'L', false);
                }
            }
            
            $currentX += $width;
        }
        
        $pdf->SetXY($startX, $startY + $cellHeight);
    }
    
    // Save PDF
    $filename = 'multiline_test_' . date('Ymd_His') . '.pdf';
    $pdfContent = $pdf->Output($filename, 'S');
    
    file_put_contents(__DIR__ . '/' . $filename, $pdfContent);
    echo "テストPDF作成完了: $filename\n";
    echo "ファイルサイズ: " . strlen($pdfContent) . " bytes\n";
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    echo "詳細: " . $e->getTraceAsString() . "\n";
}
