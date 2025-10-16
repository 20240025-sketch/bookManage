<?php

namespace App\Services;

use Carbon\Carbon;

class BorrowStatusPdfService
{
    public static function generate($borrows, $statistics)
    {
        // PDFの設定
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8');
        
        // ドキュメント情報
        $pdf->SetCreator('図書館管理システム');
        $pdf->SetAuthor('図書館管理システム');
        $pdf->SetTitle('貸出状況一覧');
        $pdf->SetSubject('貸出状況');
        
        // ヘッダー・フッターを削除
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // マージン設定
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        
        // フォント設定
        $fontPath = public_path('seieiIPAexMincho.ttf');
        if (file_exists($fontPath)) {
            $fontname = \TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 96);
            $pdf->SetFont($fontname, '', 9);
        } else {
            $pdf->SetFont('dejavusans', '', 9);
        }
        
        // ページ追加
        $pdf->AddPage();
        
        // タイトル
        $pdf->SetFont($pdf->getFontFamily(), 'B', 16);
        $pdf->Cell(0, 10, '貸出状況一覧', 0, 1, 'C');
        $pdf->Ln(2);
        
        // 出力日時とフィルター情報
        $pdf->SetFont($pdf->getFontFamily(), '', 9);
        $filterText = '';
        if (isset($statistics['filter'])) {
            if ($statistics['filter'] === 'overdue') {
                $filterText = '（滞納のみ）';
            } elseif ($statistics['filter'] === 'not_overdue') {
                $filterText = '（期限内のみ）';
            }
        }
        $pdf->Cell(0, 5, '出力日時: ' . date('Y年m月d日 H:i') . ' ' . $filterText, 0, 1, 'R');
        $pdf->Ln(3);
        
        // 統計情報
        $pdf->SetFont($pdf->getFontFamily(), 'B', 10);
        $pdf->Cell(60, 6, '総貸出数: ' . $statistics['total'] . '件', 1, 0, 'C', false);
        $pdf->Cell(60, 6, '滞納: ' . $statistics['overdue'] . '件', 1, 0, 'C', false);
        $pdf->Cell(60, 6, '期限内: ' . ($statistics['total'] - $statistics['overdue']) . '件', 1, 1, 'C', false);
        $pdf->Ln(5);
        
        // テーブルヘッダー
        $pdf->SetFont($pdf->getFontFamily(), 'B', 9);
        $pdf->SetFillColor(220, 220, 220);
        
        $pdf->Cell(60, 7, '書籍名', 1, 0, 'C', true);
        $pdf->Cell(35, 7, '著者', 1, 0, 'C', true);
        $pdf->Cell(30, 7, '借りている人', 1, 0, 'C', true);
        $pdf->Cell(22, 7, '学籍番号', 1, 0, 'C', true);
        $pdf->Cell(20, 7, '学年・クラス', 1, 0, 'C', true);
        $pdf->Cell(23, 7, '貸出日', 1, 0, 'C', true);
        $pdf->Cell(23, 7, '返却期限', 1, 0, 'C', true);
        $pdf->Cell(27, 7, '状況', 1, 1, 'C', true);
        
        // テーブルデータ
        $pdf->SetFont($pdf->getFontFamily(), '', 8);
        
        foreach ($borrows as $borrow) {
            // 滞納の場合は背景色を変更
            if ($borrow->is_overdue) {
                $pdf->SetFillColor(255, 220, 220);
                $fill = true;
            } else {
                $pdf->SetFillColor(255, 255, 255);
                $fill = false;
            }
            
            // 行の高さを計算（タイトルの長さに応じて）
            $titleLines = $pdf->getNumLines($borrow->book->title, 60);
            $rowHeight = max(7, $titleLines * 5);
            
            $y = $pdf->GetY();
            
            // 学年・クラス情報を取得
            $gradeClass = '';
            if ($borrow->student->schoolClass) {
                $gradeClass = $borrow->student->schoolClass->grade . '年' . $borrow->student->schoolClass->name;
            }
            
            // 各セルを描画
            $pdf->MultiCell(60, $rowHeight, $borrow->book->title, 1, 'L', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
            $pdf->MultiCell(35, $rowHeight, $borrow->book->author, 1, 'L', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
            $pdf->MultiCell(30, $rowHeight, $borrow->student->name, 1, 'C', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
            $pdf->MultiCell(22, $rowHeight, $borrow->student->student_number, 1, 'C', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
            $pdf->MultiCell(20, $rowHeight, $gradeClass, 1, 'C', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
            $pdf->MultiCell(23, $rowHeight, Carbon::parse($borrow->borrowed_date)->format('Y/m/d'), 1, 'C', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
            $pdf->MultiCell(23, $rowHeight, Carbon::parse($borrow->due_date)->format('Y/m/d'), 1, 'C', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
            
            // 状況列
            if ($borrow->is_overdue) {
                $statusText = '滞納 ' . $borrow->overdue_days . '日';
                $pdf->SetTextColor(200, 0, 0);
            } else {
                $dueDate = Carbon::parse($borrow->due_date);
                $today = Carbon::now()->startOfDay();
                $daysLeft = $today->diffInDays($dueDate, false);
                $statusText = '期限内 (残' . $daysLeft . '日)';
                $pdf->SetTextColor(0, 0, 0);
            }
            
            $pdf->MultiCell(27, $rowHeight, $statusText, 1, 'C', $fill, 1, '', '', true, 0, false, true, $rowHeight, 'M');
            $pdf->SetTextColor(0, 0, 0);
            
            // ページが足りない場合は改ページ
            if ($pdf->GetY() > 180) {
                $pdf->AddPage();
                
                // ヘッダーを再描画
                $pdf->SetFont($pdf->getFontFamily(), 'B', 9);
                $pdf->SetFillColor(220, 220, 220);
                
                $pdf->Cell(60, 7, '書籍名', 1, 0, 'C', true);
                $pdf->Cell(35, 7, '著者', 1, 0, 'C', true);
                $pdf->Cell(30, 7, '借りている人', 1, 0, 'C', true);
                $pdf->Cell(22, 7, '学籍番号', 1, 0, 'C', true);
                $pdf->Cell(20, 7, '学年・クラス', 1, 0, 'C', true);
                $pdf->Cell(23, 7, '貸出日', 1, 0, 'C', true);
                $pdf->Cell(23, 7, '返却期限', 1, 0, 'C', true);
                $pdf->Cell(27, 7, '状況', 1, 1, 'C', true);
                
                $pdf->SetFont($pdf->getFontFamily(), '', 8);
            }
        }
        
        return $pdf->Output('', 'S');
    }
}
