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
}
