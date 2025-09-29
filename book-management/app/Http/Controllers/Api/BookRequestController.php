<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class BookRequestController extends Controller
{
    /**
     * リクエスト一覧を取得
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = BookRequest::with('student')
                ->orderBy('created_at', 'desc');

            // ステータスでフィルター
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // 特定の生徒のリクエストのみ取得
            if ($request->filled('student_id')) {
                $query->where('student_id', $request->student_id);
            }

            $requests = $query->get();

            return response()->json([
                'data' => $requests,
                'message' => 'Book requests retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Book requests retrieval error: ' . $e->getMessage());
            return response()->json([
                'message' => 'リクエストの取得に失敗しました'
            ], 500);
        }
    }

    /**
     * 新しいリクエストを作成
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'author' => 'nullable|string|max:255',
                'requester_name' => 'nullable|string|max:255',
            ]);

            $bookRequest = BookRequest::create([
                ...$validated,
                'status' => 'pending'
            ]);

            return response()->json([
                'data' => $bookRequest,
                'message' => 'リクエストを登録しました',
                'success' => true
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Book request validation error: ' . $e->getMessage());
            return response()->json([
                'message' => 'リクエストの内容に問題があります',
                'errors' => $e->errors(),
                'success' => false
            ], 422);
        } catch (\Exception $e) {
            Log::error('Book request creation error: ' . $e->getMessage());
            return response()->json([
                'message' => 'リクエストの登録に失敗しました',
                'success' => false
            ], 500);
        }
    }

    /**
     * リクエストのステータスを更新
     */
    public function updateStatus(Request $request, BookRequest $bookRequest): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => ['required', Rule::in(['approved', 'rejected'])],
                'admin_comment' => 'nullable|string|max:1000'
            ]);

            $bookRequest->update($validated);

            return response()->json([
                'data' => $bookRequest,
                'message' => 'ステータスを更新しました'
            ]);
        } catch (\Exception $e) {
            Log::error('Book request status update error: ' . $e->getMessage());
            return response()->json([
                'message' => 'ステータスの更新に失敗しました'
            ], 500);
        }
    }

    /**
     * リクエストを削除
     */
    public function destroy(BookRequest $bookRequest): JsonResponse
    {
        try {
            $bookRequest->delete();

            return response()->json([
                'message' => 'リクエストを削除しました',
                'success' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Book request deletion error: ' . $e->getMessage());
            return response()->json([
                'message' => 'リクエストの削除に失敗しました',
                'success' => false
            ], 500);
        }
    }
}
