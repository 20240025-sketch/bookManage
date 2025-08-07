<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;

// 個別ルートを先に定義
Route::post('books/search-isbn', [BookController::class, 'searchByISBN']);
Route::get('books/search-by-isbn', [BookController::class, 'searchByISBN']);

// リソースルートを後に定義
Route::apiResource('books', BookController::class);

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
