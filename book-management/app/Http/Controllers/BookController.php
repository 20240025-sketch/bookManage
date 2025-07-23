<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Enums\ReadingStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

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
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'author' => 'required|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'published_date' => 'nullable|date',
            'isbn' => 'nullable|string|max:20',
            'pages' => 'nullable|integer|min:1',
            'reading_status' => ['required', Rule::enum(ReadingStatus::class)],
            'description' => 'nullable|string',
        ]);

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
    public function update(Request $request, Book $book): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'subtitle' => 'sometimes|nullable|string|max:255',
            'author' => 'sometimes|required|string|max:255',
            'publisher' => 'sometimes|nullable|string|max:255',
            'published_date' => 'sometimes|nullable|date',
            'isbn' => 'sometimes|nullable|string|max:20',
            'pages' => 'sometimes|nullable|integer|min:1',
            'reading_status' => ['sometimes', 'required', Rule::enum(ReadingStatus::class)],
            'description' => 'sometimes|nullable|string',
        ]);

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
                
                // XML名前空間を登録
                $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
                $xml->registerXPathNamespace('dcterms', 'http://purl.org/dc/terms/');
                $xml->registerXPathNamespace('dcndl', 'http://ndl.go.jp/dcndl/terms/');
                
                $bookData = [
                    'title' => null,
                    'author' => null,
                    'publisher' => null,
                    'published_date' => null
                ];

                // タイトルの取得
                if (isset($item->title)) {
                    $bookData['title'] = trim((string)$item->title);
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
    }
}
