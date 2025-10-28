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
            
            // 図書当番記録を取得（最新順、同日は昼休み→放課後の順）
            $duties = LibraryDuty::orderBy('duty_date', 'desc')
                ->orderByRaw("CASE WHEN shift_type = 'lunch' THEN 1 WHEN shift_type = 'after_school' THEN 2 ELSE 3 END")
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
            
            $today = Carbon::today();
            $shiftType = $request->input('shift_type', 'lunch'); // デフォルトは昼休み
            
            // 本日の貸出人数を計算
            $borrowCount = Borrow::whereDate('borrowed_date', $today)->count();
            
            // 本日の指定された時間帯の記録を取得または作成
            $duty = LibraryDuty::whereDate('duty_date', $today)
                ->where('shift_type', $shiftType)
                ->first();
            
            if (!$duty) {
                $duty = LibraryDuty::create([
                    'duty_date' => $today->format('Y-m-d'),
                    'shift_type' => $shiftType,
                    'visitor_count' => 0,
                    'borrow_count' => $borrowCount,
                    'reflection' => '',
                    'student_id' => null,
                    'student_id_2' => null,
                    'student_name_1' => '',
                    'student_name_2' => ''
                ]);
            } else {
                // 貸出人数を更新
                $duty->borrow_count = $borrowCount;
                $duty->save();
            }
            
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
                'student_name_2' => 'nullable|string|max:255',
                'shift_type' => 'nullable|in:lunch,after_school'
            ]);
            
            // 該当日の貸出人数を再計算
            $borrowCount = Borrow::whereDate('borrowed_date', $duty->duty_date)->count();
            
            $duty->update([
                'visitor_count' => $validated['visitor_count'],
                'borrow_count' => $borrowCount,
                'reflection' => $validated['reflection'] ?? '',
                'student_name_1' => $validated['student_name_1'] ?? '',
                'student_name_2' => $validated['student_name_2'] ?? '',
                'shift_type' => $validated['shift_type'] ?? $duty->shift_type
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
     * 図書当番記録を削除
     */
    public function destroy(Request $request, $id)
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
            $duty->delete();
            
            return response()->json([
                'success' => true,
                'message' => '削除しました'
            ]);
        } catch (\Exception $e) {
            Log::error('LibraryDutyController destroy - Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '削除に失敗しました。',
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
            
            // 時間帯指定（オプション）
            $shiftType = $request->input('shift_type');
            
            // 指定期間の記録を取得
            $query = LibraryDuty::whereBetween('duty_date', [$startDate, $endDate]);
            
            // 時間帯が指定されている場合はフィルタリング
            if ($shiftType && in_array($shiftType, ['lunch', 'after_school'])) {
                $query->where('shift_type', $shiftType);
            }
            
            $duties = $query->orderBy('duty_date', 'asc')
                ->orderByRaw("CASE WHEN shift_type = 'lunch' THEN 0 ELSE 1 END")
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
                'end_date' => $endDate,
                'shift_type' => $shiftType
            ];
            
            // PDF生成
            $pdf = \App\Services\LibraryDutyPdfService::generate($data);
            
            $shiftLabel = $shiftType === 'lunch' ? '昼休み_' : ($shiftType === 'after_school' ? '放課後_' : '');
            $filename = '図書当番記録_' . $shiftLabel . date('Y年m月d日') . '.pdf';
            
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
