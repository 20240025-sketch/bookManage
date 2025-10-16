<?php

namespace App\Http\Controllers;

use App\Models\Borrow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PDF;

class BorrowStatusController extends Controller
{
    /**
     * 貸出状況一覧を取得
     */
    public function index(Request $request)
    {
        try {
            Log::info('BorrowStatusController index - Starting request');
            
            // 認証チェック
            $currentUser = Auth::user();
            
            // リクエストパラメータから現在のユーザーを特定
            if (!$currentUser && $request->has('current_user_email')) {
                $email = $request->input('current_user_email');
                $currentUser = \App\Models\Student::where('email', $email)->first();
                if ($currentUser) {
                    Log::info('BorrowStatusController - User identified from request parameter (ID: ' . $currentUser->id . ', Email: ' . $currentUser->email . ')');
                }
            }
            
            // 管理者チェック
            if (!$currentUser || !$currentUser->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'この機能は管理者のみ利用できます。'
                ], 403);
            }
            
            // 現在貸出中の本を取得
            $query = Borrow::with(['book', 'student', 'student.schoolClass'])
                ->whereNull('returned_date')
                ->orderBy('due_date', 'asc');
            
            // 滞納フィルター
            if ($request->has('filter') && $request->filter === 'overdue') {
                $query->where('due_date', '<', now()->startOfDay());
            } elseif ($request->has('filter') && $request->filter === 'not_overdue') {
                $query->where('due_date', '>=', now()->startOfDay());
            }
            
            // 検索フィルター
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('book', function ($bookQuery) use ($search) {
                        $bookQuery->where('title', 'like', "%{$search}%")
                                  ->orWhere('author', 'like', "%{$search}%");
                    })->orWhereHas('student', function ($studentQuery) use ($search) {
                        $studentQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('student_number', 'like', "%{$search}%");
                    });
                });
            }
            
            // 学年フィルター
            if ($request->filled('grade')) {
                $query->whereHas('student.schoolClass', function ($q) use ($request) {
                    $q->where('grade', $request->grade);
                });
            }
            
            // クラスフィルター
            if ($request->filled('class')) {
                $query->whereHas('student.schoolClass', function ($q) use ($request) {
                    $q->where('name', $request->class);
                });
            }
            
            $borrows = $query->get();
            
            // 滞納日数を計算
            $borrowsWithOverdue = $borrows->map(function ($borrow) {
                $dueDate = \Carbon\Carbon::parse($borrow->due_date);
                $today = \Carbon\Carbon::now()->startOfDay();
                
                $overdueDays = 0;
                $isOverdue = false;
                
                if ($dueDate->lt($today)) {
                    $overdueDays = $dueDate->diffInDays($today);
                    $isOverdue = true;
                }
                
                return [
                    'id' => $borrow->id,
                    'book' => [
                        'id' => $borrow->book->id,
                        'title' => $borrow->book->title,
                        'author' => $borrow->book->author,
                        'publisher' => $borrow->book->publisher,
                        'isbn' => $borrow->book->isbn,
                    ],
                    'student' => [
                        'id' => $borrow->student->id,
                        'name' => $borrow->student->name,
                        'student_number' => $borrow->student->student_number,
                        'email' => $borrow->student->email,
                        'school_class' => $borrow->student->schoolClass ? [
                            'grade' => $borrow->student->schoolClass->grade,
                            'name' => $borrow->student->schoolClass->name,
                        ] : null,
                    ],
                    'borrowed_date' => $borrow->borrowed_date,
                    'due_date' => $borrow->due_date,
                    'is_overdue' => $isOverdue,
                    'overdue_days' => $overdueDays,
                ];
            });
            
            // 統計情報
            $totalBorrows = $borrowsWithOverdue->count();
            $overdueBorrows = $borrowsWithOverdue->where('is_overdue', true)->count();
            $notOverdueBorrows = $totalBorrows - $overdueBorrows;
            
            return response()->json([
                'success' => true,
                'data' => $borrowsWithOverdue->values(),
                'statistics' => [
                    'total' => $totalBorrows,
                    'overdue' => $overdueBorrows,
                    'not_overdue' => $notOverdueBorrows,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('BorrowStatusController index - Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '貸出状況の取得に失敗しました。'
            ], 500);
        }
    }
    
    /**
     * 貸出状況をPDF出力
     */
    public function exportPdf(Request $request)
    {
        try {
            Log::info('BorrowStatusController exportPdf - Starting request');
            
            // 認証チェック
            $currentUser = Auth::user();
            
            // リクエストパラメータから現在のユーザーを特定
            if (!$currentUser && $request->has('current_user_email')) {
                $email = $request->input('current_user_email');
                $currentUser = \App\Models\Student::where('email', $email)->first();
            }
            
            // 管理者チェック
            if (!$currentUser || !$currentUser->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'この機能は管理者のみ利用できます。'
                ], 403);
            }
            
            // 現在貸出中の本を取得
            $query = Borrow::with(['book', 'student', 'student.schoolClass'])
                ->whereNull('returned_date')
                ->orderBy('due_date', 'asc');
            
            // 滞納フィルター
            if ($request->has('filter') && $request->filter === 'overdue') {
                $query->where('due_date', '<', now()->startOfDay());
            } elseif ($request->has('filter') && $request->filter === 'not_overdue') {
                $query->where('due_date', '>=', now()->startOfDay());
            }
            
            // 検索フィルター
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('book', function ($bookQuery) use ($search) {
                        $bookQuery->where('title', 'like', "%{$search}%")
                                  ->orWhere('author', 'like', "%{$search}%");
                    })->orWhereHas('student', function ($studentQuery) use ($search) {
                        $studentQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('student_number', 'like', "%{$search}%");
                    });
                });
            }
            
            // 学年フィルター
            if ($request->filled('grade')) {
                $query->whereHas('student.schoolClass', function ($q) use ($request) {
                    $q->where('grade', $request->grade);
                });
            }
            
            // クラスフィルター
            if ($request->filled('class')) {
                $query->whereHas('student.schoolClass', function ($q) use ($request) {
                    $q->where('name', $request->class);
                });
            }
            
            $borrows = $query->get();
            
            // 滞納日数を計算
            $borrowsWithOverdue = $borrows->map(function ($borrow) {
                $dueDate = \Carbon\Carbon::parse($borrow->due_date);
                $today = \Carbon\Carbon::now()->startOfDay();
                
                $overdueDays = 0;
                $isOverdue = false;
                
                if ($dueDate->lt($today)) {
                    $overdueDays = $dueDate->diffInDays($today);
                    $isOverdue = true;
                }
                
                $borrow->is_overdue = $isOverdue;
                $borrow->overdue_days = $overdueDays;
                
                return $borrow;
            });
            
            // 統計情報
            $totalBorrows = $borrowsWithOverdue->count();
            $overdueBorrows = $borrowsWithOverdue->where('is_overdue', true)->count();
            
            // PDF生成
            $pdf = \App\Services\BorrowStatusPdfService::generate($borrowsWithOverdue, [
                'total' => $totalBorrows,
                'overdue' => $overdueBorrows,
                'filter' => $request->filter,
            ]);
            
            $filename = '貸出状況_' . date('Y年m月d日') . '.pdf';
            
            return response($pdf, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                
        } catch (\Exception $e) {
            Log::error('BorrowStatusController exportPdf - Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'PDF出力に失敗しました。'
            ], 500);
        }
    }
}
