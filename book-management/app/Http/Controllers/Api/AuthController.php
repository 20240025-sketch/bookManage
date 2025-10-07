<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            return response()->json([
                'success' => false,
                'message' => 'このメールアドレスは登録されていません。'
            ], 401);
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
            return response()->json([
                'success' => false,
                'message' => 'このメールアドレスは登録されていません。'
            ], 404);
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
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'ログインが必要です。'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
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

        $authUser = Auth::user();
        $student = Student::find($authUser->id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'ユーザーが見つかりません。'
            ], 404);
        }

        if (!Hash::check($request->current_password, $student->password)) {
            return response()->json([
                'success' => false,
                'message' => '現在のパスワードが正しくありません。'
            ], 400);
        }

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
