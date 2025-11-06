<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;

class BookController extends Controller
{
    public function index(Request $request)
    {
        try {
            Log::info('BookController index - Starting request');
            
            // 認証チェック（認証失敗時はゲストとして動作）
            /** @var Student|null $student */
            $student = Auth::user();
            Log::info('BookController index - Auth user: ' . ($student ? 'Authenticated (ID: ' . $student->id . ')' : 'Not authenticated'));
            
            // 認証が失敗した場合はゲストユーザーとして動作
            if (!$student) {
                Log::warning('BookController index - No authenticated user, using guest mode');
                // ゲストユーザーを作成（管理者権限で動作）
                $student = new \App\Models\Student();
                $student->id = 0;
                $student->email = 'guest@system.local';
                $student->name = 'Guest User';
            }
            
            $query = Book::query();

        // 検索
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // ISBN検索
        if ($request->filled('isbn')) {
            $isbn = $request->isbn;
            // ハイフンやスペースを除去して検索
            $cleanedIsbn = preg_replace('/[-\s]/', '', $isbn);
            $query->where(function($q) use ($cleanedIsbn, $isbn) {
                $q->where('isbn', $cleanedIsbn)
                  ->orWhere('isbn', $isbn)
                  ->orWhere('isbn', 'like', '%' . $cleanedIsbn . '%');
            });
        }

        // NDC分類フィルター
        if ($request->filled('ndc_category')) {
            $this->applyNdcFilter($query, $request->ndc_category);
        }

        // カテゴリフィルター
        if ($request->filled('category')) {
            $query->category($request->category);
        }

        // 読書状況フィルター
        if ($request->filled('reading_status')) {
            $query->readingStatus($request->reading_status);
        }

        // ISBNコード有無フィルター
        if ($request->filled('isbn_type')) {
            $isbnType = $request->isbn_type;
            if ($isbnType === 'with_isbn') {
                // ISBNコードあり：カスタムJANコード（938525で始まる）以外
                $query->where(function($q) {
                    $q->whereNotNull('isbn')
                      ->where('isbn', '!=', '')
                      ->where('isbn', 'not like', '938525%');
                });
            } elseif ($isbnType === 'without_isbn') {
                // ISBNコードなし：カスタムJANコード（938525で始まる）または空
                $query->where(function($q) {
                    $q->whereNull('isbn')
                      ->orWhere('isbn', '=', '')
                      ->orWhere('isbn', 'like', '938525%');
                });
            }
        }

        // 保管場所フィルター
        if ($request->filled('storage_location')) {
            $query->where('storage_location', $request->storage_location);
        }

        // 受入日範囲フィルター
        if ($request->filled('start_date') || $request->filled('end_date')) {
            $query->whereNotNull('acceptance_date');
            
            if ($request->filled('start_date')) {
                $query->where('acceptance_date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->where('acceptance_date', '<=', $request->end_date);
            }
        }

        // ソート
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Eager Loading for borrows with all history
        $query->with(['borrows' => function($query) {
            $query->orderBy('borrowed_date', 'desc')
                  ->take(3)  // 直近3件の履歴を取得
                  ->with('student'); // 生徒情報も一緒に取得
        }]);

        // ページネーション
        $books = $query->paginate(20);

        Log::info('BookController index - Successfully loaded ' . $books->count() . ' books');
        return BookResource::collection($books);
        
        } catch (\Exception $e) {
            Log::error('BookController index error: ' . $e->getMessage());
            Log::error('BookController index trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'データの読み込みに失敗しました',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreBookRequest $request)
    {
        try {
            // 権限チェック: 書籍作成権限があるかどうか
            /** @var Student|null $student */
            $student = Auth::user();
            
            Log::info('BookController store - Starting', [
                'authenticated' => $student ? 'yes' : 'no',
                'user_id' => $student ? $student->id : null,
                'user_email' => $student ? $student->email : null,
            ]);
            
            // 認証されていない場合、またはゲストの場合は管理者として扱う
            // これにより開発環境や認証なしでのアクセスでも動作する
            if (!$student) {
                Log::warning('BookController store - No authenticated user, allowing access');
                // 権限チェックをスキップ
            } else {
                $canCreate = $student->canCreateBooks();
                $isAdmin = $student->isAdmin();
                Log::info('BookController store - Permission check', [
                    'canCreateBooks' => $canCreate,
                    'isAdmin' => $isAdmin,
                    'email' => $student->email,
                ]);
                
                if (!$canCreate) {
                    Log::warning('BookController store - Permission denied');
                    return response()->json([
                        'message' => 'この操作を行う権限がありません。管理者権限が必要です。',
                        'success' => false,
                        'debug' => [
                            'isAdmin' => $isAdmin,
                            'email' => $student->email,
                        ]
                    ], 403);
                }
            }

            Log::info('BookController store - Validated data', $request->validated());
            
            DB::beginTransaction();
            
            $book = Book::create($request->validated());
            
            Log::info('BookController store - Book created', ['book_id' => $book->id]);
            
            // JANコードが含まれている場合（独自JANコード登録）
            if ($request->has('jan_code')) {
                $janCode = $request->jan_code;
                
                // JANコードレコードを更新して書籍と関連付け
                \App\Models\JanCode::where('jan_code', $janCode)
                    ->update([
                        'book_id' => $book->id,
                        'is_used' => true
                    ]);
                Log::info('BookController store - JAN code updated', ['jan_code' => $janCode]);
            }
            
            // リクエストされた本と一致するかチェックして通知を作成
            $this->checkAndNotifyBookRequests($book);
            
            DB::commit();
            
            Log::info('BookController store - Success');
            return new BookResource($book);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BookController store - Failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => '書籍の作成に失敗しました: ' . $e->getMessage(),
                'success' => false,
                'error_details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            ], 500);
        }
    }

    public function show(Book $book)
    {
        $book->load(['borrows' => function($query) {
            $query->orderBy('borrowed_date', 'desc')
                  ->with('student');
        }]);
        
        // 独自JANコードの場合、JANコード情報も読み込み
        if ($book->isbn && strlen($book->isbn) === 13 && str_starts_with($book->isbn, '938525')) {
            $janCodeRecord = \App\Models\JanCode::where('jan_code', $book->isbn)->first();
            if ($janCodeRecord) {
                $book->jan_code_info = $janCodeRecord;
            }
        }
        
        return new BookResource($book);
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        try {
            // 権限チェック: 書籍編集権限があるかどうか
            /** @var Student|null $student */
            $student = Auth::user();
            
            Log::info('BookController update - Starting', [
                'book_id' => $book->id,
                'authenticated' => $student ? 'yes' : 'no',
                'user_id' => $student ? $student->id : null,
                'user_email' => $student ? $student->email : null,
            ]);
            
            // 認証されていない場合、またはゲストの場合は管理者として扱う
            // これにより開発環境や認証なしでのアクセスでも動作する
            if (!$student) {
                Log::warning('BookController update - No authenticated user, allowing access');
                // 権限チェックをスキップ
            } else {
                $canEdit = $student->canEditBooks();
                $isAdmin = $student->isAdmin();
                Log::info('BookController update - Permission check', [
                    'canEditBooks' => $canEdit,
                    'isAdmin' => $isAdmin,
                    'email' => $student->email,
                ]);
                
                if (!$canEdit) {
                    Log::warning('BookController update - Permission denied');
                    return response()->json([
                        'message' => 'この操作を行う権限がありません。管理者権限が必要です。',
                        'success' => false,
                        'debug' => [
                            'isAdmin' => $isAdmin,
                            'email' => $student->email,
                        ]
                    ], 403);
                }
            }

            Log::info('BookController update - Validated data', $request->validated());
            
            $book->update($request->validated());
            
            Log::info('BookController update - Success', ['book_id' => $book->id]);
            return new BookResource($book);
        } catch (\Exception $e) {
            Log::error('BookController update - Failed', [
                'book_id' => $book->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => '書籍の更新に失敗しました: ' . $e->getMessage(),
                'success' => false,
                'error_details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            ], 500);
        }
    }

    public function destroy(Book $book)
    {
        // 権限チェック: 書籍編集権限があるかどうか
        /** @var Student|null $student */
        $student = Auth::user();
        
        // 認証が失敗した場合はゲストユーザーとして動作
        if (!$student) {
            $student = new \App\Models\Student();
            $student->id = 0;
            $student->email = 'guest@system.local';
            $student->name = 'Guest User';
        }
        
        if (!$student->canEditBooks()) {
            return response()->json([
                'message' => 'この操作を行う権限がありません',
                'success' => false
            ], 403);
        }

        try {
            $book->delete();
            
            return response()->noContent();
        } catch (\Exception $e) {
            Log::error('Book deletion failed: ' . $e->getMessage());
            return response()->json([
                'message' => '書籍の削除に失敗しました',
                'success' => false
            ], 500);
        }
    }

    /**
     * 本の貸出履歴を取得（ページネーション対応）
     */
    public function history(Request $request, Book $book)
    {
        try {
            // ページネーション設定
            $perPage = $request->get('per_page', 10); // デフォルト10件
            
            // 貸出履歴を取得（returned_dateでソート、新しい順）
            $borrowHistory = $book->borrows()
                ->with('student')
                ->whereNotNull('returned_date')
                ->orderBy('returned_date', 'desc')
                ->paginate($perPage);

            // レスポンス用にデータを整形
            $formattedHistory = $borrowHistory->getCollection()->map(function ($borrow) {
                return [
                    'id' => $borrow->id,
                    'borrowed_date' => $borrow->borrowed_date->format('Y-m-d'),
                    'returned_date' => $borrow->returned_date ? $borrow->returned_date->format('Y-m-d') : null,
                    'student' => [
                        'id' => $borrow->student->id,
                        'name' => $borrow->student->name,
                        'grade' => $borrow->student->grade,
                        'class' => $borrow->student->class
                    ],
                    'duration' => $borrow->returned_date 
                        ? $borrow->borrowed_date->diffInDays($borrow->returned_date) . '日間'
                        : '貸出中'
                ];
            });

            return response()->json([
                'data' => $formattedHistory,
                'pagination' => [
                    'current_page' => $borrowHistory->currentPage(),
                    'last_page' => $borrowHistory->lastPage(),
                    'per_page' => $borrowHistory->perPage(),
                    'total' => $borrowHistory->total(),
                    'from' => $borrowHistory->firstItem(),
                    'to' => $borrowHistory->lastItem(),
                    'prev_page_url' => $borrowHistory->previousPageUrl(),
                    'next_page_url' => $borrowHistory->nextPageUrl(),
                ],
                'success' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Book history error: ' . $e->getMessage());
            return response()->json([
                'message' => '履歴の取得に失敗しました',
                'success' => false
            ], 500);
        }
    }

    /**
     * ISBNから書籍情報を検索（複数API対応）
     */
    public function searchByISBN(Request $request)
    {
        Log::info('BookController@searchByISBN called', ['isbn' => $request->input('isbn')]);

        $request->validate([
            'isbn' => 'required|string|min:10|max:17'
        ]);

        $isbn = preg_replace('/[-\s]/', '', $request->isbn);
        
        // 複数のAPIを順番に試行（NDL APIを最優先）
        $apis = [
            'ndl' => [$this, 'searchNDL'],
            'openBD' => [$this, 'searchOpenBD'],
            'googleBooks' => [$this, 'searchGoogleBooks']
        ];

        foreach ($apis as $apiName => $callable) {
            try {
                Log::info("Trying API: {$apiName}");
                $bookData = call_user_func($callable, $isbn);
                
                if ($bookData && !empty($bookData['title'])) {
                    Log::info("Success with API: {$apiName}", ['data' => $bookData]);
                    return response()->json([
                        'data' => $bookData,
                        'source' => $apiName,
                        'success' => true
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning("API {$apiName} failed: " . $e->getMessage());
                continue;
            }
        }

        return response()->json([
            'message' => 'ISBN ' . $isbn . ' の書籍情報が見つかりませんでした。',
            'success' => false
        ], 404);
    }

    /**
     * OpenBD APIで検索
     */
    private function searchOpenBD($isbn)
    {
        $client = new \GuzzleHttp\Client([
            'verify' => false,
            'timeout' => 10
        ]);

        $response = $client->get("https://api.openbd.jp/v1/get?isbn={$isbn}");
        $data = json_decode($response->getBody()->getContents(), true);

        if (empty($data) || empty($data[0])) {
            return null;
        }

        $book = $data[0];
        $bookData = [];

        // タイトル
        if (isset($book['onix']['DescriptiveDetail']['TitleDetail']['TitleElement']['TitleText']['content'])) {
            $bookData['title'] = $book['onix']['DescriptiveDetail']['TitleDetail']['TitleElement']['TitleText']['content'];
        }

        // タイトルのよみ
        if (isset($book['onix']['DescriptiveDetail']['TitleDetail']['TitleElement']['TitleText']['collationkey'])) {
            $bookData['title_transcription'] = $book['onix']['DescriptiveDetail']['TitleDetail']['TitleElement']['TitleText']['collationkey'];
        }

        // 著者
        if (isset($book['onix']['DescriptiveDetail']['Contributor'])) {
            $authors = [];
            foreach ($book['onix']['DescriptiveDetail']['Contributor'] as $contributor) {
                if (isset($contributor['PersonName']['content'])) {
                    $authors[] = $contributor['PersonName']['content'];
                }
            }
            if (!empty($authors)) {
                $bookData['author'] = implode(', ', $authors);
            }
        }

        // 出版社
        if (isset($book['onix']['PublishingDetail']['Imprint']['ImprintName'])) {
            $bookData['publisher'] = $book['onix']['PublishingDetail']['Imprint']['ImprintName'];
        }

        // 出版日
        if (isset($book['onix']['PublishingDetail']['PublishingDate'][0]['Date'])) {
            $pubDate = $book['onix']['PublishingDetail']['PublishingDate'][0]['Date'];
            if (strlen($pubDate) >= 6) {
                $year = substr($pubDate, 0, 4);
                $month = substr($pubDate, 4, 2);
                $day = strlen($pubDate) >= 8 ? substr($pubDate, 6, 2) : '01';
                $bookData['published_date'] = $year . '-' . $month . '-' . $day;
            }
        }

        // 価格
        if (isset($book['onix']['ProductSupply']['SupplyDetail']['Price'][0]['PriceAmount'])) {
            $bookData['price'] = (float)$book['onix']['ProductSupply']['SupplyDetail']['Price'][0]['PriceAmount'];
        }

        return $bookData;
    }

    /**
     * Google Books APIで検索
     */
    private function searchGoogleBooks($isbn)
    {
        $client = new \GuzzleHttp\Client([
            'verify' => false,
            'timeout' => 10
        ]);

        $response = $client->get("https://www.googleapis.com/books/v1/volumes?q=isbn:{$isbn}");
        $data = json_decode($response->getBody()->getContents(), true);

        if (empty($data['items'])) {
            return null;
        }

        $book = $data['items'][0]['volumeInfo'];
        $bookData = [];

        // タイトル
        if (isset($book['title'])) {
            $title = $book['title'];
            if (isset($book['subtitle'])) {
                $title .= ' : ' . $book['subtitle'];
            }
            $bookData['title'] = $title;
        }

        // 著者
        if (isset($book['authors'])) {
            $bookData['author'] = implode(', ', $book['authors']);
        }

        // 出版社
        if (isset($book['publisher'])) {
            $bookData['publisher'] = $book['publisher'];
        }

        // 出版日
        if (isset($book['publishedDate'])) {
            $pubDate = $book['publishedDate'];
            // YYYY-MM-DD形式に正規化
            if (preg_match('/(\d{4})-(\d{1,2})-(\d{1,2})/', $pubDate, $matches)) {
                $bookData['published_date'] = sprintf('%04d-%02d-%02d', $matches[1], $matches[2], $matches[3]);
            } elseif (preg_match('/(\d{4})-(\d{1,2})/', $pubDate, $matches)) {
                $bookData['published_date'] = sprintf('%04d-%02d-01', $matches[1], $matches[2]);
            } elseif (preg_match('/(\d{4})/', $pubDate, $matches)) {
                $bookData['published_date'] = sprintf('%04d-01-01', $matches[1]);
            }
        }

        // ページ数
        if (isset($book['pageCount'])) {
            $bookData['pages'] = (int)$book['pageCount'];
        }

        return $bookData;
    }

    /**
     * 国立国会図書館APIで検索
     */
    private function searchNDL($isbn)
    {
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
        
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);
        
        if ($xml === false) {
            throw new \Exception('XMLパースエラー');
        }
        
        if (!isset($xml->channel) || !isset($xml->channel->item) || count($xml->channel->item) == 0) {
            return null;
        }

        $item = $xml->channel->item[0];
        
        $item->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
        $item->registerXPathNamespace('dcterms', 'http://purl.org/dc/terms/');
        $item->registerXPathNamespace('dcndl', 'http://ndl.go.jp/dcndl/terms/');
        
        $bookData = [];

        // タイトル
        if (isset($item->title)) {
            $bookData['title'] = trim((string)$item->title);
        }

        // タイトルのよみ
        $dcndlElements = $item->children('http://ndl.go.jp/dcndl/terms/');
        if (isset($dcndlElements->titleTranscription)) {
            $bookData['title_transcription'] = trim((string)$dcndlElements->titleTranscription);
        }

        // 著者
        if (isset($item->author)) {
            $author = trim((string)$item->author);
            $author = preg_replace('/\s*(著|編|監修|訳|共著|編著)$/', '', $author);
            $bookData['author'] = $author;
        }

        // 出版社
        $dcElements = $item->children('http://purl.org/dc/elements/1.1/');
        if (isset($dcElements->publisher)) {
            $bookData['publisher'] = trim((string)$dcElements->publisher);
        }

        // 価格
        if (isset($dcndlElements->price)) {
            $priceText = trim((string)$dcndlElements->price);
            if (preg_match('/(\d+)/', $priceText, $matches)) {
                $bookData['price'] = (float)$matches[1];
            }
        }

        // ページ数
        $extentNodes = $item->xpath('.//dc:extent');
        if (!empty($extentNodes)) {
            foreach ($extentNodes as $extentNode) {
                $extentText = trim((string)$extentNode);
                if (preg_match('/(\d+)\s*[pページ頁]?/u', $extentText, $matches)) {
                    $bookData['pages'] = (int)$matches[1];
                    break;
                }
            }
        }

        // NDC分類
        $item->registerXPathNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $ndcTypes = ['NDC10', 'NDC9', 'NDC8'];
        $foundNdc = null;
        
        // XPathでNDC分類を検索
        foreach ($ndcTypes as $ndcType) {
            $ndcNodes = $item->xpath("//dc:subject[@xsi:type='dcndl:{$ndcType}']");
            if (!empty($ndcNodes)) {
                $foundNdc = trim((string)$ndcNodes[0]);
                break;
            }
        }
        
        // 代替方法でNDC分類を検索
        if (!$foundNdc && isset($dcElements->subject)) {
            foreach ($dcElements->subject as $subject) {
                $attributes = $subject->attributes('http://www.w3.org/2001/XMLSchema-instance');
                if (isset($attributes->type)) {
                    $typeValue = (string)$attributes->type;
                    if (preg_match('/dcndl:(NDC\d+)/', $typeValue)) {
                        $foundNdc = trim((string)$subject);
                        break;
                    }
                }
            }
        }
        
        if ($foundNdc) {
            $bookData['ndc'] = $foundNdc;
        }

        return $bookData;
    }

    /**
     * 受け入れ元の候補を取得する
     */
    public function getAcceptanceSources(Request $request)
    {
        try {
            // データベースから既存の受け入れ元を取得（ユニーク）
            $sources = Book::whereNotNull('acceptance_source')
                ->where('acceptance_source', '!=', '')
                ->distinct()
                ->orderBy('acceptance_source')
                ->pluck('acceptance_source')
                ->toArray();

            // デフォルトの候補
            $defaultSources = [
                'Amazon',
                'TSUTAYA',
                '楽天ブックス',
                '紀伊國屋書店',
                '丸善ジュンク堂書店',
                '文栄堂',
                '寄贈',
                '図書館間相互貸借',
                '学校間交換',
                '保護者寄付',
                'その他'
            ];

            // 既存データとデフォルト候補をマージし、重複を除去
            $allSources = array_unique(array_merge($defaultSources, $sources));
            sort($allSources);

            return response()->json([
                'sources' => array_values($allSources),
                'count' => count($allSources)
            ]);

        } catch (\Exception $e) {
            Log::error('受け入れ元候補取得エラー: ' . $e->getMessage());
            
            return response()->json([
                'error' => '受け入れ元候補の取得に失敗しました',
                'sources' => []
            ], 500);
        }
    }

    public function exportPdf(Request $request)
    {
        try {
            $query = Book::query();

            // NDC分類フィルター（改良版）
            $ndcFilter = null;
            if ($request->filled('ndc_category')) {
                $ndcFilter = $request->ndc_category;
                $this->applyNdcFilter($query, $ndcFilter);
                Log::info('PDF NDC Filter Applied', ['filter' => $ndcFilter]);
            } elseif ($request->filled('ndc')) {
                $ndcFilter = $request->ndc;
                $this->applyNdcFilter($query, $ndcFilter);
                Log::info('PDF NDC Filter Applied', ['filter' => $ndcFilter]);
            }

            // 受入日範囲フィルター
            if ($request->filled('start_date') || $request->filled('end_date')) {
                $query->whereNotNull('acceptance_date');
                
                if ($request->filled('start_date')) {
                    $query->where('acceptance_date', '>=', $request->start_date);
                }
                if ($request->filled('end_date')) {
                    $query->where('acceptance_date', '<=', $request->end_date);
                }
            }

            // タイトルフィルター
            if ($request->filled('search_title')) {
                $searchTitle = $request->search_title;
                $query->where(function($q) use ($searchTitle) {
                    $q->where('title', 'like', "%{$searchTitle}%")
                      ->orWhere('title_transcription', 'like', "%{$searchTitle}%");
                });
                Log::info('PDF Title Filter Applied', ['filter' => $searchTitle]);
            }

            // 著者フィルター
            if ($request->filled('search_author')) {
                $searchAuthor = $request->search_author;
                $query->where('author', 'like', "%{$searchAuthor}%");
                Log::info('PDF Author Filter Applied', ['filter' => $searchAuthor]);
            }

            // ISBNコード有無フィルター
            if ($request->filled('isbn_type')) {
                $isbnType = $request->isbn_type;
                if ($isbnType === 'with_isbn') {
                    // ISBNコードあり：カスタムJANコード（938525で始まる）以外
                    $query->where(function($q) {
                        $q->whereNotNull('isbn')
                          ->where('isbn', '!=', '')
                          ->where('isbn', 'not like', '938525%');
                    });
                } elseif ($isbnType === 'without_isbn') {
                    // ISBNコードなし：カスタムJANコード（938525で始まる）または空
                    // JANコード情報も一緒に取得
                    $query->with('janCode');
                    $query->where(function($q) {
                        $q->whereNull('isbn')
                          ->orWhere('isbn', '=', '')
                          ->orWhere('isbn', 'like', '938525%');
                    });
                }
                Log::info('PDF ISBN Filter Applied', ['filter' => $isbnType]);
            }

            // 受入年月日の早い順（古い順）でソート
            $books = $query->orderBy('acceptance_date', 'asc')->get();
            
            // 統計情報の計算
            $filteredCount = $books->count();
            $totalCount = Book::count();
            
            Log::info('PDF Export Statistics', [
                'filtered_count' => $filteredCount,
                'total_count' => $totalCount,
                'ndc_filter' => $ndcFilter,
                'date_range' => [
                    'start' => $request->start_date,
                    'end' => $request->end_date
                ]
            ]);

            // TCPDFのセットアップ
            $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

            $pdf->SetCreator('蔵書管理システム');
            $pdf->SetAuthor('蔵書管理システム');
            $pdf->SetTitle('書籍一覧');
            $pdf->SetSubject('書籍一覧PDF');

            // フォントの設定
            $fontname = $this->setPdfFont($pdf);

            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(10, 10, 10);
            $pdf->SetAutoPageBreak(true, 10);

            $pdf->AddPage();

            // タイトル
            $this->setFontSize($pdf, $fontname, 'B', 16);
            $pdf->Cell(0, 10, '書籍一覧', 0, 1, 'C');
            $pdf->Ln(5);

            // 出力日時とフィルター条件の表示
            $this->addPdfHeader($pdf, $request, $fontname, $filteredCount, $totalCount);

            // テーブルヘッダー
            $this->addTableHeaders($pdf, $fontname);

            // データ行
            $this->addTableData($pdf, $books, $fontname);

            // ISBNコードなしでフィルタした場合、バーコードも出力
            if ($request->filled('isbn_type') && $request->isbn_type === 'without_isbn') {
                $this->addBarcodesSection($pdf, $books, $fontname);
            }

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
     * フォントサイズを設定するヘルパーメソッド
     */
    private function setFontSize($pdf, $fontname, $style = '', $size = 9)
    {
        try {
            $pdf->SetFont($fontname, $style, $size);
        } catch (\Exception $e) {
            // フォールバック
            $pdf->SetFont('dejavusans', $style, $size);
        }
    }

    /**
     * PDFのフォントを設定する
     */
    private function setPdfFont($pdf)
    {
        try {
            // 日本語フォントファイルのパス
            $fontPath = public_path('seieiIPAexMincho.ttf');
            
            if (file_exists($fontPath)) {
                // カスタム日本語フォントを使用
                $fontname = \TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 96);
                if ($fontname) {
                    $pdf->SetFont($fontname, '', 9);
                    Log::info('Japanese font loaded successfully', ['fontname' => $fontname]);
                    return $fontname;
                }
            }
            
            // フォールバック: CID日本語フォント
            try {
                $pdf->SetFont('cid0jp', '', 9);
                Log::info('CID Japanese font loaded');
                return 'cid0jp';
            } catch (\Exception $e) {
                // 最終フォールバック: UTF-8対応フォント
                $pdf->SetFont('dejavusans', '', 9);
                Log::info('DejaVu Sans font loaded as fallback');
                return 'dejavusans';
            }
        } catch (\Exception $e) {
            Log::error('Font setting failed: ' . $e->getMessage());
            // 緊急フォールバック
            $pdf->SetFont('helvetica', '', 9);
            Log::info('Helvetica font loaded as emergency fallback');
            return 'helvetica';
        }
    }

    /**
     * PDFヘッダー情報を追加する
     */
    private function addPdfHeader($pdf, $request, $fontname, $filteredCount = null, $totalCount = null)
    {
        $this->setFontSize($pdf, $fontname, '', 10);
        
        // 出力日時
        $pdf->Cell(0, 8, '出力日時: ' . date('Y年m月d日 H:i'), 0, 1, 'R');
        
        // フィルター条件の詳細表示
        $filterText = '';
        
        // NDC分類フィルターの表示
        $ndcFilter = null;
        if ($request->filled('ndc_category')) {
            $ndcFilter = $request->ndc_category;
        } elseif ($request->filled('ndc')) {
            $ndcFilter = $request->ndc;
        }
        
        if ($ndcFilter) {
            $ndcMap = [
                '0' => '総記', '1' => '哲学', '2' => '歴史', '3' => '社会科学', '4' => '自然科学',
                '5' => '技術・工学', '6' => '産業', '7' => '芸術・美術', '8' => '言語', '9' => '文学'
            ];
            
            $detailedNdcMap = [
                '00' => '総記', '01' => '知識・学問・学術', '02' => '図書・書誌学',
                '10' => '哲学各論', '11' => '形而上学', '12' => '認識論・論理学',
                '20' => '歴史', '21' => 'アジア史', '22' => 'ヨーロッパ史',
                '30' => '社会科学', '31' => '政治学', '32' => '法律',
                '40' => '自然科学', '41' => '数学', '42' => '物理学',
                '50' => '技術・工学', '51' => '建設工学', '52' => '建築学',
                '60' => '産業', '61' => '農業', '62' => '園芸',
                '70' => '芸術・美術', '71' => '彫刻', '72' => '絵画',
                '80' => '言語', '81' => '日本語', '82' => '中国語',
                '90' => '文学', '91' => '日本文学', '92' => '中国文学'
            ];
            
            $ndcDescription = '';
            if (strlen($ndcFilter) === 1) {
                $ndcDescription = $ndcMap[$ndcFilter] ?? '';
                $filterText .= 'NDC分類: ' . $ndcFilter . '** (' . $ndcDescription . ')　';
            } elseif (strlen($ndcFilter) === 2) {
                $ndcDescription = $detailedNdcMap[$ndcFilter] ?? $ndcMap[substr($ndcFilter, 0, 1)] ?? '';
                $filterText .= 'NDC分類: ' . $ndcFilter . '* (' . $ndcDescription . ')　';
            } elseif (strlen($ndcFilter) === 3) {
                if (str_ends_with($ndcFilter, '00')) {
                    $baseCategory = substr($ndcFilter, 0, 1);
                    $ndcDescription = $ndcMap[$baseCategory] ?? '';
                    $filterText .= 'NDC分類: ' . $ndcFilter . '-' . $baseCategory . '99 (' . $ndcDescription . ')　';
                } else {
                    $ndcDescription = $detailedNdcMap[substr($ndcFilter, 0, 2)] ?? $ndcMap[substr($ndcFilter, 0, 1)] ?? '';
                    $filterText .= 'NDC分類: ' . $ndcFilter . '* (' . $ndcDescription . ')　';
                }
            } else {
                $ndcDescription = $ndcMap[substr($ndcFilter, 0, 1)] ?? '';
                $filterText .= 'NDC分類: ' . $ndcFilter . ' (' . $ndcDescription . ')　';
            }
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
            $filterText .= '　';
        }
        
        if ($request->filled('search_title')) {
            $filterText .= 'タイトル: ' . $request->search_title . '　';
        }
        
        if ($request->filled('search_author')) {
            $filterText .= '著者: ' . $request->search_author . '　';
        }
        
        if ($filterText) {
            $pdf->Cell(0, 8, 'フィルター条件: ' . $filterText, 0, 1, 'L');
        }
        
        // 件数情報の表示
        if ($filteredCount !== null && $totalCount !== null) {
            $statsText = '対象書籍数: ' . number_format($filteredCount) . '冊';
            if ($filteredCount < $totalCount) {
                $statsText .= ' （全体: ' . number_format($totalCount) . '冊）';
            }
            $pdf->Cell(0, 8, $statsText, 0, 1, 'L');
        }
        
        $pdf->Ln(3);
    }

    /**
     * テーブルヘッダーを追加する
     */
    private function addTableHeaders($pdf, $fontname)
    {
        $this->setFontSize($pdf, $fontname, 'B', 8);
        $pdf->SetFillColor(240, 240, 240);
        
        $headers = [
            '受入年月日' => 25, 'タイトル' => 45, '巻数' => 12, '著者' => 33, '出版社' => 28, '出版日' => 20,
            'ページ数' => 15, '受入種別' => 20, '受入元' => 22, '価格' => 15, '図書分類' => 20
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
    private function addTableData($pdf, $books, $fontname)
    {
        $this->setFontSize($pdf, $fontname, '', 7);
        $pdf->SetFillColor(255, 255, 255);
        
        // ヘッダー情報を取得（重複描画なし）
        $headers = [
            '受入年月日' => 25, 'タイトル' => 45, '巻数' => 12, '著者' => 33, '出版社' => 28, '出版日' => 20,
            'ページ数' => 15, '受入種別' => 20, '受入元' => 22, '価格' => 15, '図書分類' => 20
        ];
        $colWidths = array_values($headers);

        foreach ($books as $book) {
            // 複数行が必要な列の高さを事前計算
            $tempRowData = [
                '', // 受入年月日
                $this->sanitizeText($book->title ?: ''),
                '', // 巻数
                $this->sanitizeText($book->author ?: ''),
                $this->sanitizeText($book->publisher ?: ''),
                '', '', '', '', '', '' // その他の列
            ];
            
            $longTextColumns = [1, 3, 4]; // タイトル、著者、出版社
            $maxLines = 1;
            
            foreach ($longTextColumns as $colIndex) {
                $text = $tempRowData[$colIndex];
                if (!empty($text)) {
                    $width = $colWidths[$colIndex];
                    $lines = $this->calculateTextLines($pdf, $text, $width);
                    $maxLines = max($maxLines, $lines);
                }
            }
            
            $maxLines = min($maxLines, 3);
            $estimatedCellHeight = $maxLines * 6;
            
            // 改ページの判定（予想される行の高さを考慮）
            if ($pdf->GetY() + $estimatedCellHeight > 180) { // A4横向きの場合
                $pdf->AddPage();
                $this->addTableHeaders($pdf, $fontname);
                $this->setFontSize($pdf, $fontname, '', 7);
            }

            // データの準備
            $classification = $this->getBookClassification($book);
            
            $rowData = [
                $book->acceptance_date ? $book->acceptance_date->format('Y/m/d') : '',
                $this->sanitizeText($book->title ?: ''),
                $this->sanitizeText($book->volume_number ?: ''),
                $this->sanitizeText($book->author ?: ''),
                $this->sanitizeText($book->publisher ?: ''),
                $book->published_date ? $book->published_date->format('Y/m/d') : '',
                $book->pages ?: '',
                $this->sanitizeText($book->acceptance_type ?: ''),
                $this->sanitizeText($book->acceptance_source ?: ''),
                $book->price ? number_format($book->price) . '円' : '',
                $classification
            ];

            // 複数行が必要な列（タイトル、著者、出版社）の高さを計算
            $longTextColumns = [1, 3, 4]; // タイトル、著者、出版社のインデックス
            $maxLines = 1;
            
            foreach ($longTextColumns as $colIndex) {
                $text = $rowData[$colIndex];
                if (!empty($text)) {
                    $width = $colWidths[$colIndex];
                    $lines = $this->calculateTextLines($pdf, $text, $width);
                    $maxLines = max($maxLines, $lines);
                }
            }
            
            // 最大行数を3行に制限
            $maxLines = min($maxLines, 3);
            $cellHeight = $maxLines * 6; // 各行6mmの高さ

            // 行の出力（複数行対応）
            $this->renderTableRow($pdf, $rowData, $colWidths, $cellHeight, $fontname, $longTextColumns);
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
     * NDC分類フィルターを適用する共通メソッド
     */
    private function applyNdcFilter($query, $ndcFilter)
    {
        if (!$ndcFilter) return;
        
        // NDC分類の詳細なフィルタリング
        if (strlen($ndcFilter) === 1) {
            // "0", "1", "4" などの場合 - その大分類全体（000-099, 100-199, 400-499など）
            $query->where(function($q) use ($ndcFilter) {
                $q->where('ndc', 'like', $ndcFilter . '%')
                  ->orWhere('ndc', 'like', '0' . $ndcFilter . '%');
            });
        } elseif (strlen($ndcFilter) === 2) {
            // "00", "10", "40" などの場合 - その中分類（000-009, 100-109, 400-409など）
            $query->where('ndc', 'like', $ndcFilter . '%');
        } elseif (strlen($ndcFilter) === 3) {
            // "000", "100", "400" などの場合 - その小分類または前方一致
            if (str_ends_with($ndcFilter, '00')) {
                // "000", "100", "400" の場合はその十分類（000-099, 100-199, 400-499）
                $baseCategory = substr($ndcFilter, 0, 1);
                $query->where(function($q) use ($baseCategory) {
                    $q->where('ndc', 'like', $baseCategory . '%')
                      ->orWhere('ndc', 'like', '0' . $baseCategory . '%');
                });
            } else {
                // "410", "420" などの場合は前方一致（410-419, 420-429など）
                $query->where('ndc', 'like', $ndcFilter . '%');
            }
        } else {
            // より詳細なNDC分類の場合は完全前方一致
            $query->where('ndc', 'like', $ndcFilter . '%');
        }
    }

    /**
     * テキストの必要行数を計算する
     */
    private function calculateTextLines($pdf, $text, $width)
    {
        if (empty($text)) return 1;
        
        $words = $this->splitTextForPdf($text, $pdf, $width - 4); // パディング4mm考慮
        return max(1, count($words));
    }
    
    /**
     * PDFに適した形でテキストを分割する
     */
    private function splitTextForPdf($text, $pdf, $maxWidth)
    {
        $lines = [];
        $currentLine = '';
        $chars = mb_str_split($text, 1, 'UTF-8');
        
        foreach ($chars as $char) {
            $testLine = $currentLine . $char;
            $testWidth = $pdf->GetStringWidth($testLine);
            
            if ($testWidth > $maxWidth && !empty($currentLine)) {
                $lines[] = $currentLine;
                $currentLine = $char;
            } else {
                $currentLine = $testLine;
            }
        }
        
        if (!empty($currentLine)) {
            $lines[] = $currentLine;
        }
        
        return empty($lines) ? [''] : $lines;
    }
    
    /**
     * テーブル行をレンダリングする（複数行対応）
     */
    private function renderTableRow($pdf, $rowData, $colWidths, $cellHeight, $fontname, $longTextColumns)
    {
        $startY = $pdf->GetY();
        $startX = $pdf->GetX();
        $currentX = $startX;
        
        // セルの枠線を先に描画
        foreach ($colWidths as $width) {
            $pdf->Rect($currentX, $startY, $width, $cellHeight);
            $currentX += $width;
        }
        
        // テキストを描画
        $currentX = $startX;
        foreach ($rowData as $colIndex => $data) {
            $width = $colWidths[$colIndex];
            
            if (in_array($colIndex, $longTextColumns) && !empty($data)) {
                // 複数行テキストの処理
                $lines = $this->splitTextForPdf($data, $pdf, $width - 4);
                $lines = array_slice($lines, 0, 3); // 最大3行に制限
                
                foreach ($lines as $lineIndex => $line) {
                    $lineY = $startY + 2 + ($lineIndex * 6); // 上マージン2mm + 各行6mm
                    $pdf->SetXY($currentX + 2, $lineY);
                    $pdf->Cell($width - 4, 5, $line, 0, 0, 'L', false);
                }
            } else {
                // 単行テキストの処理（中央揃え）
                $textY = $startY + ($cellHeight / 2) - 2;
                $pdf->SetXY($currentX, $textY);
                $pdf->Cell($width, 4, $data, 0, 0, 'C', false);
            }
            
            $currentX += $width;
        }
        
        // 次の行の位置を設定
        $pdf->SetXY($startX, $startY + $cellHeight);
    }

    /**
     * テキストをサニタイズする（日本語対応強化）
     */
    private function sanitizeText($text)
    {
        if (!$text) return '';
        
        // UTF-8エンコーディングの確認と変換
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }
        
        // 制御文字の除去（ただし改行とタブは保持）
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // 特殊な引用符や省略記号を標準的な文字に置換
        $replacements = [
            '"' => '"',
            '"' => '"', 
            '…' => '...'
        ];
        $text = str_replace(array_keys($replacements), array_values($replacements), $text);
        
        // PDFでの複数行表示を考慮して長いテキストは切り詰めない
        // 代わりに改行処理で対応する
        
        return $text;
    }

    /**
     * JANコードで書籍情報を検索
     */
    public function searchByJanCode(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'jan_code' => 'required|string|size:13'
        ]);

        $janCode = $request->jan_code;

        try {
            // JANコードで登録された書籍を検索
            $janCodeRecord = \App\Models\JanCode::where('jan_code', $janCode)
                ->where('is_used', true)
                ->with('book')
                ->first();

            if ($janCodeRecord && $janCodeRecord->book) {
                return response()->json([
                    'success' => true,
                    'data' => new BookResource($janCodeRecord->book),
                    'source' => 'jan_database'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'JANコード ' . $janCode . ' に対応する書籍が見つかりませんでした。'
            ], 404);

        } catch (\Exception $e) {
            Log::error('JANコード検索エラー: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'JANコード検索中にエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * ISBNコードのない書籍のバーコードセクションを追加
     */
    private function addBarcodesSection($pdf, $books, $fontname)
    {
        // JANコードを持つ書籍のみをフィルタ
        $booksWithJanCode = $books->filter(function ($book) {
            return $book->janCode && !empty($book->janCode->jan_code);
        });

        if ($booksWithJanCode->count() === 0) {
            return;
        }

        // 新しいページを開始
        $pdf->AddPage();

        // バーコードセクションのタイトル
        $this->setFontSize($pdf, $fontname, 'B', 16);
        $pdf->Cell(0, 10, 'JANコード バーコード一覧', 0, 1, 'C');
        $pdf->Ln(10);

        $currentY = $pdf->GetY();
        $pageHeight = 180; // A4横向きの場合の有効高さ
        $barcodeHeight = 70; // 1つのバーコードに必要な高さ
        $barcodesPerRow = 3; // 1行に表示するバーコード数
        $barcodeWidth = 90; // 1つのバーコードの幅
        $startX = 10; // 左マージン

        $count = 0;
        foreach ($booksWithJanCode as $book) {
            $janCode = $book->janCode->jan_code;
            
            // 改ページ判定
            if ($currentY + $barcodeHeight > $pageHeight) {
                $pdf->AddPage();
                $currentY = $pdf->GetY();
                $count = 0;
            }

            // バーコードの位置計算
            $colIndex = $count % $barcodesPerRow;
            $x = $startX + ($colIndex * $barcodeWidth);
            
            if ($colIndex === 0) {
                $y = $currentY;
            } else {
                $y = $currentY;
            }

            // バーコード描画
            $this->drawSingleBarcode($pdf, $x, $y, $janCode, $book, $fontname);

            $count++;
            
            // 行が完了したら次の行へ移動
            if ($colIndex === $barcodesPerRow - 1) {
                $currentY += $barcodeHeight;
            }
        }
    }

    /**
     * 個別のバーコードを描画
     */
    private function drawSingleBarcode($pdf, $x, $y, $janCode, $book, $fontname)
    {
        // 現在位置を保存
        $originalX = $pdf->GetX();
        $originalY = $pdf->GetY();

        // 指定位置に移動
        $pdf->SetXY($x, $y);

        // 書籍タイトル（短縮版）
        $this->setFontSize($pdf, $fontname, 'B', 9);
        $title = $this->truncateText($book->title, 30);
        $pdf->Cell(80, 6, $title, 0, 1, 'C');
        
        // 著者（存在する場合）
        if ($book->author) {
            $pdf->SetX($x);
            $this->setFontSize($pdf, $fontname, '', 8);
            $author = $this->truncateText($book->author, 25);
            $pdf->Cell(80, 5, $author, 0, 1, 'C');
        }

        // JANコード表示
        $pdf->SetX($x);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(80, 6, 'JAN: ' . $janCode, 0, 1, 'C');

        // バーコード描画
        $barcodeY = $pdf->GetY();
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
            'fontsize' => 7,
            'stretchtext' => 2
        );

        // EAN-13バーコードを描画
        $pdf->write1DBarcode($janCode, 'EAN13', $x + 5, $barcodeY, 70, 25, 0.4, $style, 'N');

        // 元の位置に戻す
        $pdf->SetXY($originalX, $originalY);
    }

    /**
     * テキストを指定文字数で切り詰める
     */
    private function truncateText($text, $maxLength)
    {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }
        return mb_substr($text, 0, $maxLength - 2) . '..';
    }

    /**
     * リクエストされた本と一致するかチェックして通知を作成
     */
    private function checkAndNotifyBookRequests(Book $book): void
    {
        try {
            Log::info("通知チェック開始: Book ID {$book->id}, Title: {$book->title}");
            
            // pending状態のリクエストを取得
            $bookRequests = \App\Models\BookRequest::where('status', 'pending')
                ->get();

            Log::info("Pendingリクエスト数: " . $bookRequests->count());

            foreach ($bookRequests as $request) {
                Log::info("リクエストチェック: ID {$request->id}, Title: {$request->title}, Student ID: {$request->student_id}");
                
                // タイトルのマッチングをゆるくする
                // リクエストタイトルの一部が書籍タイトルに含まれているか
                // または書籍タイトルの一部がリクエストタイトルに含まれているか
                $titleMatch = false;
                
                // 基本的な部分一致チェック
                if (stripos($book->title, $request->title) !== false || 
                    stripos($request->title, $book->title) !== false) {
                    $titleMatch = true;
                } else {
                    // スペースや記号で分割してキーワード単位でチェック
                    $requestKeywords = preg_split('/[\s　、。・]+/u', $request->title, -1, PREG_SPLIT_NO_EMPTY);
                    $bookKeywords = preg_split('/[\s　、。・]+/u', $book->title, -1, PREG_SPLIT_NO_EMPTY);
                    
                    // リクエストのキーワードのいずれかが書籍タイトルに含まれているか
                    foreach ($requestKeywords as $keyword) {
                        if (mb_strlen($keyword) >= 2) { // 2文字以上のキーワードのみ
                            if (stripos($book->title, $keyword) !== false) {
                                $titleMatch = true;
                                Log::info("キーワードマッチ: '{$keyword}' が書籍タイトルに含まれています");
                                break;
                            }
                        }
                    }
                    
                    // 書籍のキーワードのいずれかがリクエストタイトルに含まれているか
                    if (!$titleMatch) {
                        foreach ($bookKeywords as $keyword) {
                            if (mb_strlen($keyword) >= 2) {
                                if (stripos($request->title, $keyword) !== false) {
                                    $titleMatch = true;
                                    Log::info("キーワードマッチ: '{$keyword}' がリクエストタイトルに含まれています");
                                    break;
                                }
                            }
                        }
                    }
                }
                
                $authorMatch = false;
                if ($request->author && $book->author) {
                    $authorMatch = stripos($book->author, $request->author) !== false || 
                                   stripos($request->author, $book->author) !== false;
                }

                Log::info("マッチング結果: titleMatch={$titleMatch}, authorMatch={$authorMatch}");

                // タイトルが一致、または（タイトルと著者の両方が一致）
                if ($titleMatch && ($authorMatch || !$request->author)) {
                    $message = "リクエストされた本「{$book->title}」が登録されました。";
                    
                    // 通知を作成（リクエストした人のstudent_idを含める）
                    $notification = \App\Models\Notification::create([
                        'student_id' => $request->student_id,
                        'book_request_id' => $request->id,
                        'book_id' => $book->id,
                        'message' => $message,
                        'is_read' => false
                    ]);

                    Log::info("通知を作成しました", [
                        'notification_id' => $notification->id,
                        'message' => $notification->message,
                        'book_request_id' => $request->id,
                        'book_id' => $book->id,
                        'student_id' => $request->student_id
                    ]);
                }
            }
        } catch (\Exception $e) {
            // 通知作成のエラーは本の登録自体には影響させない
            Log::error('通知作成エラー: ' . $e->getMessage());
            Log::error('スタックトレース: ' . $e->getTraceAsString());
        }
    }
}
