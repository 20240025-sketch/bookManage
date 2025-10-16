<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\BorrowController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\Api\BookRequestController;
use App\Http\Controllers\Api\AuthController;

// CORS対応のためのOPTIONSリクエスト処理
Route::options('{any}', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
})->where('any', '.*');





// 認証関連のルート（認証不要）
Route::post('login', [AuthController::class, 'login']);

// テスト用エンドポイント（認証不要）
Route::get('test/books', function() {
    $books = \App\Models\Book::limit(5)->get();
    return response()->json([
        'success' => true,
        'books' => $books,
        'message' => '認証なしでのブック取得テスト'
    ]);
});

Route::get('test/students', function() {
    $students = \App\Models\Student::limit(5)->get();
    return response()->json([
        'success' => true,
        'students' => $students,
        'message' => '認証なしでの生徒取得テスト'
    ]);
});

// デバッグ用エンドポイント
Route::get('debug/session', function() {
    return response()->json([
        'session_id' => session()->getId(),
        'auth_check' => Auth::check(),
        'user' => Auth::user() ? [
            'id' => Auth::user()->id,
            'name' => Auth::user()->name,
            'email' => Auth::user()->email
        ] : null,
        'session_data' => session()->all()
    ]);
})->middleware('web');

// セッション認証が必要なルート
Route::middleware('web')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('setup-password', [AuthController::class, 'setupPassword']);
    Route::post('change-password', [AuthController::class, 'changePassword']);
    Route::get('me', [AuthController::class, 'me']);
    
    // 書籍管理のルート
    Route::get('books', [BookController::class, 'index']);
    Route::post('books', [BookController::class, 'store']);
    Route::get('books/{book}', [BookController::class, 'show']);
    Route::put('books/{book}', [BookController::class, 'update']);
    Route::delete('books/{book}', [BookController::class, 'destroy']);
    Route::get('books/pdf', [BookController::class, 'exportPdf']);
    Route::get('books/available', [BookController::class, 'available']);
    Route::get('books/{book}/history', [BookController::class, 'history']);
    Route::post('books/search-isbn', [BookController::class, 'searchByISBN']);
    Route::get('books/search-by-isbn', [BookController::class, 'searchByISBN']);
    Route::get('books/search-by-jan', [BookController::class, 'searchByJanCode']);
    Route::get('books/acceptance-sources', [BookController::class, 'getAcceptanceSources']);
    
    // 生徒管理のルート
    Route::get('students', [StudentController::class, 'index']);
    Route::post('students', [StudentController::class, 'store']);
    Route::get('students/{student}', [StudentController::class, 'show']);
    Route::put('students/{student}', [StudentController::class, 'update']);
    Route::delete('students/{student}', [StudentController::class, 'destroy']);
    Route::get('students/classes', [StudentController::class, 'classes']);
    Route::get('students/{student}/borrows', [StudentController::class, 'getBorrows']);
    
    // 貸し借り管理のルート
    Route::get('borrows', [BorrowController::class, 'index']);
    Route::post('borrows', [BorrowController::class, 'store']);
    Route::get('borrows/{borrow}', [BorrowController::class, 'show']);
    Route::put('borrows/{borrow}', [BorrowController::class, 'update']);
    Route::delete('borrows/{borrow}', [BorrowController::class, 'destroy']);
    Route::post('borrows/batch', [BorrowController::class, 'batchStore']);
    Route::post('borrows/batch-return', [BorrowController::class, 'batchReturn']);
    Route::patch('borrows/{borrow}/return', [BorrowController::class, 'returnBook']);
    Route::get('borrows/recent', [BorrowController::class, 'recent']);
    
    // 本のリクエスト機能のルート
    Route::get('book-requests', [BookRequestController::class, 'index']);
    Route::post('book-requests', [BookRequestController::class, 'store']);
    Route::patch('book-requests/{bookRequest}/status', [BookRequestController::class, 'updateStatus']);
    Route::delete('book-requests/{bookRequest}', [BookRequestController::class, 'destroy']);
    
    // JANコード生成のルート
    Route::post('generate-jan-code', [App\Http\Controllers\Api\JanCodeController::class, 'generateJanCode']);
    Route::post('generate-barcode-pdf', [App\Http\Controllers\Api\JanCodeController::class, 'generateBarcodePdf']);
    
    // 貸出状況のルート（管理者のみ）
    Route::get('borrow-status', [App\Http\Controllers\BorrowStatusController::class, 'index']);
    Route::get('borrow-status/pdf', [App\Http\Controllers\BorrowStatusController::class, 'exportPdf']);
    
    // 利用状況のルート（管理者のみ）
    Route::get('usage-statistics', [App\Http\Controllers\UsageStatisticsController::class, 'index']);
    Route::get('usage-statistics/chart', [App\Http\Controllers\UsageStatisticsController::class, 'chartData']);
    Route::post('usage-statistics/pdf', [App\Http\Controllers\UsageStatisticsController::class, 'exportPdf']);
    
    // 図書当番のルート（管理者のみ）
    Route::get('library-duty', [App\Http\Controllers\LibraryDutyController::class, 'index']);
    Route::get('library-duty/today', [App\Http\Controllers\LibraryDutyController::class, 'today']);
    Route::put('library-duty/{id}', [App\Http\Controllers\LibraryDutyController::class, 'update']);
    Route::get('library-duty/pdf', [App\Http\Controllers\LibraryDutyController::class, 'exportPdf']);
});

// テスト用エンドポイント
Route::get('test-ndl/{isbn}', function($isbn) {
    try {
        $client = new \GuzzleHttp\Client([
            'verify' => false, // SSL証明書の検証を無効化
            'timeout' => 10
        ]);
        
        $response = $client->get('https://ndlsearch.ndl.go.jp/api/opensearch', [
            'query' => [
                'isbn' => $isbn,
                'format' => 'rss'
            ]
        ]);
        
        $xmlContent = $response->getBody()->getContents();
        
        return response()->json([
            'raw_xml' => $xmlContent,
            'parsed' => simplexml_load_string($xmlContent)
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
});

// セッション認証チェック用のミドルウェア
Route::get('/user', function (Request $request) {
    $student = session('student');
    if (!$student) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    return response()->json($student);
});
