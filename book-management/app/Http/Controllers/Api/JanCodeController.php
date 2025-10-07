<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JanCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JanCodeController extends Controller
{
    /**
     * 新しいJANコードを生成
     */
    public function generateJanCode(): JsonResponse
    {
        try {
            DB::beginTransaction();

            // 最新のシーケンス番号を取得（938525から始まる13桁コード）
            $latestJanCode = JanCode::where('jan_code', 'like', '938525%')
                ->orderBy('sequence_number', 'desc')
                ->first();

            $nextSequence = $latestJanCode ? $latestJanCode->sequence_number + 1 : 1;
            
            // 6桁の連番を生成（000001から開始）
            $sequencePart = str_pad($nextSequence, 6, '0', STR_PAD_LEFT);
            
            // JANコードを生成（EAN-13形式: 938525 + 6桁連番 = 12桁、最後1桁はチェックディジット）
            $baseCode = '938525' . $sequencePart;
            
            // チェックディジットを計算
            $checkDigit = $this->calculateEan13CheckDigit($baseCode);
            $janCode = $baseCode . $checkDigit;

            // データベースに保存
            $janCodeRecord = JanCode::create([
                'jan_code' => $janCode,
                'sequence_number' => $nextSequence,
                'is_used' => false
            ]);

            DB::commit();

            return response()->json([
                'jan_code' => $janCode,
                'sequence_number' => $nextSequence,
                'success' => true
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('JANコード生成エラー: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'JANコードの生成に失敗しました',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * バーコードPDFを生成
     */
    public function generateBarcodePdf(Request $request): \Illuminate\Http\Response
    {
        $request->validate([
            'jan_code' => 'required|string|size:13'
        ]);

        $janCode = $request->jan_code;

        try {
            // 書籍情報を取得
            $book = \App\Models\Book::where('isbn', $janCode)->first();
            $bookTitle = $book ? $book->title : 'Unknown Book';
            $bookAuthor = $book ? $book->author : null;

            // PDFライブラリを使用してバーコードPDFを生成
            $pdf = $this->generateSimpleBarcodePdf($janCode, $bookTitle, $bookAuthor);

            // ファイル名に書籍タイトルも含める（日本語対応）
            if ($bookTitle) {
                // 日本語が含まれている場合は、URL-safe base64エンコードを使用
                $hasJapanese = preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FAF}]/u', $bookTitle);
                if ($hasJapanese) {
                    // 日本語タイトルの場合はJANコードのみでファイル名を生成
                    $filename = "barcode_{$janCode}.pdf";
                } else {
                    // 英語タイトルの場合は従来通り
                    $safeTitle = preg_replace('/[^\w\-_\.]/', '_', mb_substr($bookTitle, 0, 20));
                    $filename = "barcode_{$safeTitle}_{$janCode}.pdf";
                }
            } else {
                $filename = "barcode_{$janCode}.pdf";
            }

            return response($pdf)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            Log::error('PDF生成エラー: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'PDFの生成に失敗しました',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * JAN-13のチェックディジット計算
     */
    private function calculateEan13CheckDigit(string $code): int
    {
        if (strlen($code) !== 12) {
            throw new \InvalidArgumentException('コードは12桁である必要があります');
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int)$code[$i];
            // 奇数位置は1倍、偶数位置は3倍
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;
        return $checkDigit;
    }

    /**
     * TCPDFを使用したバーコードPDF生成
     */
    private function generateSimpleBarcodePdf(string $janCode, string $bookTitle = null, string $bookAuthor = null): string
    {
        // TCPDFを使用してPDFを生成 (UTF-8エンコーディング対応)
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        
        // PDFの設定
        $pdf->SetCreator('Book Management System');
        $pdf->SetAuthor('Book Management System');
        $pdf->SetTitle('Barcode - ' . ($bookTitle ?: $janCode));
        $pdf->SetSubject('Book Barcode');
        
        // マージン設定
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetAutoPageBreak(true, 20);
        
        // ページを追加
        $pdf->AddPage();
        
        // タイトル（英語）
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 15, 'Book Barcode', 0, 1, 'C');
        $pdf->Ln(5);
        
        // 書籍タイトル表示（存在する場合）
        if ($bookTitle) {
            // 日本語文字が含まれているかチェック
            $hasJapanese = preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FAF}]/u', $bookTitle);
            
            if ($hasJapanese) {
                // 日本語フォントを使用
                $pdf->SetFont('cid0jp', 'B', 14);
            } else {
                // 英語フォントを使用
                $pdf->SetFont('helvetica', 'B', 14);
            }
            
            // タイトルが長い場合の処理
            $titleLines = $pdf->getStringHeight(170, $bookTitle, false, true, '', 1);
            if ($titleLines > 10) {
                // 長いタイトルは複数行で表示
                $pdf->MultiCell(170, 8, $bookTitle, 0, 'C', false, 1, '', '', true);
            } else {
                $pdf->Cell(0, 10, $bookTitle, 0, 1, 'C');
            }
            $pdf->Ln(3);
        }
        
        // 作者表示（存在する場合）
        if ($bookAuthor) {
            // 日本語文字が含まれているかチェック
            $hasJapanese = preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FAF}]/u', $bookAuthor);
            
            if ($hasJapanese) {
                // 日本語フォントを使用
                $pdf->SetFont('cid0jp', '', 12);
                $pdf->Cell(0, 8, '著者: ' . $bookAuthor, 0, 1, 'C');
            } else {
                // 英語フォントを使用
                $pdf->SetFont('helvetica', '', 12);
                $pdf->Cell(0, 8, 'Author: ' . $bookAuthor, 0, 1, 'C');
            }
            $pdf->Ln(3);
        }
        
        // JANコード表示
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'JAN Code: ' . $janCode, 0, 1, 'C');
        $pdf->Ln(10);
        
        // バーコード生成（EAN13形式）
        $style = array(
            'position' => '',
            'align' => 'C',
            'stretch' => false,
            'fitwidth' => true,
            'cellfitscale' => false,
            'border' => false,
            'hpadding' => 'auto',
            'vpadding' => 'auto',
            'fgcolor' => array(0, 0, 0),
            'bgcolor' => false,
            'text' => true,
            'font' => 'helvetica',
            'fontsize' => 10,
            'stretchtext' => 4
        );
        
        // EAN-13バーコードを描画
        $pdf->write1DBarcode($janCode, 'EAN13', '', '', '', 18, 0.4, $style, 'N');
        
        // 生成日時を追加
        $pdf->Ln(25);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 8, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $pdf->Cell(0, 8, 'Book Management System', 0, 1, 'C');
        
        // PDFを文字列として出力
        return $pdf->Output('', 'S');
    }
}