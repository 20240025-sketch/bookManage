<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Enums\ReadingStatus;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Exception;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $books = Book::orderBy('created_at', 'desc')->get();
        
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
        if (empty($validated['reading_status'])) {
            $validated['reading_status'] = 'unread';
        }
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
        // Add debugging log to verify the method is called
        \Log::info('BookController@searchByISBN called', ['isbn' => $request->input('isbn')]);

        \Log::info('searchByISBN called', ['isbn' => $request->isbn, 'all' => $request->all()]);
        $request->validate([
            'isbn' => 'required|string|min:10|max:17'
        ]);

        $isbn = preg_replace('/[-\s]/', '', $request->isbn);
        
        try {
            $client = new \GuzzleHttp\Client([
                'verify' => false, // SSL証明書の検証を無効化（開発環境用）
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
            
            // デバッグ用: XMLの内容をログに出力
            \Log::info('NDL API Response:', ['xml' => $xmlContent]);
            
            // libxml_use_internal_errorsを使用してXMLエラーを詳細に取得
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlContent);
            
            if ($xml === false) {
                $errors = libxml_get_errors();
                \Log::error('XML Parse Error:', ['errors' => $errors]);
                throw new \Exception('XMLパースエラー: ' . implode(', ', array_map(fn($e) => $e->message, $errors)));
            }
            
            // XMLの構造を確認
            \Log::info('Parsed XML structure:', ['xml' => json_encode($xml)]);
            
            // RSS形式の場合、channelの下にitemがある
            if (isset($xml->channel) && isset($xml->channel->item) && count($xml->channel->item) > 0) {
                $item = $xml->channel->item[0];
                
                // XML名前空間を登録（$itemに対して）
                $item->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
                $item->registerXPathNamespace('dcterms', 'http://purl.org/dc/terms/');
                $item->registerXPathNamespace('dcndl', 'http://ndl.go.jp/dcndl/terms/');
                
                // デバッグ用: itemの詳細構造をログ出力
                \Log::info('Item structure:', ['item' => json_encode($item)]);
                
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

                // タイトルの取得
                if (isset($item->title)) {
                    $bookData['title'] = trim((string)$item->title);
                }

                // タイトルのヨミの取得（dcndl:titleTranscription）
                $dcndlElements = $item->children('http://ndl.go.jp/dcndl/terms/');
                \Log::info('DCNDL Elements:', ['dcndl' => json_encode($dcndlElements)]);
                
                if (isset($dcndlElements->titleTranscription)) {
                    $bookData['title_transcription'] = trim((string)$dcndlElements->titleTranscription);
                }

                // 著者の取得
                if (isset($item->author)) {
                    $author = trim((string)$item->author);
                    // "著", "編", "監修"などを除去
                    $author = preg_replace('/\s*(著|編|監修|訳|共著|編著)$/', '', $author);
                    $bookData['author'] = $author;
                }

                // 出版社の取得（DCメタデータから）
                $dcElements = $item->children('http://purl.org/dc/elements/1.1/');
                if (isset($dcElements->publisher)) {
                    $bookData['publisher'] = trim((string)$dcElements->publisher);
                }

                // 価格の取得（dcndl:price）
                if (isset($dcndlElements->price)) {
                    $priceText = trim((string)$dcndlElements->price);
                    // "1500円" のような形式から数値を抽出
                    if (preg_match('/(\d+)/', $priceText, $matches)) {
                        $bookData['price'] = (float)$matches[1];
                    }
                }

                // ページ数の取得（dc:extent から数値を抽出）
                \Log::info('Starting pages extraction from dc:extent...');
                
                // 方法1: XPathで直接dc:extent要素を検索
                $extentNodes = $item->xpath('.//dc:extent');
                if (!empty($extentNodes)) {
                    foreach ($extentNodes as $extentNode) {
                        $extentText = trim((string)$extentNode);
                        \Log::info('Found dc:extent via XPath:', ['extent' => $extentText]);
                        
                        // "166p", "166ページ", "166頁" などの形式から数値を抽出
                        if (preg_match('/(\d+)\s*[pページ頁]?/u', $extentText, $matches)) {
                            $bookData['pages'] = (int)$matches[1];
                            \Log::info('Pages extracted successfully:', ['extent' => $extentText, 'pages' => $bookData['pages']]);
                            break;
                        }
                    }
                }
                
                // 方法2: DCメタデータ要素から直接アクセス（フォールバック）
                if (empty($bookData['pages']) && isset($dcElements->extent)) {
                    $extentText = trim((string)$dcElements->extent);
                    \Log::info('DC extent direct access:', ['extent' => $extentText]);
                    
                    if (preg_match('/(\d+)\s*[pページ頁]?/u', $extentText, $matches)) {
                        $bookData['pages'] = (int)$matches[1];
                        \Log::info('Pages found via direct access:', ['extent' => $extentText, 'pages' => $bookData['pages']]);
                    }
                }
                
                // 方法3: すべてのDC要素を順次チェック（最終フォールバック）
                if (empty($bookData['pages'])) {
                    \Log::info('Checking all DC elements for extent...');
                    foreach ($dcElements as $elementName => $element) {
                        if ($elementName === 'extent') {
                            $extentText = trim((string)$element);
                            \Log::info('Found dc:extent in element iteration:', ['extent' => $extentText]);
                            
                            if (preg_match('/(\d+)\s*[pページ頁]?/u', $extentText, $matches)) {
                                $bookData['pages'] = (int)$matches[1];
                                \Log::info('Pages found via element iteration:', ['extent' => $extentText, 'pages' => $bookData['pages']]);
                                break;
                            }
                        }
                    }
                }
                
                if (empty($bookData['pages'])) {
                    \Log::warning('Pages not found in dc:extent elements');
                }

                // 日本十進分類法の取得（NDC10→NDC9→NDC8の順で優先）
                \Log::info('Attempting NDC retrieval...');
                $item->registerXPathNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');
                $ndcTypes = ['NDC10', 'NDC9', 'NDC8'];
                $foundNdc = null;
                foreach ($ndcTypes as $ndcType) {
                    $ndcNodes = $item->xpath("//dc:subject[@xsi:type='dcndl:$ndcType']");
                    if (!empty($ndcNodes)) {
                        $foundNdc = trim((string)$ndcNodes[0]);
                        \Log::info("NDC found via XPath dc:subject[@xsi:type='dcndl:$ndcType']: ", ['ndc' => $foundNdc]);
                        break;
                    }
                }
                // 属性チェック（フォールバック）
                if (!$foundNdc && isset($dcElements->subject)) {
                    foreach ($ndcTypes as $ndcType) {
                        foreach ($dcElements->subject as $subject) {
                            $attributes = $subject->attributes('http://www.w3.org/2001/XMLSchema-instance');
                            if (isset($attributes->type) && (string)$attributes->type === "dcndl:$ndcType") {
                                $foundNdc = trim((string)$subject);
                                \Log::info("NDC found via attribute check ($ndcType):", ['ndc' => $foundNdc]);
                                break 2;
                            }
                        }
                    }
                }
                // すべてのdc:subject要素をチェックして属性をログ出力（さらにフォールバック）
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
                        \Log::info("DC Subject element $index:", [
                            'text' => $subjectText,
                            'attributes' => $allAttributes
                        ]);
                        foreach ($ndcTypes as $ndcType) {
                            if (isset($allAttributes['xsi:type']) && $allAttributes['xsi:type'] === "dcndl:$ndcType") {
                                $foundNdc = $subjectText;
                                \Log::info("NDC found via detailed attribute check ($ndcType):", ['ndc' => $foundNdc]);
                                break 2;
                            }
                        }
                        // フォールバック: NDCで始まるテキスト
                        if (preg_match('/^NDC[:\s]*([\d.]+)/', $subjectText, $matches)) {
                            $foundNdc = $matches[1];
                            \Log::info('NDC found via text pattern fallback:', ['ndc' => $foundNdc]);
                            break;
                        }
                    }
                }
                if ($foundNdc) {
                    $bookData['ndc'] = $foundNdc;
                }

                // 出版日の取得（DCTermsから）
                $dctermsElements = $item->children('http://purl.org/dc/terms/');
                if (isset($dctermsElements->issued)) {
                    $issued = trim((string)$dctermsElements->issued);
                    // "2023.9" 形式を "2023-09-01" 形式に変換
                    if (preg_match('/^(\d{4})\.(\d{1,2})$/', $issued, $matches)) {
                        $year = $matches[1];
                        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                        $bookData['published_date'] = "$year-$month-01";
                    } elseif (preg_match('/^(\d{4})$/', $issued)) {
                        $bookData['published_date'] = "$issued-01-01";
                    }
                } elseif (isset($item->pubDate)) {
                    // pubDateからの取得（フォールバック）
                    $pubDate = (string)$item->pubDate;
                    try {
                        $date = new \DateTime($pubDate);
                        $bookData['published_date'] = $date->format('Y-m-d');
                    } catch (\Exception $e) {
                        \Log::warning('Date parse error:', ['date' => $pubDate, 'error' => $e->getMessage()]);
                    }
                }

                // データのクリーンアップ（空文字列をnullに変換）
                foreach ($bookData as $key => $value) {
                    if (empty($value)) {
                        $bookData[$key] = null;
                    }
                }

                \Log::info('Extracted book data:', $bookData);

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
            \Log::error('Guzzle Request Error:', [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'API接続エラー: ' . $e->getMessage()
            ], 500);
            
        } catch (\Exception $e) {
            \Log::error('General Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '書籍情報の取得に失敗しました: ' . $e->getMessage()
            ], 500);
        }

        // For debugging, if no processing is done, a dummy response can be returned
        return response()->json(['title' => 'サンプルタイトル', 'author' => 'サンプル著者']);
    }

    /**
     * Export books list to PDF
     */
    public function exportPdf(Request $request): Response
    {
        try {
            // 書籍データを取得
            $books = Book::all();

            // TCPDFのインスタンスを作成（UTF-8エンコーディングを明示的に指定）
            $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

            // ドキュメント情報を設定
            $pdf->SetCreator('蔵書管理システム');
            $pdf->SetAuthor('蔵書管理システム');
            $pdf->SetTitle('書籍一覧');
            $pdf->SetSubject('書籍一覧PDF');

            // カスタム日本語フォント設定
            $fontname = null;
            $fontPath = public_path('seieiIPAexMincho.ttf');
            
            try {
                // フォントファイルの存在を確認
                if (file_exists($fontPath)) {
                    // カスタムフォントを追加
                    $fontname = \TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 96);
                    \Log::info('Custom font loaded successfully', ['fontname' => $fontname]);
                } else {
                    \Log::warning('Font file not found', ['path' => $fontPath]);
                }
            } catch (Exception $e) {
                \Log::error('Error loading custom font', ['error' => $e->getMessage()]);
            }

            // フォント設定（カスタムフォント優先、フォールバック設定）
            if ($fontname) {
                $pdf->SetFont($fontname, '', 9);
            } else {
                // フォールバック：TCPDFでサポートされているCJK（中日韓）フォントを使用
                try {
                    // CJK用のdejavusansフォントを使用（日本語サポートあり）
                    $pdf->SetFont('dejavusans', '', 9);
                } catch (Exception $e) {
                    try {
                        // freeserifフォント（Unicode対応）
                        $pdf->SetFont('freeserif', '', 9);
                    } catch (Exception $e) {
                        try {
                            // cid0jp フォント（日本語専用）
                            $pdf->SetFont('cid0jp', '', 9);
                        } catch (Exception $e) {
                            // 最後の手段としてHelvetica（ASCII文字のみ）
                            $pdf->SetFont('helvetica', '', 9);
                        }
                    }
                }
            }

            // デフォルトのヘッダー・フッターを無効化
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // マージンを設定
            $pdf->SetMargins(10, 15, 10);
            $pdf->SetAutoPageBreak(true, 20);

            // ページを追加
            $pdf->AddPage();

            // タイトルを追加
            if ($fontname) {
                $pdf->SetFont($fontname, 'B', 16);
            } else {
                try {
                    $pdf->SetFont('dejavusans', 'B', 16);
                } catch (Exception $e) {
                    try {
                        $pdf->SetFont('freeserif', 'B', 16);
                    } catch (Exception $e) {
                        $pdf->SetFont('helvetica', 'B', 16);
                    }
                }
            }
            $pdf->Cell(0, 10, '書籍一覧', 0, 1, 'C');
            $pdf->Ln(5);

            // 出力日時を追加
            if ($fontname) {
                $pdf->SetFont($fontname, '', 10);
            } else {
                try {
                    $pdf->SetFont('dejavusans', '', 10);
                } catch (Exception $e) {
                    try {
                        $pdf->SetFont('freeserif', '', 10);
                    } catch (Exception $e) {
                        $pdf->SetFont('helvetica', '', 10);
                    }
                }
            }
            $pdf->Cell(0, 8, '出力日時: ' . date('Y年m月d日 H:i'), 0, 1, 'R');
            $pdf->Ln(3);

            // テーブルヘッダー
            if ($fontname) {
                $pdf->SetFont($fontname, 'B', 8);
            } else {
                try {
                    $pdf->SetFont('dejavusans', 'B', 8);
                } catch (Exception $e) {
                    try {
                        $pdf->SetFont('freeserif', 'B', 8);
                    } catch (Exception $e) {
                        $pdf->SetFont('helvetica', 'B', 8);
                    }
                }
            }
            $pdf->SetFillColor(240, 240, 240);
            
            $headers = [
                '受入年月日' => 25,
                'タイトル' => 50,
                '著者' => 35,
                '出版社' => 30,
                '出版日' => 20,
                'ページ数' => 15,
                '受入種別' => 20,
                '受け入れ元' => 25,
                '価格' => 15,
                'NDC分類' => 15,
                'ヨミ頭文字' => 15
            ];

            foreach ($headers as $header => $width) {
                $pdf->Cell($width, 8, $header, 1, 0, 'C', true);
            }
            $pdf->Ln();

            // データ行
            if ($fontname) {
                $pdf->SetFont($fontname, '', 7);
            } else {
                try {
                    $pdf->SetFont('dejavusans', '', 7);
                } catch (Exception $e) {
                    try {
                        $pdf->SetFont('freeserif', '', 7);
                    } catch (Exception $e) {
                        $pdf->SetFont('helvetica', '', 7);
                    }
                }
            }
            $pdf->SetFillColor(255, 255, 255);

            foreach ($books as $book) {
                // 改ページチェック
                if ($pdf->GetY() > 170) {
                    $pdf->AddPage();
                    
                    // ヘッダーを再表示
                    if ($fontname) {
                        $pdf->SetFont($fontname, 'B', 8);
                    } else {
                        try {
                            $pdf->SetFont('dejavusans', 'B', 8);
                        } catch (Exception $e) {
                            try {
                                $pdf->SetFont('freeserif', 'B', 8);
                            } catch (Exception $e) {
                                $pdf->SetFont('helvetica', 'B', 8);
                            }
                        }
                    }
                    $pdf->SetFillColor(240, 240, 240);
                    
                    foreach ($headers as $header => $width) {
                        $pdf->Cell($width, 8, $header, 1, 0, 'C', true);
                    }
                    $pdf->Ln();
                    
                    if ($fontname) {
                        $pdf->SetFont($fontname, '', 7);
                    } else {
                        try {
                            $pdf->SetFont('dejavusans', '', 7);
                        } catch (Exception $e) {
                            try {
                                $pdf->SetFont('freeserif', '', 7);
                            } catch (Exception $e) {
                                $pdf->SetFont('helvetica', '', 7);
                            }
                        }
                    }
                    $pdf->SetFillColor(255, 255, 255);
                }

                // タイトルのヨミの頭文字を取得
                $yomiInitial = $book->title_transcription ? mb_substr($book->title_transcription, 0, 1, 'UTF-8') : '';

                // 各列のデータを準備
                $rowData = [
                    $book->acceptance_date ? $book->acceptance_date->format('Y/m/d') : '',
                    $book->title ?: '',
                    $book->author ?: '',
                    $book->publisher ?: '',
                    $book->published_date ? $book->published_date->format('Y/m/d') : '',
                    $book->pages ?: '',
                    $book->acceptance_type ?: '',
                    $book->acceptance_source ?: '',
                    $book->price ? number_format($book->price) . '円' : '',
                    $book->ndc ?: '',
                    $yomiInitial
                ];

                // 必要な行数を計算（長いテキストがある列を基準）
                $requiredLines = 1;
                $colWidths = array_values($headers);
                
                // タイトル、著者、出版社の長さをチェック
                $longTextColumns = [1, 2, 3]; // タイトル、著者、出版社のインデックス
                foreach ($longTextColumns as $colIndex) {
                    $text = $rowData[$colIndex];
                    if (!empty($text)) {
                        $width = $colWidths[$colIndex];
                        $textWidth = $pdf->GetStringWidth($text);
                        $lines = ceil($textWidth / ($width - 2)); // マージンを考慮
                        $requiredLines = max($requiredLines, $lines);
                    }
                }
                
                // 最大3行に制限し、行の高さを計算
                $requiredLines = min($requiredLines, 3);
                $cellHeight = $requiredLines * 4;

                // 現在のY位置を記録
                $startY = $pdf->GetY();
                $startX = $pdf->GetX();

                // 各列のデータを出力（手動でセルを描画して高さを完全統一）
                $colIndex = 0;
                $currentX = $startX; // X座標をリセット
                
                foreach ($rowData as $data) {
                    $width = $colWidths[$colIndex];
                    
                    // 文字エンコーディングを確実にする
                    if (!mb_check_encoding($data, 'UTF-8')) {
                        $data = mb_convert_encoding($data, 'UTF-8', 'auto');
                    }
                    
                    // セルの枠線を描画
                    $pdf->Rect($currentX, $startY, $width, $cellHeight);
                    
                    // テキストの処理と配置
                    if (in_array($colIndex, [1, 2, 3]) && !empty($data)) {
                        // 長いテキストの列（タイトル、著者、出版社）: 複数行対応
                        $lines = [];
                        $words = mb_str_split($data, 1, 'UTF-8');
                        $currentLine = '';
                        
                        foreach ($words as $char) {
                            $testLine = $currentLine . $char;
                            $testWidth = $pdf->GetStringWidth($testLine);
                            
                            if ($testWidth > ($width - 2) && !empty($currentLine)) {
                                $lines[] = $currentLine;
                                $currentLine = $char;
                            } else {
                                $currentLine = $testLine;
                            }
                        }
                        
                        if (!empty($currentLine)) {
                            $lines[] = $currentLine;
                        }
                        
                        // 最大行数に制限
                        $lines = array_slice($lines, 0, $requiredLines);
                        
                        // 各行を描画
                        foreach ($lines as $lineIndex => $line) {
                            $lineY = $startY + 1 + ($lineIndex * 4);
                            $pdf->SetXY($currentX + 1, $lineY);
                            $pdf->Cell($width - 2, 3, $line, 0, 0, 'L', false);
                        }
                    } else {
                        // その他の列: 単行、中央揃え
                        $textY = $startY + ($cellHeight / 2) - 1.5; // 垂直中央に配置
                        $pdf->SetXY($currentX, $textY);
                        $pdf->Cell($width, 3, $data, 0, 0, 'C', false);
                    }
                    
                    $currentX += $width;
                    $colIndex++;
                }
                
                // 次の行に移動（Y座標のみ更新、X座標は左端にリセット）
                $pdf->SetXY($startX, $startY + $cellHeight);
            }

            // フッター（ページ番号）
            $pdf->SetY(-15);
            if ($fontname) {
                $pdf->SetFont($fontname, '', 8);
            } else {
                try {
                    $pdf->SetFont('dejavusans', '', 8);
                } catch (Exception $e) {
                    try {
                        $pdf->SetFont('freeserif', '', 8);
                    } catch (Exception $e) {
                        $pdf->SetFont('helvetica', '', 8);
                    }
                }
            }
            $pdf->Cell(0, 8, 'ページ ' . $pdf->getAliasNumPage() . ' / ' . $pdf->getAliasNbPages(), 0, 0, 'C');

            // PDFを出力
            $filename = '書籍一覧_' . date('Ymd_His') . '.pdf';
            
            return response($pdf->Output($filename, 'S'), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => strlen($pdf->Output($filename, 'S')),
            ]);

        } catch (\Exception $e) {
            \Log::error('PDF出力エラー: ' . $e->getMessage());
            return response()->json([
                'error' => 'PDF出力中にエラーが発生しました: ' . $e->getMessage()
            ], 500);
        }
    }
}
