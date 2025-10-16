<?php

namespace App\Http\Controllers;

use App\Models\LibraryDuty;
use App\Models\Borrow;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LibraryDutyController extends Controller
{
    /**
     * 図書当番記録一覧を取得
     */
    public function index(Request $request)
    {
        try {
            Log::info('LibraryDutyController index - Starting request');
            
            // 認証チェック
            $currentUser = Auth::user();
            
            if (!$currentUser && $request->has('current_user_email')) {
                $email = $request->input('current_user_email');
                $currentUser = Student::where('email', $email)->first();
                if ($currentUser) {
                    Log::info('LibraryDutyController - User identified from request parameter');
                }
            }
            
            // 管理者チェック
            if (!$currentUser || !$currentUser->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'この機能は管理者のみ利用できます。'
                ], 403);
            }
            
            // ページネーション設定
            $perPage = $request->get('per_page', 30);
            
            // 図書当番記録を取得（最新順）
            $duties = LibraryDuty::orderBy('duty_date', 'desc')
                ->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $duties->items(),
                'pagination' => [
                    'current_page' => $duties->currentPage(),
                    'last_page' => $duties->lastPage(),
                    'per_page' => $duties->perPage(),
                    'total' => $duties->total()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('LibraryDutyController index - Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '図書当番記録の取得に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 本日の図書当番記録を取得または作成
     */
    public function today(Request $request)
    {
        try {
            // 認証チェック
            $currentUser = Auth::user();
            
            if (!$currentUser && $request->has('current_user_email')) {
                $email = $request->input('current_user_email');
                $currentUser = Student::where('email', $email)->first();
            }
            
            if (!$currentUser || !$currentUser->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'この機能は管理者のみ利用できます。'
                ], 403);
            }
            
            $today = Carbon::today()->format('Y-m-d');
            
            // 本日の貸出人数を計算
            $borrowCount = Borrow::whereDate('borrowed_date', $today)->count();
            
            // 本日の記録を取得または作成
            $duty = LibraryDuty::firstOrCreate(
                ['duty_date' => $today],
                [
                    'visitor_count' => 0,
                    'borrow_count' => $borrowCount,
                    'reflection' => '',
                    'student_name_1' => '',
                    'student_name_2' => ''
                ]
            );
            
            // 貸出人数を更新
            $duty->borrow_count = $borrowCount;
            $duty->save();
            
            return response()->json([
                'success' => true,
                'data' => $duty
            ]);
        } catch (\Exception $e) {
            Log::error('LibraryDutyController today - Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '本日の記録取得に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 図書当番記録を更新
     */
    public function update(Request $request, $id)
    {
        try {
            // 認証チェック
            $currentUser = Auth::user();
            
            if (!$currentUser && $request->has('current_user_email')) {
                $email = $request->input('current_user_email');
                $currentUser = Student::where('email', $email)->first();
            }
            
            if (!$currentUser || !$currentUser->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'この機能は管理者のみ利用できます。'
                ], 403);
            }
            
            $duty = LibraryDuty::findOrFail($id);
            
            // バリデーション
            $validated = $request->validate([
                'visitor_count' => 'required|integer|min:0',
                'reflection' => 'nullable|string',
                'student_name_1' => 'nullable|string|max:255',
                'student_name_2' => 'nullable|string|max:255'
            ]);
            
            // 該当日の貸出人数を再計算
            $borrowCount = Borrow::whereDate('borrowed_date', $duty->duty_date)->count();
            
            $duty->update([
                'visitor_count' => $validated['visitor_count'],
                'borrow_count' => $borrowCount,
                'reflection' => $validated['reflection'] ?? '',
                'student_name_1' => $validated['student_name_1'] ?? '',
                'student_name_2' => $validated['student_name_2'] ?? ''
            ]);
            
            return response()->json([
                'success' => true,
                'message' => '更新しました',
                'data' => $duty
            ]);
        } catch (\Exception $e) {
            Log::error('LibraryDutyController update - Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '更新に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * PDF出力
     */
    public function exportPdf(Request $request)
    {
        try {
            Log::info('LibraryDutyController exportPdf - Starting request');
            
            // 認証チェック
            $currentUser = Auth::user();
            
            if (!$currentUser && $request->has('current_user_email')) {
                $email = $request->input('current_user_email');
                $currentUser = Student::where('email', $email)->first();
            }
            
            if (!$currentUser || !$currentUser->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'この機能は管理者のみ利用できます。'
                ], 403);
            }
            
            // 期間指定（デフォルトは今月）
            $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
            
            // 指定期間の記録を取得
            $duties = LibraryDuty::whereBetween('duty_date', [$startDate, $endDate])
                ->orderBy('duty_date', 'asc')
                ->get();
            
            // 統計情報を計算
            $stats = [
                'total_days' => $duties->count(),
                'total_visitors' => $duties->sum('visitor_count'),
                'total_borrows' => $duties->sum('borrow_count'),
                'avg_visitors' => $duties->count() > 0 ? round($duties->avg('visitor_count'), 1) : 0,
                'avg_borrows' => $duties->count() > 0 ? round($duties->avg('borrow_count'), 1) : 0,
            ];
            
            $data = [
                'duties' => $duties,
                'stats' => $stats,
                'start_date' => $startDate,
                'end_date' => $endDate
            ];
            
            // PDF生成
            $pdf = \App\Services\LibraryDutyPdfService::generate($data);
            
            $filename = '図書当番記録_' . date('Y年m月d日') . '.pdf';
            
            return response($pdf, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                
        } catch (\Exception $e) {
            Log::error('LibraryDutyController exportPdf - Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'PDF出力に失敗しました。',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
