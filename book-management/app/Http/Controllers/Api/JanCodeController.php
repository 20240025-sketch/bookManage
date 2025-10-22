<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JanCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
    public function generateBarcodePdf(Request $request): Response|JsonResponse
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

            // ファイル名を生成
            $filename = "barcode_{$janCode}.pdf";

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => strlen($pdf),
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);

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
    private function generateSimpleBarcodePdf(string $janCode, ?string $bookTitle = null, ?string $bookAuthor = null): string
    {
        try {
            // TCPDFを使用してPDFを生成
            $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // PDFの基本設定
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Book Management System');
            $pdf->SetTitle('Barcode');
            $pdf->SetSubject('Book Barcode');
            
            // ヘッダー・フッターを非表示
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // マージン設定
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(true, 15);
            
            // ページを追加
            $pdf->AddPage();
            
            // タイトル
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'Book Barcode', 0, 1, 'C');
            $pdf->Ln(5);
            
            // 書籍タイトル表示（存在する場合）
            if ($bookTitle && $bookTitle !== 'Unknown Book') {
                // 日本語チェック
                $hasJapanese = preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FAF}]/u', $bookTitle);
                
                if ($hasJapanese) {
                    // 日本語対応フォント
                    $pdf->SetFont('kozminproregular', '', 12);
                } else {
                    $pdf->SetFont('helvetica', '', 12);
                }
                
                // タイトル表示
                $pdf->MultiCell(0, 8, $bookTitle, 0, 'C', false, 1);
                $pdf->Ln(3);
            }
            
            // 著者表示（存在する場合）
            if ($bookAuthor) {
                // 日本語チェック
                $hasJapanese = preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FAF}]/u', $bookAuthor);
                
                if ($hasJapanese) {
                    $pdf->SetFont('kozminproregular', '', 10);
                    $authorText = '著者: ' . $bookAuthor;
                } else {
                    $pdf->SetFont('helvetica', '', 10);
                    $authorText = 'Author: ' . $bookAuthor;
                }
                
                $pdf->Cell(0, 6, $authorText, 0, 1, 'C');
                $pdf->Ln(3);
            }
            
            // JANコード表示
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 8, 'JAN: ' . $janCode, 0, 1, 'C');
            $pdf->Ln(8);
            
            // バーコード生成
            $barcodeStyle = [
                'position' => '',
                'align' => 'C',
                'stretch' => false,
                'fitwidth' => true,
                'cellfitscale' => false,
                'border' => false,
                'hpadding' => 'auto',
                'vpadding' => 'auto',
                'fgcolor' => [0, 0, 0],
                'bgcolor' => false,
                'text' => true,
                'font' => 'helvetica',
                'fontsize' => 10,
                'stretchtext' => 4
            ];
            
            // バーコードを描画
            $pdf->write1DBarcode($janCode, 'EAN13', '', '', '', 18, 0.4, $barcodeStyle, 'N');
            
            // 生成日時
            $pdf->Ln(20);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell(0, 5, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
            
            // PDFを文字列として出力
            return $pdf->Output('', 'S');
            
        } catch (\Exception $e) {
            Log::error('TCPDF生成エラー: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
}