<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * 生徒ログイン
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラー',
                'errors' => $validator->errors()
            ], 422);
        }

        $student = Student::where('email', $request->email)->first();

        if (!$student) {
            // 管理者の条件を満たす場合は自動的にアカウントを作成
            // 条件: 1. @以降が seiei.ac.jp, 2. メールアドレスが数字から始まらない
            $parts = explode('@', $request->email);
            $isAdminEmail = false;
            
            if (count($parts) === 2) {
                $localPart = $parts[0];
                $domain = $parts[1];
                $isAdminEmail = ($domain === 'seiei.ac.jp' && !is_numeric(substr($localPart, 0, 1)));
            }
            
            if ($isAdminEmail) {
                // 管理者用の新規アカウントを作成
                $student = new Student();
                $student->email = $request->email;
                $student->name = explode('@', $request->email)[0]; // メールアドレスの@前を名前として使用
                $student->student_number = 'ADMIN-' . time(); // 一時的な学籍番号
                $student->password = Hash::make($request->password);
                $student->save();
                
                Log::info('Auto-created admin account', [
                    'email' => $request->email,
                    'student_id' => $student->id
                ]);
            } else {
                // 利用者の場合は登録されていないエラーを返す
                return response()->json([
                    'success' => false,
                    'message' => 'このメールアドレスは登録されていません。'
                ], 401);
            }
        }

        if (!$student->password) {
            return response()->json([
                'success' => false,
                'message' => 'パスワードが設定されていません。初回パスワード設定を行ってください。',
                'requires_password_setup' => true
            ], 401);
        }

        if (!Hash::check($request->password, $student->password)) {
            return response()->json([
                'success' => false,
                'message' => 'メールアドレスまたはパスワードが正しくありません。'
            ], 401);
        }

        // セッションベース認証
        Auth::login($student);
        
        // デバッグ: ログイン後の状態確認
        Log::info('Auth login - Student logged in', [
            'student_id' => $student->id,
            'session_id' => session()->getId(),
            'auth_check' => Auth::check(),
            'auth_user' => Auth::user() ? Auth::user()->id : null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ログインしました。',
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'student_number' => $student->student_number,
                'grade' => $student->grade,
                'class' => $student->class
            ],
            'permissions' => [
                'isAdmin' => $student->isAdmin(),
                'canCreateBooks' => $student->canCreateBooks(),
                'canViewStudents' => $student->canViewStudents(),
                'canCreateBorrows' => $student->canCreateBorrows(),
                'canViewBookRequestHistory' => $student->canViewBookRequestHistory(),
                'canEditBooks' => $student->canEditBooks(),
                'canUseBorrowFeatures' => $student->canUseBorrowFeatures()
            ]
        ]);
    }

    /**
     * ログアウト
     */
    public function logout(Request $request)
    {
        Auth::logout();
        
        return response()->json([
            'success' => true,
            'message' => 'ログアウトしました。'
        ]);
    }

    /**
     * 初回パスワード設定
     */
    public function setupPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラー',
                'errors' => $validator->errors()
            ], 422);
        }

        $student = Student::where('email', $request->email)->first();

        if (!$student) {
            // 管理者の条件を満たす場合は自動的にアカウントを作成
            // 条件: 1. @以降が seiei.ac.jp, 2. メールアドレスが数字から始まらない
            $parts = explode('@', $request->email);
            $isAdminEmail = false;
            
            if (count($parts) === 2) {
                $localPart = $parts[0];
                $domain = $parts[1];
                $isAdminEmail = ($domain === 'seiei.ac.jp' && !is_numeric(substr($localPart, 0, 1)));
            }
            
            if ($isAdminEmail) {
                // 管理者用の新規アカウントを作成
                $student = new Student();
                $student->email = $request->email;
                $student->name = explode('@', $request->email)[0]; // メールアドレスの@前を名前として使用
                $student->student_number = 'ADMIN-' . time(); // 一時的な学籍番号
                $student->password = Hash::make($request->password);
                $student->save();
                
                Log::info('Auto-created admin account via password setup', [
                    'email' => $request->email,
                    'student_id' => $student->id
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'アカウントを作成し、パスワードを設定しました。ログインしてください。'
                ]);
            } else {
                // 利用者の場合は登録されていないエラーを返す
                return response()->json([
                    'success' => false,
                    'message' => 'このメールアドレスは登録されていません。'
                ], 404);
            }
        }

        if ($student->password) {
            return response()->json([
                'success' => false,
                'message' => 'パスワードは既に設定されています。'
            ], 400);
        }

        $student->password = Hash::make($request->password);
        $student->save();

        return response()->json([
            'success' => true,
            'message' => 'パスワードを設定しました。ログインしてください。'
        ]);
    }

    /**
     * パスワード変更
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラー',
                'errors' => $validator->errors()
            ], 422);
        }

        // メールアドレスで生徒を検索
        $student = Student::where('email', $request->email)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'ユーザーが見つかりません。'
            ], 404);
        }

        // パスワードが設定されていない場合
        if (!$student->password) {
            return response()->json([
                'success' => false,
                'message' => 'パスワードが設定されていません。先にパスワード設定を行ってください。'
            ], 400);
        }

        // 現在のパスワードを確認
        if (!Hash::check($request->current_password, $student->password)) {
            return response()->json([
                'success' => false,
                'message' => '現在のパスワードが正しくありません。'
            ], 400);
        }

        // 新しいパスワードを設定
        $student->password = Hash::make($request->password);
        $student->save();

        return response()->json([
            'success' => true,
            'message' => 'パスワードを変更しました。'
        ]);
    }

    /**
     * 現在の認証情報を取得
     */
    public function me(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'ログインしていません。'
            ], 401);
        }

        $student = Auth::user();

        return response()->json([
            'success' => true,
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'student_number' => $student->student_number,
                'grade' => $student->grade,
                'class' => $student->class
            ]
        ]);
    }
}
