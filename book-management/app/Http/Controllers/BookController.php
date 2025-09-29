<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Exception;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Book::query();

        // 受入日範囲フィルター
        if ($request->filled('start_date')) {
            $query->where('acceptance_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('acceptance_date', '<=', $request->end_date);
        }

        // NDC分類フィルター
        if ($request->filled('ndc')) {
            // NDCは前方一致で検索（例：3で始まる社会科学全般）
            $query->where('ndc', 'like', $request->ndc . '%');
        }

        $books = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'data' => $books,
            'message' => 'Books retrieved successfully'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $book = Book::create($validated);
        return response()->json([
            'data' => $book,
            'message' => 'Book created successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book): JsonResponse
    {
        return response()->json([
            'data' => $book,
            'message' => 'Book retrieved successfully'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookRequest $request, Book $book): JsonResponse
    {
        $validated = $request->validated();
        $book->update($validated);
        return response()->json([
            'data' => $book,
            'message' => 'Book updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book): JsonResponse
    {
        $book->delete();

        return response()->json([
            'message' => 'Book deleted successfully'
        ]);
    }

    /**
     * Search book information by ISBN using NDL API
     */
    public function searchByISBN(Request $request): JsonResponse
    {
        Log::info('BookController@searchByISBN called', ['isbn' => $request->input('isbn')]);

        Log::info('searchByISBN called', ['isbn' => $request->isbn, 'all' => $request->all()]);
        $request->validate([
            'isbn' => 'required|string|min:10|max:17'
        ]);

        $isbn = preg_replace('/[-\s]/', '', $request->isbn);
        
        try {
            $client = new \GuzzleHttp\Client([
                'verify' => false,
                'timeout' => 10
            ]);
            
            $response = $client->get('https://ndlsearch.ndl.go.jp/api/opensearch', [
                'query' => [
                    'isbn' => $isbn,
                    'format' => 'rss'
                ],
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]);

            $xmlContent = $response->getBody()->getContents();
            Log::info('NDL API Response:', ['xml' => $xmlContent]);
            
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlContent);
            
            if ($xml === false) {
                $errors = libxml_get_errors();
                Log::error('XML Parse Error:', ['errors' => $errors]);
                throw new \Exception('XMLパースエラー: ' . implode(', ', array_map(fn($e) => $e->message, $errors)));
            }
            
            Log::info('Parsed XML structure:', ['xml' => json_encode($xml)]);
            
            if (isset($xml->channel) && isset($xml->channel->item) && count($xml->channel->item) > 0) {
                $item = $xml->channel->item[0];
                
                $item->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
                $item->registerXPathNamespace('dcterms', 'http://purl.org/dc/terms/');
                $item->registerXPathNamespace('dcndl', 'http://ndl.go.jp/dcndl/terms/');
                
                Log::info('Item structure:', ['item' => json_encode($item)]);
                
                $bookData = [
                    'title' => null,
                    'title_transcription' => null,
                    'author' => null,
                    'publisher' => null,
                    'published_date' => null,
                    'price' => null,
                    'pages' => null,
                    'ndc' => null
                ];

                if (isset($item->title)) {
                    $bookData['title'] = trim((string)$item->title);
                }

                $dcndlElements = $item->children('http://ndl.go.jp/dcndl/terms/');
                Log::info('DCNDL Elements:', ['dcndl' => json_encode($dcndlElements)]);
                
                if (isset($dcndlElements->titleTranscription)) {
                    $bookData['title_transcription'] = trim((string)$dcndlElements->titleTranscription);
                }

                if (isset($item->author)) {
                    $author = trim((string)$item->author);
                    $author = preg_replace('/\s*(著|編|監修|訳|共著|編著)$/', '', $author);
                    $bookData['author'] = $author;
                }

                $dcElements = $item->children('http://purl.org/dc/elements/1.1/');
                if (isset($dcElements->publisher)) {
                    $bookData['publisher'] = trim((string)$dcElements->publisher);
                }

                if (isset($dcndlElements->price)) {
                    $priceText = trim((string)$dcndlElements->price);
                    if (preg_match('/(\d+)/', $priceText, $matches)) {
                        $bookData['price'] = (float)$matches[1];
                    }
                }

                Log::info('Starting pages extraction from dc:extent...');
                
                $extentNodes = $item->xpath('.//dc:extent');
                if (!empty($extentNodes)) {
                    foreach ($extentNodes as $extentNode) {
                        $extentText = trim((string)$extentNode);
                        Log::info('Found dc:extent via XPath:', ['extent' => $extentText]);
                        
                        if (preg_match('/(\d+)\s*[pページ頁]?/u', $extentText, $matches)) {
                            $bookData['pages'] = (int)$matches[1];
                            Log::info('Pages extracted successfully:', ['extent' => $extentText, 'pages' => $bookData['pages']]);
                            break;
                        }
                    }
                }
                
                if (empty($bookData['pages']) && isset($dcElements->extent)) {
                    $extentText = trim((string)$dcElements->extent);
                    Log::info('DC extent direct access:', ['extent' => $extentText]);
                    
                    if (preg_match('/(\d+)\s*[pページ頁]?/u', $extentText, $matches)) {
                        $bookData['pages'] = (int)$matches[1];
                        Log::info('Pages found via direct access:', ['extent' => $extentText, 'pages' => $bookData['pages']]);
                    }
                }
                
                if (empty($bookData['pages'])) {
                    Log::info('Checking all DC elements for extent...');
                    foreach ($dcElements as $elementName => $element) {
                        if ($elementName === 'extent') {
                            $extentText = trim((string)$element);
                            Log::info('Found dc:extent in element iteration:', ['extent' => $extentText]);
                            
                            if (preg_match('/(\d+)\s*[pページ頁]?/u', $extentText, $matches)) {
                                $bookData['pages'] = (int)$matches[1];
                                Log::info('Pages found via element iteration:', ['extent' => $extentText, 'pages' => $bookData['pages']]);
                                break;
                            }
                        }
                    }
                }
                
                if (empty($bookData['pages'])) {
                    Log::warning('Pages not found in dc:extent elements');
                }

                Log::info('Attempting NDC retrieval...');
                $item->registerXPathNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');
                $ndcTypes = ['NDC10', 'NDC9', 'NDC8'];
                $foundNdc = null;
                foreach ($ndcTypes as $ndcType) {
                    $ndcNodes = $item->xpath("//dc:subject[@xsi:type='dcndl:$ndcType']");
                    if (!empty($ndcNodes)) {
                        $foundNdc = trim((string)$ndcNodes[0]);
                        Log::info("NDC found via XPath dc:subject[@xsi:type='dcndl:$ndcType']: ", ['ndc' => $foundNdc]);
                        break;
                    }
                }
                
                if (!$foundNdc && isset($dcElements->subject)) {
                    foreach ($ndcTypes as $ndcType) {
                        foreach ($dcElements->subject as $subject) {
                            $attributes = $subject->attributes('http://www.w3.org/2001/XMLSchema-instance');
                            if (isset($attributes->type) && (string)$attributes->type === "dcndl:$ndcType") {
                                $foundNdc = trim((string)$subject);
                                Log::info("NDC found via attribute check ($ndcType):", ['ndc' => $foundNdc]);
                                break 2;
                            }
                        }
                    }
                }
                
                if (!$foundNdc && isset($dcElements->subject)) {
                    foreach ($dcElements->subject as $index => $subject) {
                        $subjectText = trim((string)$subject);
                        $allAttributes = [];
                        foreach ($subject->attributes() as $attrName => $attrValue) {
                            $allAttributes[$attrName] = (string)$attrValue;
                        }
                        foreach ($subject->attributes('http://www.w3.org/2001/XMLSchema-instance') as $attrName => $attrValue) {
                            $allAttributes["xsi:$attrName"] = (string)$attrValue;
                        }
                        Log::info("DC Subject element $index:", [
                            'text' => $subjectText,
                            'attributes' => $allAttributes
                        ]);
                        foreach ($ndcTypes as $ndcType) {
                            if (isset($allAttributes['xsi:type']) && $allAttributes['xsi:type'] === "dcndl:$ndcType") {
                                $foundNdc = $subjectText;
                                Log::info("NDC found via detailed attribute check ($ndcType):", ['ndc' => $foundNdc]);
                                break 2;
                            }
                        }
                        
                        if (preg_match('/^NDC[:\s]*([\d.]+)/', $subjectText, $matches)) {
                            $foundNdc = $matches[1];
                            Log::info('NDC found via text pattern fallback:', ['ndc' => $foundNdc]);
                            break;
                        }
                    }
                }
                if ($foundNdc) {
                    $bookData['ndc'] = $foundNdc;
                }

                $dctermsElements = $item->children('http://purl.org/dc/terms/');
                if (isset($dctermsElements->issued)) {
                    $issued = trim((string)$dctermsElements->issued);
                    
                    if (preg_match('/^(\d{4})\.(\d{1,2})$/', $issued, $matches)) {
                        $year = $matches[1];
                        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                        $bookData['published_date'] = "$year-$month-01";
                    } elseif (preg_match('/^(\d{4})$/', $issued)) {
                        $bookData['published_date'] = "$issued-01-01";
                    }
                } elseif (isset($item->pubDate)) {
                    $pubDate = (string)$item->pubDate;
                    try {
                        $date = new \DateTime($pubDate);
                        $bookData['published_date'] = $date->format('Y-m-d');
                    } catch (\Exception $e) {
                        Log::warning('Date parse error:', ['date' => $pubDate, 'error' => $e->getMessage()]);
                    }
                }

                foreach ($bookData as $key => $value) {
                    if (empty($value)) {
                        $bookData[$key] = null;
                    }
                }

                Log::info('Extracted book data:', $bookData);

                return response()->json([
                    'success' => true,
                    'data' => $bookData,
                    'message' => '書籍情報を取得しました'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => '該当する書籍が見つかりませんでした',
                'debug' => [
                    'has_channel' => isset($xml->channel),
                    'has_items' => isset($xml->channel->item),
                    'item_count' => isset($xml->channel->item) ? count($xml->channel->item) : 0
                ]
            ], 404);

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Guzzle Request Error:', [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'API接続エラー: ' . $e->getMessage()
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('General Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '書籍情報の取得に失敗しました: ' . $e->getMessage()
            ], 500);
        }

        return response()->json(['title' => 'サンプルタイトル', 'author' => 'サンプル著者']);
    }

    /**
     * Export books list to PDF
     */
    /**
     * 貸出可能な書籍一覧を取得
     */
    public function available(): JsonResponse
    {
        // 貸出中でない書籍を取得
        $books = Book::whereDoesntHave('borrows', function($query) {
            $query->whereNull('returned_date');
        })
        ->orderBy('title')
        ->get();

        return response()->json([
            'data' => $books,
            'message' => 'Available books retrieved successfully'
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        try {
            $query = Book::query();

            // NDC分類フィルター
            if ($request->filled('ndc')) {
                $query->where('ndc', 'like', $request->ndc . '%');
            }

            // 受入日範囲フィルター
            if ($request->filled('start_date')) {
                $query->where('acceptance_date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->where('acceptance_date', '<=', $request->end_date);
            }

            // 受入年月日の早い順（古い順）でソート
            $books = $query->orderBy('acceptance_date', 'asc')->get();

            // TCPDFのセットアップ
            $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

            $pdf->SetCreator('蔵書管理システム');
            $pdf->SetAuthor('蔵書管理システム');
            $pdf->SetTitle('書籍一覧');
            $pdf->SetSubject('書籍一覧PDF');

            // フォントの設定
            $this->setPdfFont($pdf);

            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(10, 10, 10);
            $pdf->SetAutoPageBreak(true, 10);

            $pdf->AddPage();

            // タイトル
            $pdf->SetFont('dejavusans', 'B', 16);
            $pdf->Cell(0, 10, '書籍一覧', 0, 1, 'C');
            $pdf->Ln(5);

            // 出力日時とフィルター条件の表示
            $this->addPdfHeader($pdf, $request);

            // テーブルヘッダー
            $this->addTableHeaders($pdf);

            // データ行
            $this->addTableData($pdf, $books);

            // PDFの出力
            $filename = '書籍一覧_' . date('Ymd_His') . '.pdf';
            $pdfContent = $pdf->Output($filename, 'S');
            
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => strlen($pdfContent),
            ]);

        } catch (\Exception $e) {
            Log::error('PDF出力エラー: ' . $e->getMessage());
            Log::error('PDF出力エラー詳細: ', ['trace' => $e->getTraceAsString()]);
            
            return response()->json([
                'error' => 'PDF出力中にエラーが発生しました',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PDFのフォントを設定する
     */
    private function setPdfFont($pdf)
    {
        try {
            $pdf->SetFont('dejavusans', '', 9);
        } catch (\Exception $e) {
            Log::warning('DejaVu Sans font not available, using fallback');
            try {
                $pdf->SetFont('helvetica', '', 9);
            } catch (\Exception $e) {
                Log::error('Font setting failed: ' . $e->getMessage());
                throw new \Exception('フォントの設定に失敗しました');
            }
        }
    }

    /**
     * PDFヘッダー情報を追加する
     */
    private function addPdfHeader($pdf, $request)
    {
        $pdf->SetFont('dejavusans', '', 10);
        
        // 出力日時
        $pdf->Cell(0, 8, '出力日時: ' . date('Y年m月d日 H:i'), 0, 1, 'R');
        
        // フィルター条件
        $filterText = '';
        if ($request->filled('ndc')) {
            $ndcMap = [
                '0' => '総記', '1' => '哲学', '2' => '歴史', '3' => '社会科学', '4' => '自然科学',
                '5' => '技術・工学', '6' => '産業', '7' => '芸術・美術', '8' => '言語', '9' => '文学'
            ];
            $ndcValue = $request->ndc;
            $filterText .= 'NDC分類: ' . $ndcValue . ' ' . ($ndcMap[$ndcValue] ?? '') . '　';
        }
        
        if ($request->filled('start_date') || $request->filled('end_date')) {
            $filterText .= '受入期間: ';
            if ($request->filled('start_date')) {
                $filterText .= date('Y年m月d日', strtotime($request->start_date));
            }
            $filterText .= ' ～ ';
            if ($request->filled('end_date')) {
                $filterText .= date('Y年m月d日', strtotime($request->end_date));
            }
        }
        
        if ($filterText) {
            $pdf->Cell(0, 8, 'フィルター条件: ' . $filterText, 0, 1, 'L');
        }
        $pdf->Ln(3);
    }

    /**
     * テーブルヘッダーを追加する
     */
    private function addTableHeaders($pdf)
    {
        $pdf->SetFont('dejavusans', 'B', 8);
        $pdf->SetFillColor(240, 240, 240);
        
        $headers = [
            '受入年月日' => 25, 'タイトル' => 50, '著者' => 35, '出版社' => 30, '出版日' => 20,
            'ページ数' => 15, '受入種別' => 20, '受入元' => 25, '価格' => 15, '図書分類' => 20
        ];

        foreach ($headers as $header => $width) {
            $pdf->Cell($width, 8, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        return $headers;
    }

    /**
     * テーブルデータを追加する
     */
    private function addTableData($pdf, $books)
    {
        $pdf->SetFont('dejavusans', '', 7);
        $pdf->SetFillColor(255, 255, 255);
        
        $headers = $this->addTableHeaders($pdf);
        $colWidths = array_values($headers);

        foreach ($books as $book) {
            // 改ページの判定
            if ($pdf->GetY() > 180) { // A4横向きの場合
                $pdf->AddPage();
                $this->addTableHeaders($pdf);
                $pdf->SetFont('dejavusans', '', 7);
            }

            // データの準備
            $classification = $this->getBookClassification($book);
            
            $rowData = [
                $book->acceptance_date ? $book->acceptance_date->format('Y/m/d') : '',
                $this->sanitizeText($book->title ?: ''),
                $this->sanitizeText($book->author ?: ''),
                $this->sanitizeText($book->publisher ?: ''),
                $book->published_date ? $book->published_date->format('Y/m/d') : '',
                $book->pages ?: '',
                $this->sanitizeText($book->acceptance_type ?: ''),
                $this->sanitizeText($book->acceptance_source ?: ''),
                $book->price ? number_format($book->price) . '円' : '',
                $classification
            ];

            // 行の出力
            $colIndex = 0;
            foreach ($rowData as $data) {
                $width = $colWidths[$colIndex];
                $pdf->Cell($width, 6, $data, 1, 0, 'C', false);
                $colIndex++;
            }
            $pdf->Ln();
        }
    }

    /**
     * 図書分類を取得する
     */
    private function getBookClassification($book)
    {
        $yomiInitial = '';
        if ($book->title_transcription) {
            $yomiInitial = mb_substr($book->title_transcription, 0, 1, 'UTF-8');
        }
        
        $classification = '';
        if ($book->ndc && $yomiInitial) {
            $classification = $book->ndc . '-' . $yomiInitial;
        } elseif ($book->ndc) {
            $classification = $book->ndc;
        } elseif ($yomiInitial) {
            $classification = $yomiInitial;
        }
        
        return $classification;
    }

    /**
     * テキストをサニタイズする
     */
    private function sanitizeText($text)
    {
        if (!$text) return '';
        
        // UTF-8エンコーディングの確認と変換
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }
        
        // 制御文字の除去
        $text = preg_replace('/[\x00-\x1F\x7F]/', '', $text);
        
        // 長すぎるテキストの切り詰め
        if (mb_strlen($text, 'UTF-8') > 50) {
            $text = mb_substr($text, 0, 47, 'UTF-8') . '...';
        }
        
        return $text;
    }
}
