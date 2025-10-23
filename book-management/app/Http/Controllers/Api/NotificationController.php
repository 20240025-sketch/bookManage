<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * 通知一覧を取得
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // リクエストパラメータまたはセッションから学生情報を取得
            $studentId = $request->query('student_id') ?? $request->input('student_id');
            
            if (!$studentId) {
                $student = session('student');
                if ($student) {
                    $studentId = is_array($student) ? $student['id'] : $student->id;
                }
            }
            
            Log::info("通知取得リクエスト", [
                'query_student_id' => $request->query('student_id'),
                'input_student_id' => $request->input('student_id'),
                'session_student' => session('student'),
                'final_student_id' => $studentId
            ]);
            
            // 認証されていない場合は空配列を返す（エラーにしない）
            if (!$studentId) {
                Log::warning("通知取得: Student IDが取得できませんでした");
                return response()->json([
                    'data' => [],
                    'message' => '未認証',
                    'success' => true
                ]);
            }

            Log::info("通知取得: Student ID = {$studentId}");

            // 現在のユーザーの通知のみを取得（student_idがnullの場合も含める）
            $query = Notification::with(['bookRequest', 'book'])
                ->where(function($q) use ($studentId) {
                    $q->where('student_id', $studentId)
                      ->orWhereNull('student_id');
                })
                ->orderBy('created_at', 'desc');

            // 未読のみフィルター
            if ($request->filled('unread_only') && $request->unread_only) {
                $query->unread();
            }

            // リクエストIDでフィルター
            if ($request->filled('book_request_id')) {
                $query->where('book_request_id', $request->book_request_id);
            }

            $notifications = $query->get();

            return response()->json([
                'data' => $notifications,
                'message' => 'Notifications retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Notifications retrieval error: ' . $e->getMessage());
            return response()->json([
                'message' => '通知の取得に失敗しました'
            ], 500);
        }
    }

    /**
     * 未読通知数を取得
     */
    public function unreadCount(Request $request): JsonResponse
    {
        try {
            // リクエストパラメータまたはセッションから学生情報を取得
            $studentId = $request->query('student_id') ?? $request->input('student_id');
            
            if (!$studentId) {
                $student = session('student');
                if ($student) {
                    $studentId = is_array($student) ? $student['id'] : $student->id;
                }
            }
            
            // 認証されていない場合は0を返す（エラーにしない）
            if (!$studentId) {
                return response()->json([
                    'count' => 0,
                    'success' => true,
                    'message' => '未認証'
                ]);
            }

            Log::info("未読通知数取得: Student ID = {$studentId}");

            // 現在のユーザーの未読通知数のみを取得
            $count = Notification::unread()
                ->where('student_id', $studentId)
                ->count();

            Log::info("未読通知数: {$count}件");

            return response()->json([
                'count' => $count,
                'success' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Unread count retrieval error: ' . $e->getMessage());
            return response()->json([
                'message' => '未読数の取得に失敗しました',
                'count' => 0,
                'success' => false
            ], 500);
        }
    }

    /**
     * 通知を既読にする
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        try {
            $notification->markAsRead();

            return response()->json([
                'data' => $notification,
                'message' => '通知を既読にしました',
                'success' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Mark as read error: ' . $e->getMessage());
            return response()->json([
                'message' => '既読処理に失敗しました',
                'success' => false
            ], 500);
        }
    }

    /**
     * すべての通知を既読にする
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            // セッションから学生情報を取得
            $student = session('student');
            
            if (!$student) {
                return response()->json([
                    'message' => '認証が必要です',
                    'success' => false
                ], 401);
            }

            $studentId = is_array($student) ? $student['id'] : $student->id;

            // 現在のユーザーの未読通知のみを既読にする
            $updated = Notification::unread()
                ->where('student_id', $studentId)
                ->update(['is_read' => true]);

            return response()->json([
                'count' => $updated,
                'message' => 'すべての通知を既読にしました',
                'success' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Mark all as read error: ' . $e->getMessage());
            return response()->json([
                'message' => '既読処理に失敗しました',
                'success' => false
            ], 500);
        }
    }

    /**
     * 通知を削除
     */
    public function destroy(Notification $notification): JsonResponse
    {
        try {
            $notification->delete();

            return response()->json([
                'message' => '通知を削除しました',
                'success' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Notification deletion error: ' . $e->getMessage());
            return response()->json([
                'message' => '通知の削除に失敗しました',
                'success' => false
            ], 500);
        }
    }
}
