<?php

namespace App\Http\Controllers;

use App\Models\Borrow;
use App\Models\Book;
use App\Models\Student;
use App\Http\Requests\StoreBorrowRequest;
use App\Http\Requests\UpdateBorrowRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        
        // 返却期限日を設定（借用日の14日後）
        if (!isset($validated['due_date'])) {
            $borrowedDate = \Carbon\Carbon::parse($validated['borrowed_date']);
            $validated['due_date'] = $borrowedDate->addDays(14)->toDateString();
        }
        
        $borrow = Borrow::create($validated);
        return response()->json([
            'data' => $borrow,
            'message' => 'Borrow created successfully'
        ], 201);
    }

    /**
     * 複数の本を一括で貸出
     */
    public function batchStore(Request $request): JsonResponse
    {
        $request->validate([
            'book_ids' => 'required|array',
            'book_ids.*' => 'required|integer|exists:books,id',
            'student_id' => 'required|integer|exists:students,id',
            'borrowed_date' => 'required|date|before_or_equal:today'
        ]);

        $bookIds = $request->book_ids;
        $studentId = $request->student_id;
        $borrowedDate = $request->borrowed_date;
        $dueDate = \Carbon\Carbon::parse($borrowedDate)->addDays(14)->toDateString();

        // 冊数と在庫チェック
        $bookCounts = array_count_values($bookIds);
        $unavailableBooks = [];
        
        foreach ($bookCounts as $bookId => $requestedCount) {
            $book = Book::with(['borrows' => function($query) {
                $query->whereNull('returned_date');
            }])->find($bookId);
            
            if (!$book) {
                return response()->json([
                    'message' => '存在しない本が選択されています'
                ], 422);
            }
            
            $currentBorrowedCount = $book->borrows->count();
            $totalQuantity = $book->quantity ?? 1;
            $availableQuantity = $totalQuantity - $currentBorrowedCount;
            
            if ($requestedCount > $availableQuantity) {
                $unavailableBooks[] = [
                    'title' => $book->title,
                    'requested' => $requestedCount,
                    'available' => $availableQuantity,
                    'total' => $totalQuantity
                ];
            }
        }
        
        if (!empty($unavailableBooks)) {
            $messages = [];
            foreach ($unavailableBooks as $book) {
                $messages[] = "「{$book['title']}」: {$book['requested']}冊選択されていますが、利用可能なのは{$book['available']}冊です（全{$book['total']}冊中）";
            }
            
            return response()->json([
                'message' => '以下の本で冊数が不足しています: ' . implode(', ', $messages)
            ], 422);
        }

        $createdBorrows = [];
        
        try {
            DB::beginTransaction();

            foreach ($bookIds as $bookId) {
                $borrow = Borrow::create([
                    'book_id' => $bookId,
                    'student_id' => $studentId,
                    'borrowed_date' => $borrowedDate,
                    'due_date' => $dueDate
                ]);
                $createdBorrows[] = $borrow;
            }

            DB::commit();
            
            return response()->json([
                'data' => $createdBorrows,
                'message' => count($createdBorrows) . '冊の本を貸し出しました'
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => '貸出処理中にエラーが発生しました'
            ], 500);
        }
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
     * 複数の本を一括で返却
     */
    public function batchReturn(Request $request): JsonResponse
    {
        $request->validate([
            'borrow_ids' => 'required|array',
            'borrow_ids.*' => 'required|integer|exists:borrows,id'
        ]);

        $borrowIds = $request->borrow_ids;
        
        try {
            DB::beginTransaction();

            // 指定されたIDの貸出記録を取得（未返却のもののみ）
            $borrows = Borrow::with(['book', 'student'])
                ->whereIn('id', $borrowIds)
                ->whereNull('returned_date')
                ->get();

            if ($borrows->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'message' => '返却可能な貸出記録が見つかりません'
                ], 422);
            }

            // 既に返却済みの本があるかチェック
            $alreadyReturned = Borrow::whereIn('id', $borrowIds)
                ->whereNotNull('returned_date')
                ->with('book')
                ->get();

            $returnedCount = 0;
            $returnedBooks = [];

            // 一括で返却日を設定
            foreach ($borrows as $borrow) {
                $borrow->update([
                    'returned_date' => now()
                ]);
                $returnedBooks[] = $borrow->book->title;
                $returnedCount++;
            }

            DB::commit();
            
            $message = $returnedCount . '冊の本を返却しました';
            
            if ($alreadyReturned->count() > 0) {
                $alreadyReturnedTitles = $alreadyReturned->pluck('book.title')->toArray();
                $message .= '（既に返却済み: ' . implode(', ', $alreadyReturnedTitles) . '）';
            }

            return response()->json([
                'data' => [
                    'returned_count' => $returnedCount,
                    'returned_books' => $returnedBooks,
                    'already_returned' => $alreadyReturned->pluck('book.title')->toArray()
                ],
                'message' => $message
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => '返却処理中にエラーが発生しました: ' . $e->getMessage()
            ], 500);
        }
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
