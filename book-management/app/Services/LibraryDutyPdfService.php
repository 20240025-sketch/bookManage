<?php

namespace App\Services;

use TCPDF;

class LibraryDutyPdfService
{
    public static function generate($data)
    {
        // TCPDFインスタンスを作成
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // ドキュメント情報
        $pdf->SetCreator('図書管理システム');
        $pdf->SetAuthor('図書管理システム');
        $pdf->SetTitle('図書当番記録');
        $pdf->SetSubject('図書当番記録');
        
        // ヘッダー・フッターを削除
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // マージン設定
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        
        // フォント設定
        $fontPath = public_path('seieiIPAexMincho.ttf');
        $fontName = \TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 32);
        $pdf->SetFont($fontName, '', 10);
        
        // ページ追加
        $pdf->AddPage();
        
        // タイトル
        $pdf->SetFont($fontName, 'B', 16);
        $shiftTypeLabel = '';
        if (isset($data['shift_type'])) {
            $shiftTypeLabel = $data['shift_type'] === 'lunch' ? '（昼休み）' : ($data['shift_type'] === 'after_school' ? '（放課後）' : '');
        }
        $pdf->Cell(0, 10, '図書当番記録' . $shiftTypeLabel, 0, 1, 'C');
        $pdf->SetFont($fontName, '', 10);
        
        // 期間
        $startDate = date('Y年m月d日', strtotime($data['start_date']));
        $endDate = date('Y年m月d日', strtotime($data['end_date']));
        $pdf->Cell(0, 7, '期間: ' . $startDate . ' ～ ' . $endDate, 0, 1, 'C');
        $pdf->Cell(0, 7, '作成日: ' . date('Y年m月d日'), 0, 1, 'C');
        $pdf->Ln(5);
        
        // 統計情報
        $pdf->SetFont($fontName, 'B', 12);
        $pdf->Cell(0, 7, '統計情報', 0, 1, 'L');
        $pdf->SetFont($fontName, '', 10);
        
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(45, 7, '項目', 1, 0, 'C', true);
        $pdf->Cell(45, 7, '合計', 1, 0, 'C', true);
        $pdf->Cell(45, 7, '平均', 1, 0, 'C', true);
        $pdf->Cell(45, 7, '日数', 1, 1, 'C', true);
        
        // 利用者数
        $pdf->Cell(45, 7, '利用者数', 1, 0, 'L');
        $pdf->Cell(45, 7, $data['stats']['total_visitors'] . '人', 1, 0, 'C');
        $pdf->Cell(45, 7, $data['stats']['avg_visitors'] . '人/日', 1, 0, 'C');
        $pdf->Cell(45, 7, $data['stats']['total_days'] . '日', 1, 1, 'C');
        
        // 貸出人数
        $pdf->Cell(45, 7, '貸出人数', 1, 0, 'L');
        $pdf->Cell(45, 7, $data['stats']['total_borrows'] . '人', 1, 0, 'C');
        $pdf->Cell(45, 7, $data['stats']['avg_borrows'] . '人/日', 1, 0, 'C');
        $pdf->Cell(45, 7, '', 1, 1, 'C');
        
        $pdf->Ln(5);
        
        // 日別記録
        $pdf->SetFont($fontName, 'B', 12);
        $pdf->Cell(0, 7, '日別記録', 0, 1, 'L');
        $pdf->SetFont($fontName, '', 9);
        
        // テーブルヘッダー
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(22, 7, '日付', 1, 0, 'C', true);
        $pdf->Cell(18, 7, '利用者数', 1, 0, 'C', true);
        $pdf->Cell(18, 7, '貸出人数', 1, 0, 'C', true);
        $pdf->Cell(40, 7, '担当者', 1, 0, 'C', true);
        $pdf->Cell(82, 7, 'ふりかえり', 1, 1, 'C', true);
        
        // データ行
        foreach ($data['duties'] as $duty) {
            $date = date('m/d', strtotime($duty->duty_date));
            
            // 担当者名（2名まで）
            $studentNames = [];
            if (!empty($duty->student_name_1)) {
                $studentNames[] = $duty->student_name_1;
            }
            if (!empty($duty->student_name_2)) {
                $studentNames[] = $duty->student_name_2;
            }
            $studentName = !empty($studentNames) ? implode('、', $studentNames) : '-';
            
            $reflection = $duty->reflection ?: '-';
            
            // 行の高さを計算（ふりかえりの長さに応じて）
            $reflectionLines = $pdf->getNumLines($reflection, 82);
            $cellHeight = max(7, $reflectionLines * 5);
            
            $currentY = $pdf->GetY();
            
            // 各セル
            $pdf->MultiCell(22, $cellHeight, $date, 1, 'C', false, 0);
            $pdf->MultiCell(18, $cellHeight, $duty->visitor_count . '人', 1, 'C', false, 0);
            $pdf->MultiCell(18, $cellHeight, $duty->borrow_count . '人', 1, 'C', false, 0);
            $pdf->MultiCell(40, $cellHeight, $studentName, 1, 'C', false, 0);
            $pdf->MultiCell(82, $cellHeight, $reflection, 1, 'L', false, 1);
            
            // ページ境界チェック
            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
                $pdf->SetFont($fontName, '', 9);
                
                // ヘッダーを再表示
                $pdf->SetFillColor(240, 240, 240);
                $pdf->Cell(25, 7, '日付', 1, 0, 'C', true);
                $pdf->Cell(20, 7, '利用者数', 1, 0, 'C', true);
                $pdf->Cell(20, 7, '貸出人数', 1, 0, 'C', true);
                $pdf->Cell(25, 7, '担当者', 1, 0, 'C', true);
                $pdf->Cell(90, 7, 'ふりかえり', 1, 1, 'C', true);
            }
        }
        
        // PDF出力
        return $pdf->Output('', 'S');
    }
}
