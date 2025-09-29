<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\BorrowController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\Api\BookRequestController;

// 個別ルートを先に定義
Route::post('books/search-isbn', [BookController::class, 'searchByISBN']);
Route::get('books/search-by-isbn', [BookController::class, 'searchByISBN']);
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

// 生徒管理のルート
Route::apiResource('students', StudentController::class);
Route::get('students/{student}/borrows', [StudentController::class, 'getBorrows']);

// 本のリクエスト機能のルート
Route::get('book-requests', [BookRequestController::class, 'index']);
Route::post('book-requests', [BookRequestController::class, 'store']);
Route::patch('book-requests/{bookRequest}/status', [BookRequestController::class, 'updateStatus']);
Route::delete('book-requests/{bookRequest}', [BookRequestController::class, 'destroy']);

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
