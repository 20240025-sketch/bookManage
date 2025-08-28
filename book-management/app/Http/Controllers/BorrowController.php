<?php

namespace App\Http\Controllers;

use App\Models\Borrow;
use App\Models\Book;
use App\Models\Student;
use App\Http\Requests\StoreBorrowRequest;
use App\Http\Requests\UpdateBorrowRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class BorrowController extends Controller
{
    /**
     * 最近の貸出を取得
     */
    public function recent(): JsonResponse
    {
        $recentBorrows = Borrow::with(['book', 'student'])
            ->orderByDesc('borrowed_date')
            ->take(10)
            ->get();

        return response()->json([
            'data' => $recentBorrows,
            'message' => 'Recent borrows retrieved successfully'
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Borrow::with(['book', 'student']);

        // フィルタリング
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->filled('book_id')) {
            $query->where('book_id', $request->book_id);
        }
        if ($request->filled('status')) {
            if ($request->status === 'borrowed') {
                $query->whereNull('returned_date');
            } elseif ($request->status === 'returned') {
                $query->whereNotNull('returned_date');
            }
        }

        $borrows = $query->orderBy('borrowed_date', 'desc')->get();
        
        return response()->json([
            'data' => $borrows,
            'message' => 'Borrows retrieved successfully'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBorrowRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $borrow = Borrow::create($validated);
        return response()->json([
            'data' => $borrow,
            'message' => 'Borrow created successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Borrow $borrow): JsonResponse
    {
        $borrow->load(['book', 'student']);
        return response()->json([
            'data' => $borrow,
            'message' => 'Borrow retrieved successfully'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBorrowRequest $request, Borrow $borrow): JsonResponse
    {
        $validated = $request->validated();
        $borrow->update($validated);
        return response()->json([
            'data' => $borrow,
            'message' => 'Borrow updated successfully'
        ]);
    }

    /**
     * Mark a book as returned.
     */
    public function returnBook(Borrow $borrow): JsonResponse
    {
        if ($borrow->returned_date) {
            return response()->json([
                'message' => 'This book has already been returned'
            ], 422);
        }

        $borrow->update([
            'returned_date' => now()
        ]);

        return response()->json([
            'data' => $borrow,
            'message' => 'Book marked as returned successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Borrow $borrow): JsonResponse
    {
        $borrow->delete();

        return response()->json([
            'message' => 'Borrow deleted successfully'
        ]);
    }
}
