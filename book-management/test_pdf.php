<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Book;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "Starting PDF test...\n";
    
    // Get some books
    $books = Book::take(3)->get();
    echo "Found " . count($books) . " books\n";
    
    // Create TCPDF instance
    $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    echo "TCPDF instance created\n";
    
    $pdf->SetCreator('蔵書管理システム');
    $pdf->SetAuthor('蔵書管理システム');
    $pdf->SetTitle('書籍一覧');
    $pdf->SetSubject('書籍一覧PDF');
    
    // Set font
    $pdf->SetFont('dejavusans', '', 9);
    echo "Font set successfully\n";
    
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 10);
    
    $pdf->AddPage();
    echo "Page added\n";
    
    // Title
    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->Cell(0, 10, '書籍一覧', 0, 1, 'C');
    $pdf->Ln(5);
    echo "Title added\n";
    
    // Table headers
    $pdf->SetFont('dejavusans', 'B', 8);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(50, 8, 'タイトル', 1, 0, 'C', true);
    $pdf->Cell(40, 8, '著者', 1, 0, 'C', true);
    $pdf->Cell(30, 8, '出版社', 1, 1, 'C', true);
    echo "Headers added\n";
    
    // Data rows
    $pdf->SetFont('dejavusans', '', 7);
    foreach ($books as $book) {
        $pdf->Cell(50, 6, $book->title ?: '', 1, 0, 'C');
        $pdf->Cell(40, 6, $book->author ?: '', 1, 0, 'C');
        $pdf->Cell(30, 6, $book->publisher ?: '', 1, 1, 'C');
    }
    echo "Data rows added\n";
    
    // Output PDF
    $filename = 'test_' . date('Ymd_His') . '.pdf';
    $pdfContent = $pdf->Output($filename, 'S');
    
    file_put_contents(__DIR__ . '/' . $filename, $pdfContent);
    echo "PDF saved as: $filename\n";
    echo "PDF size: " . strlen($pdfContent) . " bytes\n";
    echo "Test completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
