<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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

// 個別ルートを先に定義
Route::post('books/search-isbn', [BookController::class, 'searchByISBN']);
Route::get('books/search-by-isbn', [BookController::class, 'searchByISBN']);
Route::get('books/search-by-jan', [BookController::class, 'searchByJanCode']);
Route::get('books/pdf', [BookController::class, 'exportPdf']);
Route::get('books/acceptance-sources', [BookController::class, 'getAcceptanceSources']);

// リソースルートを後に定義
Route::apiResource('books', BookController::class);
Route::get('books/{book}/history', [BookController::class, 'history']);

// 貸し借り管理のルート
Route::apiResource('borrows', BorrowController::class);
Route::post('borrows/batch', [BorrowController::class, 'batchStore']);
Route::post('borrows/batch-return', [BorrowController::class, 'batchReturn']);
Route::patch('borrows/{borrow}/return', [BorrowController::class, 'returnBook']);
Route::get('borrows/recent', [BorrowController::class, 'recent']);
Route::get('books/available', [BookController::class, 'available']);

// JANコード生成のルート
Route::post('generate-jan-code', [App\Http\Controllers\Api\JanCodeController::class, 'generateJanCode']);
Route::post('generate-barcode-pdf', [App\Http\Controllers\Api\JanCodeController::class, 'generateBarcodePdf']);

// 生徒管理のルート
Route::get('students/classes', [StudentController::class, 'classes']);
Route::apiResource('students', StudentController::class);
Route::get('students/{student}/borrows', [StudentController::class, 'getBorrows']);

// 本のリクエスト機能のルート
Route::get('book-requests', [BookRequestController::class, 'index']);
Route::post('book-requests', [BookRequestController::class, 'store']);
Route::patch('book-requests/{bookRequest}/status', [BookRequestController::class, 'updateStatus']);
Route::delete('book-requests/{bookRequest}', [BookRequestController::class, 'destroy']);

// 認証関連のルート
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth');
Route::post('setup-password', [AuthController::class, 'setupPassword']);
Route::post('change-password', [AuthController::class, 'changePassword'])->middleware('auth');
Route::get('me', [AuthController::class, 'me'])->middleware('auth');

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

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
