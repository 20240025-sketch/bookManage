<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::query();

        // 検索
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // カテゴリフィルター
        if ($request->filled('category')) {
            $query->category($request->category);
        }

        // 読書状況フィルター
        if ($request->filled('reading_status')) {
            $query->readingStatus($request->reading_status);
        }

        // ソート
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // ページネーション
        $books = $query->paginate(20);

        return BookResource::collection($books);
    }

    public function store(StoreBookRequest $request)
    {
        $book = Book::create($request->validated());
        
        return new BookResource($book);
    }

    public function show(Book $book)
    {
        return new BookResource($book);
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        $book->update($request->validated());
        
        return new BookResource($book);
    }

    public function destroy(Book $book)
    {
        $book->delete();
        
        return response()->noContent();
    }
}
