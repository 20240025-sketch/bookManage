<?php

namespace App\Services;

use TCPDF;

class UsageStatisticsPdfService
{
    public static function generate($data)
    {
        // TCPDFインスタンスを作成
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // ドキュメント情報
        $pdf->SetCreator('図書管理システム');
        $pdf->SetAuthor('図書管理システム');
        $pdf->SetTitle('利用状況レポート');
        $pdf->SetSubject('利用状況レポート');
        
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
        $pdf->Cell(0, 10, '利用状況レポート', 0, 1, 'C');
        $pdf->SetFont($fontName, '', 10);
        $pdf->Cell(0, 7, '作成日: ' . date('Y年m月d日'), 0, 1, 'C');
        $pdf->Ln(5);
        
        // 年間で一番借りられた本
        $pdf->SetFont($fontName, 'B', 12);
        $pdf->Cell(0, 7, '年間で最も借りられた本', 0, 1, 'L');
        $pdf->SetFont($fontName, '', 10);
        
        if ($data['top_book_yearly']) {
            $book = $data['top_book_yearly']['book'];
            $count = $data['top_book_yearly']['borrow_count'];
            $pdf->Cell(40, 6, '書籍名:', 0, 0, 'L');
            $pdf->Cell(0, 6, $book->title, 0, 1, 'L');
            $pdf->Cell(40, 6, '著者:', 0, 0, 'L');
            $pdf->Cell(0, 6, $book->author, 0, 1, 'L');
            $pdf->Cell(40, 6, '貸出回数:', 0, 0, 'L');
            $pdf->Cell(0, 6, $count . '回', 0, 1, 'L');
        } else {
            $pdf->Cell(0, 6, 'データなし', 0, 1, 'L');
        }
        $pdf->Ln(3);
        
        // 月間で一番借りられた本
        $pdf->SetFont($fontName, 'B', 12);
        $pdf->Cell(0, 7, '月間で最も借りられた本', 0, 1, 'L');
        $pdf->SetFont($fontName, '', 10);
        
        if ($data['top_book_monthly']) {
            $book = $data['top_book_monthly']['book'];
            $count = $data['top_book_monthly']['borrow_count'];
            $pdf->Cell(40, 6, '書籍名:', 0, 0, 'L');
            $pdf->Cell(0, 6, $book->title, 0, 1, 'L');
            $pdf->Cell(40, 6, '著者:', 0, 0, 'L');
            $pdf->Cell(0, 6, $book->author, 0, 1, 'L');
            $pdf->Cell(40, 6, '貸出回数:', 0, 0, 'L');
            $pdf->Cell(0, 6, $count . '回', 0, 1, 'L');
        } else {
            $pdf->Cell(0, 6, 'データなし', 0, 1, 'L');
        }
        $pdf->Ln(3);
        
        // 統計情報テーブル
        $pdf->SetFont($fontName, 'B', 12);
        $pdf->Cell(0, 7, '期間別統計情報', 0, 1, 'L');
        $pdf->SetFont($fontName, '', 10);
        
        // テーブルヘッダー
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(45, 7, '期間', 1, 0, 'C', true);
        $pdf->Cell(45, 7, '貸出件数', 1, 0, 'C', true);
        $pdf->Cell(45, 7, '利用者数', 1, 0, 'C', true);
        $pdf->Cell(45, 7, '平均貸出数', 1, 1, 'C', true);
        
        // 一日
        $dailyAvg = $data['daily_stats']['user_count'] > 0 
            ? round($data['daily_stats']['borrow_count'] / $data['daily_stats']['user_count'], 1)
            : 0;
        $pdf->Cell(45, 7, '本日', 1, 0, 'C');
        $pdf->Cell(45, 7, $data['daily_stats']['borrow_count'] . '件', 1, 0, 'C');
        $pdf->Cell(45, 7, $data['daily_stats']['user_count'] . '人', 1, 0, 'C');
        $pdf->Cell(45, 7, $dailyAvg . '冊/人', 1, 1, 'C');
        
        // 一ヶ月
        $monthlyAvg = $data['monthly_stats']['user_count'] > 0 
            ? round($data['monthly_stats']['borrow_count'] / $data['monthly_stats']['user_count'], 1)
            : 0;
        $pdf->Cell(45, 7, '今月', 1, 0, 'C');
        $pdf->Cell(45, 7, $data['monthly_stats']['borrow_count'] . '件', 1, 0, 'C');
        $pdf->Cell(45, 7, $data['monthly_stats']['user_count'] . '人', 1, 0, 'C');
        $pdf->Cell(45, 7, $monthlyAvg . '冊/人', 1, 1, 'C');
        
        // 一年間
        $yearlyAvg = $data['yearly_stats']['user_count'] > 0 
            ? round($data['yearly_stats']['borrow_count'] / $data['yearly_stats']['user_count'], 1)
            : 0;
        $pdf->Cell(45, 7, '今年', 1, 0, 'C');
        $pdf->Cell(45, 7, $data['yearly_stats']['borrow_count'] . '件', 1, 0, 'C');
        $pdf->Cell(45, 7, $data['yearly_stats']['user_count'] . '人', 1, 0, 'C');
        $pdf->Cell(45, 7, $yearlyAvg . '冊/人', 1, 1, 'C');
        
        $pdf->Ln(5);
        
        // グラフ画像を追加（Base64デコード）
        if (!empty($data['chart_image'])) {
            $pdf->SetFont($fontName, 'B', 12);
            
            $periodLabels = [
                'daily' => '日間推移',
                'monthly' => '月間推移',
                'yearly' => '年間推移'
            ];
            $dataTypeLabels = [
                'borrow_count' => '貸出件数',
                'user_count' => '利用者数',
                'both' => '貸出件数・利用者数'
            ];
            
            $title = $periodLabels[$data['period']] . ' - ' . $dataTypeLabels[$data['data_type']];
            $pdf->Cell(0, 7, $title, 0, 1, 'L');
            $pdf->Ln(2);
            
            // Base64画像をデコード
            $imageData = $data['chart_image'];
            if (strpos($imageData, 'data:image/png;base64,') === 0) {
                $imageData = substr($imageData, strlen('data:image/png;base64,'));
            }
            $imageData = base64_decode($imageData);
            
            // 一時ファイルに保存
            $tempFile = tempnam(sys_get_temp_dir(), 'chart_') . '.png';
            file_put_contents($tempFile, $imageData);
            
            // 画像を挿入（幅150mm、アスペクト比維持）
            $pdf->Image($tempFile, 15, $pdf->GetY(), 150, 0, 'PNG');
            
            // 一時ファイルを削除
            unlink($tempFile);
        }
        
        // PDF出力
        return $pdf->Output('', 'S');
    }
}
