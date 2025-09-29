<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Student::query()
            ->with('schoolClass') // classesテーブルとのリレーションを事前に読み込み
            ->withCount(['borrows as active_borrows_count' => function ($query) {
                $query->whereNull('returned_date');
            }])
            ->withCount(['borrows as overdue_borrows_count' => function ($query) {
                $query->whereNull('returned_date')
                      ->where('due_date', '<', now()->startOfDay());
            }])
            ->withCount('borrows as total_borrows_count'); // 総貸出数を追加

        // 検索フィルター
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('student_number', 'like', "%{$search}%");
            });
        }

        // 学年フィルター - classesテーブルを参照
        if ($request->filled('grade')) {
            $query->byGrade($request->grade);
        }

        // 組フィルター - classesテーブルを参照
        if ($request->filled('class')) {
            $query->byClass($request->class);
        }

        // 出席番号での検索（完全一致）
        if ($request->filled('student_number')) {
            $query->where('student_number', $request->student_number);
        }

        // class_numberがnullの場合とそうでない場合を考慮したソート
        $students = $query->leftJoin('classes', 'students.class_number', '=', 'classes.class_number')
                         ->orderByRaw('classes.grade IS NULL, classes.grade')
                         ->orderByRaw('classes.kumi IS NULL, classes.kumi')
                         ->orderBy('students.name')
                         ->select('students.*') // studentsテーブルのカラムのみ選択
                         ->get();

        // アチーブメント情報を含むレスポンスデータを準備
        $studentsWithAchievements = $students->map(function ($student) {
            $studentArray = $student->toArray();
            $studentArray['achievement'] = $student->achievement;
            $studentArray['ndc_achievements'] = $student->ndc_achievements;
            return $studentArray;
        });

        return response()->json([
            'data' => $studentsWithAchievements,
            'message' => 'Students retrieved successfully'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $student = Student::create($validated);
        return response()->json([
            'data' => $student,
            'message' => 'Student created successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student): JsonResponse
    {
        $student->load([
            'borrows' => function ($query) {
                $query->with('book')
                      ->orderBy('borrowed_date', 'desc');
            }
        ]);

        // アクティブな貸出と履歴を分離
        $activeborrows = $student->borrows->whereNull('returned_date');
        $borrowHistory = $student->borrows->whereNotNull('returned_date');

        return response()->json([
            'data' => [
                'student' => $student,
                'active_borrows' => $activeborrows,
                'borrow_history' => $borrowHistory
            ],
            'message' => 'Student retrieved successfully'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $validated = $request->validated();
        $student->update($validated);
        return response()->json([
            'data' => $student,
            'message' => 'Student updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student): JsonResponse
    {
        $student->delete();

        return response()->json([
            'message' => 'Student deleted successfully'
        ]);
    }

    /**
     * Get borrows for a specific student.
     */
    public function getBorrows(Student $student): JsonResponse
    {
        $student->load(['borrows' => function ($query) {
            $query->with('book')->orderBy('borrowed_date', 'desc');
        }]);

        $activeBorrows = $student->borrows->whereNull('returned_date')->values()->map(function ($borrow) {
            $borrow->due_date = $borrow->due_date?->format('Y-m-d');
            return $borrow;
        });
        $borrowHistory = $student->borrows->whereNotNull('returned_date')->values()->map(function ($borrow) {
            $borrow->due_date = $borrow->due_date?->format('Y-m-d');
            return $borrow;
        });

        // 総貸出冊数を計算
        $totalBorrowsCount = $student->borrows->count();

        return response()->json([
            'active_borrows' => $activeBorrows,
            'borrow_history' => $borrowHistory,
            'total_borrows_count' => $totalBorrowsCount
        ]);
    }
}
